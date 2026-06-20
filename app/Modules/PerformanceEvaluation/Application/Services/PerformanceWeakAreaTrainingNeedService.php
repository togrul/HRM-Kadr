<?php

namespace App\Modules\PerformanceEvaluation\Application\Services;

use App\Models\PerformanceForm;
use App\Models\PerformanceFormScore;
use App\Models\PerformanceTrainingNeedLink;
use App\Models\RoleCompetencyRequirement;
use App\Models\TrainingNeedItem;
use App\Models\TrainingProgramCompetency;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PerformanceWeakAreaTrainingNeedService
{
    public function syncForForm(PerformanceForm $form): Collection
    {
        $this->refreshFormResult($form);

        $form->loadMissing([
            'scores.item:id,training_competency_id,low_score_threshold,name',
            'scores.form.personnel:id,position_id',
        ]);

        $links = collect();

        foreach ($form->scores as $score) {
            $links->put($score->id, $this->syncForScore($score));
        }

        $this->syncExistingNeedPrioritiesForForm($form->fresh(['scores']));

        return $links;
    }

    public function syncForScore(PerformanceFormScore $score): ?PerformanceTrainingNeedLink
    {
        $score->loadMissing([
            'form.personnel:id,position_id',
            'item:id,training_competency_id,low_score_threshold,name',
        ]);

        $competencyId = $score->item?->training_competency_id;
        if (empty($competencyId)) {
            $this->deleteExistingLink($score);

            return null;
        }

        $threshold = (float) ($score->item?->low_score_threshold ?? 60);
        if ((float) $score->score >= $threshold) {
            $this->deleteExistingLink($score);

            return null;
        }

        return DB::transaction(function () use ($score, $competencyId, $threshold): PerformanceTrainingNeedLink {
            $form = $score->form;
            $personnel = $form->personnel;

            $requirement = RoleCompetencyRequirement::query()
                ->where('position_id', $personnel->position_id)
                ->where('training_competency_id', $competencyId)
                ->first();

            $recommendedProgramId = TrainingProgramCompetency::query()
                ->where('training_competency_id', $competencyId)
                ->orderByDesc('target_level_id')
                ->value('training_program_id');

            $priority = ($form->final_category === 'weak' || (float) $score->score <= max(40, $threshold - 20))
                ? 'high'
                : 'medium';

            $link = PerformanceTrainingNeedLink::query()->with('trainingNeed')->firstWhere('performance_form_score_id', $score->id);

            $need = $link?->trainingNeed;
            if ($need === null) {
                $need = new TrainingNeedItem();
            }

            $need->fill([
                'personnel_id' => $personnel->id,
                'training_competency_id' => $competencyId,
                'position_id' => $personnel->position_id,
                'recommended_program_id' => $recommendedProgramId,
                'target_level_id' => $requirement?->required_level_id,
                'priority' => $priority,
                'source' => 'performance_gap',
                'status' => 'draft',
                'reason' => __('performance_evaluation::dashboard.messages.performance_gap_reason', [
                    'form' => $form->id,
                    'item' => $score->performance_form_template_item_id,
                    'score' => (string) $score->score,
                ]),
                'plan_note' => __('performance_evaluation::dashboard.messages.auto_created_weak_area_note'),
            ]);
            $need->save();

            return PerformanceTrainingNeedLink::query()->updateOrCreate(
                ['performance_form_score_id' => $score->id],
                [
                    'performance_form_id' => $form->id,
                    'training_need_item_id' => $need->id,
                    'training_competency_id' => $competencyId,
                    'source' => 'low_score',
                ]
            );
        });
    }

    public function refreshFormResult(PerformanceForm $form): void
    {
        $scores = $form->scores()->with('item:id,weight_percent')->get();
        if ($scores->isEmpty()) {
            return;
        }

        $weightedTotal = 0.0;
        $weightSum = 0.0;

        foreach ($scores as $score) {
            $weight = (float) ($score->item?->weight_percent ?? 0);
            $effectiveWeight = $weight > 0 ? $weight : 1;
            $weightedTotal += ((float) $score->score) * $effectiveWeight;
            $weightSum += $effectiveWeight;
        }

        $finalScore = $weightSum > 0 ? round($weightedTotal / $weightSum, 2) : null;
        $category = match (true) {
            $finalScore === null => null,
            $finalScore >= 85 => 'high',
            $finalScore >= 60 => 'medium',
            default => 'weak',
        };

        $form->update([
            'final_score' => $finalScore,
            'final_category' => $category,
            'result_status' => $scores->count() > 0 ? 'in_progress' : 'draft',
        ]);
    }

    private function deleteExistingLink(PerformanceFormScore $score): void
    {
        $link = PerformanceTrainingNeedLink::query()
            ->with('trainingNeed')
            ->firstWhere('performance_form_score_id', $score->id);

        if ($link === null) {
            return;
        }

        DB::transaction(function () use ($link): void {
            $trainingNeed = $link->trainingNeed;
            $link->delete();

            if ($trainingNeed !== null && $trainingNeed->source === 'performance_gap') {
                $trainingNeed->delete();
            }
        });
    }

    private function syncExistingNeedPrioritiesForForm(PerformanceForm $form): void
    {
        $basePriority = match ((string) $form->final_category) {
            'weak' => 'high',
            'medium' => 'medium',
            'high' => 'low',
            default => null,
        };

        if ($basePriority === null) {
            return;
        }

        PerformanceTrainingNeedLink::query()
            ->with('trainingNeed')
            ->where('performance_form_id', $form->id)
            ->get()
            ->each(function (PerformanceTrainingNeedLink $link) use ($basePriority): void {
                $need = $link->trainingNeed;
                if ($need === null || $need->source !== 'performance_gap') {
                    return;
                }

                if ($need->priority !== $basePriority) {
                    $need->update(['priority' => $basePriority]);
                }
            });
    }
}
