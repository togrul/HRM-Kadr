<?php

namespace App\Modules\TrainingNeeds\Application\Services;

use App\Models\RoleCompetencyRequirement;
use App\Models\TrainingAnnualPlan;
use App\Models\TrainingDeliveryRecord;
use App\Models\TrainingFeedbackResponse;
use App\Models\TrainingNeedItem;
use App\Models\TrainingPlanItem;
use App\Models\TrainingSession;
use Illuminate\Support\Facades\DB;

class TrainingNeedAnalyticsService
{
    public function summary(): array
    {
        $row = DB::selectOne(
            'select
                (select count(*) from training_need_items) as total_needs,
                (select count(*) from training_need_items where status in (?, ?)) as approved_needs,
                (select count(*) from training_need_items where recommended_program_id is not null) as mapped_needs,
                (select coalesce(sum(need_count), 0) from training_plan_items) as planned_needs,
                (select count(*) from role_competency_requirements) as requirements,
                (select count(distinct training_competency_id) from training_need_items where training_competency_id is not null) as covered_competencies',
            ['approved', 'planned'],
        );

        return [
            'total_needs' => (int) ($row->total_needs ?? 0),
            'approved_needs' => (int) ($row->approved_needs ?? 0),
            'mapped_needs' => (int) ($row->mapped_needs ?? 0),
            'planned_needs' => (int) ($row->planned_needs ?? 0),
            'coverage_ratio' => (int) ($row->approved_needs ?? 0) > 0 ? round((((int) ($row->planned_needs ?? 0)) / ((int) ($row->approved_needs ?? 0))) * 100, 2) : 0,
            'mapping_ratio' => (int) ($row->total_needs ?? 0) > 0 ? round((((int) ($row->mapped_needs ?? 0)) / ((int) ($row->total_needs ?? 0))) * 100, 2) : 0,
            'requirement_coverage_ratio' => (int) ($row->requirements ?? 0) > 0
                ? round((((int) ($row->covered_competencies ?? 0)) / ((int) $row->requirements)) * 100, 2)
                : 0,
        ];
    }

    public function sourceMix(): array
    {
        return TrainingNeedItem::query()
            ->selectRaw('COALESCE(source, "manual") as source, COUNT(*) as total')
            ->groupBy('source')
            ->orderByDesc('total')
            ->pluck('total', 'source')
            ->all();
    }

    public function priorityMix(): array
    {
        return TrainingNeedItem::query()
            ->selectRaw('priority, COUNT(*) as total')
            ->groupBy('priority')
            ->orderByDesc('total')
            ->pluck('total', 'priority')
            ->all();
    }

    public function topGapPositions(): array
    {
        return TrainingNeedItem::query()
            ->leftJoin('positions', 'positions.id', '=', 'training_need_items.position_id')
            ->selectRaw('COALESCE(positions.name, ?) as position_name, COUNT(*) as total', ['Unknown'])
            ->groupBy('position_name')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->pluck('total', 'position_name')
            ->all();
    }

    public function recentPlans()
    {
        return TrainingAnnualPlan::query()
            ->withCount('items')
            ->latest('id')
            ->limit(6)
            ->get();
    }

    public function deliverySummary(): array
    {
        $row = DB::selectOne(
            'select
                (select count(*) from training_sessions where status in (?, ?, ?)) as scheduled_sessions,
                (select count(*) from training_sessions where status = ?) as completed_sessions,
                (select count(*) from training_session_participants) as participants,
                (select count(*) from training_session_participants where attendance_status = ?) as attended_participants,
                (select count(*) from training_delivery_records) as delivery_records,
                (select avg(overall_score) from training_feedback_responses) as average_feedback',
            ['draft', 'scheduled', 'in_progress', 'completed', 'attended'],
        );

        return [
            'scheduled_sessions' => (int) ($row->scheduled_sessions ?? 0),
            'completed_sessions' => (int) ($row->completed_sessions ?? 0),
            'participants' => (int) ($row->participants ?? 0),
            'attended_participants' => (int) ($row->attended_participants ?? 0),
            'delivery_records' => (int) ($row->delivery_records ?? 0),
            'average_feedback' => round((float) ($row->average_feedback ?? 0), 2),
        ];
    }
}
