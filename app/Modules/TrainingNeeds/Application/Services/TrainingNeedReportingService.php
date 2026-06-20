<?php

namespace App\Modules\TrainingNeeds\Application\Services;

use App\Models\TrainingDeliveryRecord;
use App\Models\TrainingFeedbackResponse;
use App\Models\TrainingSession;
use Spatie\Activitylog\Models\Activity;

class TrainingNeedReportingService
{
    public function deliverySummaryRows()
    {
        return TrainingSession::query()
            ->leftJoin('training_programs', 'training_programs.id', '=', 'training_sessions.training_program_id')
            ->leftJoin('training_session_participants', 'training_session_participants.training_session_id', '=', 'training_sessions.id')
            ->leftJoin('training_delivery_records', 'training_delivery_records.training_session_id', '=', 'training_sessions.id')
            ->leftJoin('training_feedback_responses', 'training_feedback_responses.training_session_id', '=', 'training_sessions.id')
            ->selectRaw('
                training_sessions.id,
                training_sessions.title,
                training_sessions.status,
                training_sessions.scheduled_start_at,
                training_programs.title as program_title,
                COUNT(DISTINCT training_session_participants.id) as participant_count,
                SUM(CASE WHEN training_session_participants.attendance_status = "attended" THEN 1 ELSE 0 END) as attended_count,
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

    public function deliveryPivotRows()
    {
        return TrainingSession::query()
            ->leftJoin('training_programs', 'training_programs.id', '=', 'training_sessions.training_program_id')
            ->leftJoin('training_session_participants', 'training_session_participants.training_session_id', '=', 'training_sessions.id')
            ->leftJoin('training_delivery_records', 'training_delivery_records.training_session_id', '=', 'training_sessions.id')
            ->leftJoin('training_feedback_responses', 'training_feedback_responses.training_session_id', '=', 'training_sessions.id')
            ->selectRaw('
                COALESCE(training_programs.title, "-") as program_title,
                COALESCE(training_programs.delivery_type, "internal") as delivery_type,
                COUNT(DISTINCT training_sessions.id) as sessions_count,
                SUM(CASE WHEN training_session_participants.attendance_status = "attended" THEN 1 ELSE 0 END) as attended_count,
                COUNT(DISTINCT training_delivery_records.id) as delivery_records_count,
                SUM(CASE WHEN training_delivery_records.certificate_path IS NOT NULL THEN 1 ELSE 0 END) as certificates_uploaded,
                ROUND(COALESCE(AVG(training_feedback_responses.overall_score), 0), 2) as average_feedback_score
            ')
            ->groupBy('training_programs.title', 'training_programs.delivery_type')
            ->orderBy('training_programs.title')
            ->get();
    }

    public function deliveryRows()
    {
        return TrainingDeliveryRecord::query()
            ->with([
                'session:id,title,scheduled_start_at,location',
                'program:id,title',
                'personnel:id,surname,name,patronymic,tabel_no',
                'competency:id,name',
            ])
            ->latest('completed_at')
            ->get();
    }

    public function feedbackRows()
    {
        return TrainingFeedbackResponse::query()
            ->with([
                'form:id,title',
                'session:id,title',
                'personnel:id,surname,name,patronymic,tabel_no',
            ])
            ->latest('submitted_at')
            ->get();
    }

    public function feedbackSessionSummaries()
    {
        return TrainingSession::query()
            ->leftJoin('training_feedback_forms', 'training_feedback_forms.training_session_id', '=', 'training_sessions.id')
            ->leftJoin('training_feedback_responses', 'training_feedback_responses.training_session_id', '=', 'training_sessions.id')
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

    public function upcomingSessions()
    {
        return TrainingSession::query()
            ->with(['program:id,title', 'participants'])
            ->whereIn('status', ['draft', 'scheduled', 'in_progress'])
            ->whereNotNull('scheduled_start_at')
            ->orderBy('scheduled_start_at')
            ->limit(8)
            ->get();
    }

    public function auditRows()
    {
        return Activity::query()
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
