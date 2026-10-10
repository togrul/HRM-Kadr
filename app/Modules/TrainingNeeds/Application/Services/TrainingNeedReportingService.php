<?php

namespace App\Modules\TrainingNeeds\Application\Services;

use App\Contracts\VisibleAuditActivities;
use App\Models\TrainingDeliveryRecord;
use App\Models\TrainingFeedbackResponse;
use App\Models\TrainingSession;
use App\Modules\TrainingNeeds\Support\TrainingStructureScope;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;

class TrainingNeedReportingService
{
    /** @return array<string, int> */
    public function foundationStats(): array
    {
        $tables = [
            'groups' => 'training_competency_groups', 'levels' => 'training_levels',
            'competencies' => 'training_competencies', 'programs' => 'training_programs',
            'program_maps' => 'training_program_competency_map', 'requirements' => 'role_competency_requirements',
            'profiles' => 'employee_competency_profiles', 'needs' => 'training_need_items',
            'plans' => 'training_annual_plans', 'plan_items' => 'training_plan_items',
            'sessions' => 'training_sessions', 'deliveries' => 'training_delivery_records', 'feedback_forms' => 'training_feedback_forms',
        ];
        $query = DB::query();
        foreach ($tables as $key => $table) {
            $count = DB::table($table)->selectRaw('count(*)');
            if (in_array($key, ['profiles', 'needs', 'deliveries'], true)) {
                TrainingStructureScope::onPersonnelColumn($count, $table.'.personnel_id');
            }
            $query->selectSub($count, $key);
        }

        return array_map('intval', (array) $query->first());
    }

