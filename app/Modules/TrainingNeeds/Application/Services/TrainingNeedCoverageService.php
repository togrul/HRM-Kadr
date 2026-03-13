<?php

namespace App\Modules\TrainingNeeds\Application\Services;

use App\Models\TrainingNeedItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TrainingNeedCoverageService
{
    public function summary(?int $year = null, ?int $quarter = null): array
    {
        $totalNeeds = (clone $this->needPeriodQuery($year, $quarter))->count();
        $approvedNeeds = (clone $this->needPeriodQuery($year, $quarter))
            ->whereIn('status', ['approved', 'planned'])
            ->count();
        $plannedNeeds = (int) (DB::table('training_plan_items')
            ->when($year, fn ($query) => $query->whereExists(function ($sub) use ($year, $quarter): void {
                $sub->selectRaw('1')
                    ->from('training_annual_plans')
                    ->whereColumn('training_annual_plans.id', 'training_plan_items.training_annual_plan_id')
                    ->where('training_annual_plans.plan_year', $year)
                    ->when($quarter, fn ($planQuery) => $planQuery->where('training_annual_plans.plan_quarter', $quarter));
            }))
            ->sum('need_count'));
        $sessionLinkedNeeds = (int) DB::table('training_session_participants')
            ->join('training_sessions', 'training_sessions.id', '=', 'training_session_participants.training_session_id')
            ->when($year, fn ($query) => $query->whereRaw($this->yearExpression('training_sessions.scheduled_start_at').' = ?', [$year]))
            ->when($quarter, fn ($query) => $query->whereRaw($this->quarterExpression('training_sessions.scheduled_start_at').' = ?', [$quarter]))
            ->whereNotNull('training_session_participants.training_need_item_id')
            ->distinct('training_session_participants.training_need_item_id')
            ->count('training_session_participants.training_need_item_id');
        $completedNeeds = (int) DB::table('training_delivery_records')
            ->join('training_sessions', 'training_sessions.id', '=', 'training_delivery_records.training_session_id')
            ->when($year, fn ($query) => $query->whereRaw($this->yearExpression('training_sessions.scheduled_start_at').' = ?', [$year]))
            ->when($quarter, fn ($query) => $query->whereRaw($this->quarterExpression('training_sessions.scheduled_start_at').' = ?', [$quarter]))
            ->whereNotNull('training_delivery_records.training_need_item_id')
            ->distinct('training_delivery_records.training_need_item_id')
            ->count('training_delivery_records.training_need_item_id');

        return [
            'total_needs' => $totalNeeds,
            'approved_needs' => $approvedNeeds,
            'planned_needs' => $plannedNeeds,
            'session_linked_needs' => $sessionLinkedNeeds,
            'completed_needs' => $completedNeeds,
            'open_needs' => max($totalNeeds - $completedNeeds, 0),
            'planning_coverage_ratio' => $approvedNeeds > 0 ? round(($plannedNeeds / $approvedNeeds) * 100, 2) : 0.0,
            'delivery_coverage_ratio' => $totalNeeds > 0 ? round(($completedNeeds / $totalNeeds) * 100, 2) : 0.0,
        ];
    }

    public function competencyRows(?int $year = null, ?int $quarter = null, int $limit = 10): Collection
    {
        $needAgg = $this->needPeriodQuery($year, $quarter)
            ->selectRaw('training_competency_id, COUNT(*) as total_needs')
            ->whereNotNull('training_competency_id')
            ->groupBy('training_competency_id');

        $plannedAgg = DB::table('training_plan_items')
            ->selectRaw('training_competency_id, COALESCE(SUM(need_count), 0) as planned_needs')
            ->whereNotNull('training_competency_id')
            ->when($year, fn ($query) => $query->whereExists(function ($sub) use ($year, $quarter): void {
                $sub->selectRaw('1')
                    ->from('training_annual_plans')
                    ->whereColumn('training_annual_plans.id', 'training_plan_items.training_annual_plan_id')
                    ->where('training_annual_plans.plan_year', $year)
                    ->when($quarter, fn ($planQuery) => $planQuery->where('training_annual_plans.plan_quarter', $quarter));
            }))
            ->groupBy('training_competency_id');

        $deliveredAgg = DB::table('training_delivery_records')
            ->join('training_sessions', 'training_sessions.id', '=', 'training_delivery_records.training_session_id')
            ->selectRaw('training_delivery_records.training_competency_id, COUNT(*) as delivered_records')
            ->whereNotNull('training_delivery_records.training_competency_id')
            ->when($year, fn ($query) => $query->whereRaw($this->yearExpression('training_sessions.scheduled_start_at').' = ?', [$year]))
            ->when($quarter, fn ($query) => $query->whereRaw($this->quarterExpression('training_sessions.scheduled_start_at').' = ?', [$quarter]))
            ->groupBy('training_delivery_records.training_competency_id');

        return DB::table('training_competencies')
            ->leftJoinSub($needAgg, 'need_agg', fn ($join) => $join->on('need_agg.training_competency_id', '=', 'training_competencies.id'))
            ->leftJoinSub($plannedAgg, 'plan_agg', fn ($join) => $join->on('plan_agg.training_competency_id', '=', 'training_competencies.id'))
            ->leftJoinSub($deliveredAgg, 'delivery_agg', fn ($join) => $join->on('delivery_agg.training_competency_id', '=', 'training_competencies.id'))
            ->selectRaw('
                training_competencies.id,
                training_competencies.name,
                COALESCE(need_agg.total_needs, 0) as total_needs,
                COALESCE(plan_agg.planned_needs, 0) as planned_needs,
                COALESCE(delivery_agg.delivered_records, 0) as delivered_records
            ')
            ->whereRaw('COALESCE(need_agg.total_needs, 0) > 0 OR COALESCE(plan_agg.planned_needs, 0) > 0 OR COALESCE(delivery_agg.delivered_records, 0) > 0')
            ->orderByDesc('total_needs')
            ->orderBy('training_competencies.name')
            ->limit($limit)
            ->get();
    }

    public function programRows(?int $year = null, ?int $quarter = null, int $limit = 10): Collection
    {
        $recommendedAgg = $this->needPeriodQuery($year, $quarter)
            ->selectRaw('recommended_program_id, COUNT(*) as recommended_needs')
            ->whereNotNull('recommended_program_id')
            ->groupBy('recommended_program_id');

        $sessionAgg = DB::table('training_sessions')
            ->selectRaw('training_program_id, COUNT(*) as sessions_count')
            ->whereNotNull('training_program_id')
            ->when($year, fn ($query) => $query->whereRaw($this->yearExpression('scheduled_start_at').' = ?', [$year]))
            ->when($quarter, fn ($query) => $query->whereRaw($this->quarterExpression('scheduled_start_at').' = ?', [$quarter]))
            ->groupBy('training_program_id');

        $deliveryAgg = DB::table('training_delivery_records')
            ->join('training_sessions', 'training_sessions.id', '=', 'training_delivery_records.training_session_id')
            ->selectRaw('training_delivery_records.training_program_id, COUNT(*) as delivered_records')
            ->whereNotNull('training_delivery_records.training_program_id')
            ->when($year, fn ($query) => $query->whereRaw($this->yearExpression('training_sessions.scheduled_start_at').' = ?', [$year]))
            ->when($quarter, fn ($query) => $query->whereRaw($this->quarterExpression('training_sessions.scheduled_start_at').' = ?', [$quarter]))
            ->groupBy('training_delivery_records.training_program_id');

        return DB::table('training_programs')
            ->leftJoinSub($recommendedAgg, 'need_agg', fn ($join) => $join->on('need_agg.recommended_program_id', '=', 'training_programs.id'))
            ->leftJoinSub($sessionAgg, 'session_agg', fn ($join) => $join->on('session_agg.training_program_id', '=', 'training_programs.id'))
            ->leftJoinSub($deliveryAgg, 'delivery_agg', fn ($join) => $join->on('delivery_agg.training_program_id', '=', 'training_programs.id'))
            ->selectRaw('
                training_programs.id,
                training_programs.title,
                training_programs.delivery_type,
                COALESCE(need_agg.recommended_needs, 0) as recommended_needs,
                COALESCE(session_agg.sessions_count, 0) as sessions_count,
                COALESCE(delivery_agg.delivered_records, 0) as delivered_records
            ')
            ->whereRaw('COALESCE(need_agg.recommended_needs, 0) > 0 OR COALESCE(session_agg.sessions_count, 0) > 0 OR COALESCE(delivery_agg.delivered_records, 0) > 0')
            ->orderByDesc('recommended_needs')
            ->orderBy('training_programs.title')
            ->limit($limit)
            ->get();
    }

    private function needPeriodQuery(?int $year = null, ?int $quarter = null): Builder
    {
        return TrainingNeedItem::query()
            ->when($year, fn ($query) => $query->whereRaw($this->yearExpression('COALESCE(target_completion_date, created_at)').' = ?', [$year]))
            ->when($quarter, fn ($query) => $query->whereRaw($this->quarterExpression('COALESCE(target_completion_date, created_at)').' = ?', [$quarter]));
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
