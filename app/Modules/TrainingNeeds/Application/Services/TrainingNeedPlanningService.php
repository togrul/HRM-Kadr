<?php

namespace App\Modules\TrainingNeeds\Application\Services;

use App\Models\TrainingAnnualPlan;
use App\Models\TrainingPlanItem;
use Illuminate\Support\Facades\DB;

class TrainingNeedPlanningService
{
    public function generatePlanItems(TrainingAnnualPlan $plan): void
    {
        DB::transaction(function () use ($plan): void {
            $plan->items()->delete();

            $suggestions = app(TrainingNeedSuggestionService::class)->suggestions(
                planYear: (int) $plan->plan_year,
                planQuarter: $plan->plan_quarter ? (int) $plan->plan_quarter : null,
            );

            $plannedParticipants = 0;
            $coveredNeedCount = 0;
            $estimatedBudget = 0.0;

            foreach ($suggestions as $suggestion) {
                TrainingPlanItem::query()->create([
                    'training_annual_plan_id' => $plan->id,
                    'training_competency_id' => $suggestion['training_competency_id'],
                    'training_program_id' => $suggestion['training_program_id'],
                    'position_id' => $suggestion['position_id'],
                    'target_level_id' => $suggestion['target_level_id'],
                    'priority' => $suggestion['priority'],
                    'participant_count' => $suggestion['participant_count'],
                    'need_count' => $suggestion['need_count'],
                    'estimated_budget' => $suggestion['estimated_budget'],
                    'source_mix' => $suggestion['source_mix'],
                    'review_status' => 'suggested',
                    'suggested_score' => $suggestion['suggested_score'],
                    'review_note' => null,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                ]);

                $plannedParticipants += (int) $suggestion['participant_count'];
                $coveredNeedCount += (int) $suggestion['need_count'];
                $estimatedBudget += (float) $suggestion['estimated_budget'];
            }

            $plan->update([
                'planned_participants' => $plannedParticipants,
                'covered_need_count' => $coveredNeedCount,
                'estimated_budget' => round($estimatedBudget, 2),
            ]);

            $this->syncPlanStatus($plan->fresh());
        });
    }

    public function syncPlanStatus(TrainingAnnualPlan $plan): void
    {
        $itemCount = (int) $plan->items()->count();
        $approvedCount = (int) $plan->items()->where('review_status', 'approved')->count();

        if ($itemCount === 0 && $plan->status === 'review') {
            $plan->update(['status' => 'draft']);

            return;
        }

        if ($itemCount > 0 && $approvedCount === $itemCount && in_array($plan->status, ['draft', 'review', 'approved'], true)) {
            $plan->update(['status' => 'approved']);

            return;
        }

        if ($itemCount > 0 && $approvedCount < $itemCount && in_array($plan->status, ['approved', 'review', 'draft'], true)) {
            $plan->update(['status' => 'review']);

            return;
        }

        if ($itemCount > 0 && $plan->status === 'draft') {
            $plan->update(['status' => 'review']);
        }
    }
}
