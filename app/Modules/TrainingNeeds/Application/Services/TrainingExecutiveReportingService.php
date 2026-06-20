<?php

namespace App\Modules\TrainingNeeds\Application\Services;

use App\Models\TrainingAnnualPlan;
use App\Models\TrainingNeedItem;
use App\Models\TrainingSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TrainingExecutiveReportingService
{
    public function availableYears(): array
    {
        $years = collect()
            ->merge(TrainingAnnualPlan::query()->pluck('plan_year'))
            ->merge(
                TrainingSession::query()
                    ->selectRaw('DISTINCT '.$this->yearExpression('scheduled_start_at').' as report_year')
                    ->whereNotNull('scheduled_start_at')
                    ->pluck('report_year')
            )
            ->merge(
                TrainingNeedItem::query()
                    ->selectRaw('DISTINCT '.$this->yearExpression('COALESCE(target_completion_date, created_at)').' as report_year')
                    ->pluck('report_year')
            )
            ->filter(fn ($year) => is_numeric($year))
            ->map(fn ($year) => (int) $year)
            ->push((int) now()->year)
            ->unique()
            ->sortDesc()
            ->values();

        return $years->all();
    }

    public function executiveSummary(?int $year = null, ?int $quarter = null): array
    {
        $sessionFacts = $this->applySessionPeriod($this->sessionFactQuery(), $year, $quarter);

        $summary = (clone $sessionFacts)
            ->selectRaw('
                COUNT(*) as sessions_count,
                SUM(CASE WHEN training_sessions.status = "completed" THEN 1 ELSE 0 END) as completed_sessions,
                COALESCE(SUM(session_participants.participant_count), 0) as participants_count,
                COALESCE(SUM(session_participants.attended_count), 0) as attended_count,
                COALESCE(SUM(session_delivery.total_hours), 0) as attended_hours,
                COALESCE(SUM(session_delivery.delivery_records_count), 0) as delivered_trainings_count,
                ROUND(COALESCE(AVG(session_feedback.average_feedback_score), 0), 2) as average_feedback_score,
                COALESCE(SUM(training_sessions.planned_budget), 0) as planned_budget_total,
                COALESCE(SUM(training_sessions.actual_budget), 0) as actual_budget_total,
                SUM(CASE WHEN COALESCE(training_programs.delivery_type, "internal") = "internal" THEN 1 ELSE 0 END) as internal_sessions_count,
                SUM(CASE WHEN COALESCE(training_programs.delivery_type, "internal") = "external" THEN 1 ELSE 0 END) as external_sessions_count,
                SUM(CASE WHEN COALESCE(training_programs.delivery_type, "internal") = "hybrid" THEN 1 ELSE 0 END) as hybrid_sessions_count,
                COALESCE(SUM(CASE WHEN COALESCE(training_programs.delivery_type, "internal") = "internal" THEN session_delivery.total_hours ELSE 0 END), 0) as internal_hours_total,
                COALESCE(SUM(CASE WHEN COALESCE(training_programs.delivery_type, "internal") = "external" THEN session_delivery.total_hours ELSE 0 END), 0) as external_hours_total,
                COALESCE(SUM(CASE WHEN COALESCE(training_programs.delivery_type, "internal") = "hybrid" THEN session_delivery.total_hours ELSE 0 END), 0) as hybrid_hours_total
            ')
            ->first();

        $sessionsCount = (int) ($summary?->sessions_count ?? 0);
        $participantsCount = (int) ($summary?->participants_count ?? 0);
        $attendedCount = (int) ($summary?->attended_count ?? 0);
        $plannedBudget = (float) ($summary?->planned_budget_total ?? 0);
        $actualBudget = (float) ($summary?->actual_budget_total ?? 0);

        return [
            'sessions_count' => $sessionsCount,
            'completed_sessions' => (int) ($summary?->completed_sessions ?? 0),
            'participants_count' => $participantsCount,
            'attended_count' => $attendedCount,
            'attendance_rate' => $participantsCount > 0 ? round(($attendedCount / $participantsCount) * 100, 2) : 0.0,
            'attended_hours' => round((float) ($summary?->attended_hours ?? 0), 2),
            'delivered_trainings_count' => (int) ($summary?->delivered_trainings_count ?? 0),
            'average_feedback_score' => round((float) ($summary?->average_feedback_score ?? 0), 2),
            'planned_budget_total' => round($plannedBudget, 2),
            'actual_budget_total' => round($actualBudget, 2),
            'budget_variance' => round($plannedBudget - $actualBudget, 2),
            'internal_sessions_count' => (int) ($summary?->internal_sessions_count ?? 0),
            'external_sessions_count' => (int) ($summary?->external_sessions_count ?? 0),
            'hybrid_sessions_count' => (int) ($summary?->hybrid_sessions_count ?? 0),
            'internal_hours_total' => round((float) ($summary?->internal_hours_total ?? 0), 2),
            'external_hours_total' => round((float) ($summary?->external_hours_total ?? 0), 2),
            'hybrid_hours_total' => round((float) ($summary?->hybrid_hours_total ?? 0), 2),
        ];
    }

    public function annualRows(int $limit = 5): Collection
    {
        return $this->sessionFactQuery()
            ->whereNotNull('training_sessions.scheduled_start_at')
            ->selectRaw('
                '.$this->yearExpression('training_sessions.scheduled_start_at').' as report_year,
                COUNT(*) as sessions_count,
                SUM(CASE WHEN training_sessions.status = "completed" THEN 1 ELSE 0 END) as completed_sessions,
                COALESCE(SUM(session_participants.participant_count), 0) as participants_count,
                COALESCE(SUM(session_participants.attended_count), 0) as attended_count,
                COALESCE(SUM(session_delivery.total_hours), 0) as attended_hours,
                COALESCE(SUM(training_sessions.planned_budget), 0) as planned_budget_total,
                COALESCE(SUM(training_sessions.actual_budget), 0) as actual_budget_total,
                ROUND(COALESCE(AVG(session_feedback.average_feedback_score), 0), 2) as average_feedback_score
            ')
            ->groupBy('report_year')
            ->orderByDesc('report_year')
            ->limit($limit)
            ->get();
    }

    public function quarterlyRows(?int $year = null): Collection
    {
        $year ??= (int) now()->year;

        return $this->sessionFactQuery()
            ->whereRaw($this->yearExpression('training_sessions.scheduled_start_at').' = ?', [$year])
            ->selectRaw('
                '.$this->quarterExpression('training_sessions.scheduled_start_at').' as report_quarter,
                COUNT(*) as sessions_count,
                SUM(CASE WHEN training_sessions.status = "completed" THEN 1 ELSE 0 END) as completed_sessions,
                COALESCE(SUM(session_participants.participant_count), 0) as participants_count,
                COALESCE(SUM(session_participants.attended_count), 0) as attended_count,
                COALESCE(SUM(session_delivery.total_hours), 0) as attended_hours,
                COALESCE(SUM(training_sessions.planned_budget), 0) as planned_budget_total,
                COALESCE(SUM(training_sessions.actual_budget), 0) as actual_budget_total,
                ROUND(COALESCE(AVG(session_feedback.average_feedback_score), 0), 2) as average_feedback_score
            ')
            ->groupBy('report_quarter')
            ->orderBy('report_quarter')
            ->get();
    }

    public function employeeHoursRows(?int $year = null, ?int $quarter = null, int $limit = 12): Collection
    {
        $query = DB::table('training_delivery_records')
            ->join('training_sessions', 'training_sessions.id', '=', 'training_delivery_records.training_session_id')
            ->leftJoin('training_programs', 'training_programs.id', '=', 'training_delivery_records.training_program_id')
            ->join('personnels', 'personnels.id', '=', 'training_delivery_records.personnel_id')
            ->leftJoin('training_feedback_responses', function ($join): void {
                $join->on('training_feedback_responses.training_session_id', '=', 'training_delivery_records.training_session_id')
                    ->on('training_feedback_responses.personnel_id', '=', 'training_delivery_records.personnel_id');
            })
            ->selectRaw('
                personnels.id as personnel_id,
                CONCAT(personnels.surname, " ", personnels.name, " ", personnels.patronymic) as personnel_fullname,
                personnels.tabel_no as personnel_tabel_no,
                COUNT(training_delivery_records.id) as delivered_trainings_count,
                ROUND(COALESCE(SUM(training_delivery_records.attended_hours), 0), 2) as attended_hours_total,
                SUM(CASE WHEN COALESCE(training_programs.delivery_type, "internal") = "internal" THEN training_delivery_records.attended_hours ELSE 0 END) as internal_hours_total,
                SUM(CASE WHEN COALESCE(training_programs.delivery_type, "external") = "external" THEN training_delivery_records.attended_hours ELSE 0 END) as external_hours_total,
                SUM(CASE WHEN COALESCE(training_programs.delivery_type, "hybrid") = "hybrid" THEN training_delivery_records.attended_hours ELSE 0 END) as hybrid_hours_total,
                ROUND(COALESCE(AVG(training_feedback_responses.overall_score), 0), 2) as average_feedback_score,
                MAX(training_delivery_records.completed_at) as last_completed_at
            ')
            ->groupBy('personnels.id', 'personnel_fullname', 'personnel_tabel_no')
            ->orderByDesc('attended_hours_total')
            ->orderBy('personnel_fullname')
            ->limit($limit);

        $this->applySessionPeriod($query, $year, $quarter, 'training_sessions.scheduled_start_at');

        return $query->get();
    }

    public function deliveryTypeRows(?int $year = null, ?int $quarter = null): Collection
    {
        return $this->applySessionPeriod($this->sessionFactQuery(), $year, $quarter)
            ->selectRaw('
                COALESCE(training_programs.delivery_type, "internal") as delivery_type,
                COUNT(*) as sessions_count,
                SUM(CASE WHEN training_sessions.status = "completed" THEN 1 ELSE 0 END) as completed_sessions,
                COALESCE(SUM(session_participants.participant_count), 0) as participants_count,
                COALESCE(SUM(session_participants.attended_count), 0) as attended_count,
                COALESCE(SUM(session_delivery.total_hours), 0) as attended_hours,
                COALESCE(SUM(training_sessions.planned_budget), 0) as planned_budget_total,
                COALESCE(SUM(training_sessions.actual_budget), 0) as actual_budget_total,
                ROUND(COALESCE(AVG(session_feedback.average_feedback_score), 0), 2) as average_feedback_score
            ')
            ->groupBy('delivery_type')
            ->orderBy('delivery_type')
            ->get();
    }

    public function outcomeRows(?int $year = null, ?int $quarter = null): Collection
    {
        $query = DB::table('training_delivery_records')
            ->join('training_sessions', 'training_sessions.id', '=', 'training_delivery_records.training_session_id')
            ->leftJoin('training_feedback_responses', function ($join): void {
                $join->on('training_feedback_responses.training_session_id', '=', 'training_delivery_records.training_session_id')
                    ->on('training_feedback_responses.personnel_id', '=', 'training_delivery_records.personnel_id');
            })
            ->selectRaw('
                training_delivery_records.result_status as result_status,
                COUNT(training_delivery_records.id) as deliveries_count,
                ROUND(COALESCE(AVG(training_delivery_records.attended_hours), 0), 2) as average_attended_hours,
                ROUND(COALESCE(AVG(training_feedback_responses.overall_score), 0), 2) as average_feedback_score
            ')
            ->groupBy('training_delivery_records.result_status')
            ->orderByDesc('deliveries_count');

        $this->applySessionPeriod($query, $year, $quarter, 'training_sessions.scheduled_start_at');

        return $query->get();
    }

    private function sessionFactQuery(): Builder
    {
        $participantAgg = DB::table('training_session_participants')
            ->selectRaw('
                training_session_id,
                COUNT(*) as participant_count,
                SUM(CASE WHEN attendance_status = "attended" THEN 1 ELSE 0 END) as attended_count
            ')
            ->groupBy('training_session_id');

        $deliveryAgg = DB::table('training_delivery_records')
            ->selectRaw('
                training_session_id,
                COUNT(*) as delivery_records_count,
                COALESCE(SUM(attended_hours), 0) as total_hours
            ')
            ->groupBy('training_session_id');

        $feedbackAgg = DB::table('training_feedback_responses')
            ->selectRaw('
                training_session_id,
                ROUND(COALESCE(AVG(overall_score), 0), 2) as average_feedback_score
            ')
            ->groupBy('training_session_id');

        return TrainingSession::query()
            ->leftJoin('training_programs', 'training_programs.id', '=', 'training_sessions.training_program_id')
            ->leftJoinSub($participantAgg, 'session_participants', fn ($join) => $join->on('session_participants.training_session_id', '=', 'training_sessions.id'))
            ->leftJoinSub($deliveryAgg, 'session_delivery', fn ($join) => $join->on('session_delivery.training_session_id', '=', 'training_sessions.id'))
            ->leftJoinSub($feedbackAgg, 'session_feedback', fn ($join) => $join->on('session_feedback.training_session_id', '=', 'training_sessions.id'));
    }

    private function applySessionPeriod(Builder|\Illuminate\Database\Query\Builder $query, ?int $year = null, ?int $quarter = null, string $column = 'training_sessions.scheduled_start_at'): Builder|\Illuminate\Database\Query\Builder
    {
        if ($year) {
            $query->whereRaw($this->yearExpression($column).' = ?', [$year]);
        }

        if ($quarter) {
            $query->whereRaw($this->quarterExpression($column).' = ?', [$quarter]);
        }

        return $query;
    }

    private function yearExpression(string $column): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return "CAST(strftime('%Y', {$column}) as integer)";
        }

        return "YEAR({$column})";
    }

    private function quarterExpression(string $column): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return "CAST(((CAST(strftime('%m', {$column}) as integer) - 1) / 3) + 1 as integer)";
        }

        return "QUARTER({$column})";
    }
}