    public function deliverySummaryRows(): Collection
    {
        return TrainingSession::query()
            ->leftJoin('training_programs', 'training_programs.id', '=', 'training_sessions.training_program_id')
            ->leftJoinSub(TrainingStructureScope::onPersonnelColumn(DB::table('training_session_participants'), 'training_session_participants.personnel_id'), 'training_session_participants', fn ($join) => $join->on('training_session_participants.training_session_id', '=', 'training_sessions.id'))
            ->leftJoinSub(TrainingStructureScope::onPersonnelColumn(DB::table('training_delivery_records'), 'training_delivery_records.personnel_id'), 'training_delivery_records', fn ($join) => $join->on('training_delivery_records.training_session_id', '=', 'training_sessions.id'))
            ->leftJoinSub(TrainingStructureScope::onPersonnelColumn(DB::table('training_feedback_responses'), 'training_feedback_responses.personnel_id'), 'training_feedback_responses', fn ($join) => $join->on('training_feedback_responses.training_session_id', '=', 'training_sessions.id'))
            ->selectRaw('
                training_sessions.id,
                training_sessions.title,
                training_sessions.status,
                training_sessions.scheduled_start_at,
                training_programs.title as program_title,
                COUNT(DISTINCT training_session_participants.id) as participant_count,
                COUNT(DISTINCT CASE WHEN training_session_participants.attendance_status = "attended" THEN training_session_participants.id END) as attended_count,
                COUNT(DISTINCT training_delivery_records.id) as delivery_records_count,
                ROUND(COALESCE(AVG(training_feedback_responses.overall_score), 0), 2) as average_feedback_score
            ')
            ->groupBy(
                'training_sessions.id',
                'training_sessions.title',
                'training_sessions.status',
                'training_sessions.scheduled_start_at',
                'training_programs.title'
            )
            ->orderByDesc('training_sessions.scheduled_start_at')
            ->get();
    }

    public function deliveryPivotRows(): Collection
    {
        return TrainingSession::query()
            ->leftJoin('training_programs', 'training_programs.id', '=', 'training_sessions.training_program_id')
            ->leftJoinSub(TrainingStructureScope::onPersonnelColumn(DB::table('training_session_participants'), 'training_session_participants.personnel_id'), 'training_session_participants', fn ($join) => $join->on('training_session_participants.training_session_id', '=', 'training_sessions.id'))
            ->leftJoinSub(TrainingStructureScope::onPersonnelColumn(DB::table('training_delivery_records'), 'training_delivery_records.personnel_id'), 'training_delivery_records', fn ($join) => $join->on('training_delivery_records.training_session_id', '=', 'training_sessions.id'))
            ->leftJoinSub(TrainingStructureScope::onPersonnelColumn(DB::table('training_feedback_responses'), 'training_feedback_responses.personnel_id'), 'training_feedback_responses', fn ($join) => $join->on('training_feedback_responses.training_session_id', '=', 'training_sessions.id'))
            ->selectRaw('
                COALESCE(training_programs.title, "-") as program_title,
                COALESCE(training_programs.delivery_type, "internal") as delivery_type,
                COUNT(DISTINCT training_sessions.id) as sessions_count,
                COUNT(DISTINCT CASE WHEN training_session_participants.attendance_status = "attended" THEN training_session_participants.id END) as attended_count,
                COUNT(DISTINCT training_delivery_records.id) as delivery_records_count,
                COUNT(DISTINCT CASE WHEN training_delivery_records.certificate_path IS NOT NULL THEN training_delivery_records.id END) as certificates_uploaded,
                ROUND(COALESCE(AVG(training_feedback_responses.overall_score), 0), 2) as average_feedback_score
            ')
            ->groupBy('training_programs.title', 'training_programs.delivery_type')
            ->orderBy('training_programs.title')
            ->get();
    }

    public function deliveryRows(): Collection
    {
        // Eksport sətirləri baxan istifadəçinin struktur görünürlüyü ilə məhdudlaşır.
        return TrainingStructureScope::onPersonnelColumn(TrainingDeliveryRecord::query(), 'training_delivery_records.personnel_id')
            ->with([
                'session:id,title,scheduled_start_at,location',
                'program:id,title',
                'personnel:id,surname,name,patronymic,tabel_no',
                'competency:id,name',
            ])
            ->latest('completed_at')
            ->get();
    }

    public function feedbackRows(): Collection
    {
        return TrainingStructureScope::onPersonnelColumn(TrainingFeedbackResponse::query(), 'training_feedback_responses.personnel_id')
            ->with([
                'form:id,title',
                'session:id,title',
                'personnel:id,surname,name,patronymic,tabel_no',
            ])
            ->latest('submitted_at')
            ->get();
    }

    public function feedbackSessionSummaries(): Collection
    {
        return TrainingSession::query()
            ->leftJoin('training_feedback_forms', 'training_feedback_forms.training_session_id', '=', 'training_sessions.id')
            ->leftJoinSub(TrainingStructureScope::onPersonnelColumn(DB::table('training_feedback_responses'), 'training_feedback_responses.personnel_id'), 'training_feedback_responses', fn ($join) => $join->on('training_feedback_responses.training_session_id', '=', 'training_sessions.id'))
            ->selectRaw('
                training_sessions.id,
                training_sessions.title,
                training_sessions.status,
                training_sessions.scheduled_start_at,
                COUNT(DISTINCT training_feedback_forms.id) as feedback_forms_count,
                COUNT(training_feedback_responses.id) as feedback_responses_count,
                ROUND(COALESCE(AVG(training_feedback_responses.overall_score), 0), 2) as average_feedback_score
            ')
            ->groupBy(
                'training_sessions.id',
                'training_sessions.title',
                'training_sessions.status',
                'training_sessions.scheduled_start_at'
            )
            ->orderByDesc('training_sessions.scheduled_start_at')
            ->limit(6)
            ->get();
    }

    public function upcomingSessions(): Collection
    {
        return TrainingSession::query()
            ->with(['program:id,title', 'participants' => fn (Relation $relation) => TrainingStructureScope::onPersonnelColumn($relation->getQuery(), 'training_session_participants.personnel_id')])
            ->whereIn('status', ['draft', 'scheduled', 'in_progress'])
            ->whereNotNull('scheduled_start_at')
            ->orderBy('scheduled_start_at')
            ->limit(8)
            ->get();
    }

    public function auditRows(): Collection
    {
        return app(VisibleAuditActivities::class)->visibleQuery()
            ->whereIn('subject_type', [
                \App\Models\TrainingAnnualPlan::class,
                \App\Models\TrainingPlanItem::class,
                \App\Models\TrainingSession::class,
                \App\Models\TrainingDeliveryRecord::class,
                \App\Models\TrainingNeedItem::class,
            ])
            ->with('causer:id,name,email')
            ->latest('id')
            ->limit(500)
            ->get();
    }
}
