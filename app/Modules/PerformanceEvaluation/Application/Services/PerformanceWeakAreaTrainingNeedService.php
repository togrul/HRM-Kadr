<?php

namespace App\Modules\PerformanceEvaluation\Application\Services;

use App\Models\PerformanceForm;
use App\Models\PerformanceFormScore;
use App\Models\PerformanceScorecard;
use App\Models\PerformanceTrainingNeedLink;
use App\Models\RoleCompetencyRequirement;
use App\Models\TrainingNeedItem;
use App\Models\TrainingProgramCompetency;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\ScorecardService;
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
                $need = new TrainingNeedItem;
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
        $scores = $form->scores()
            ->with('item:id,weight_percent,performance_form_template_section_id', 'item.section:id,weight_percent')
            ->get();
        if ($scores->isEmpty()) {
            return;
        }

        // Self ratings are for comparison only; per item the manager's rating wins over HR's.
        $rated = $scores
            ->where('evaluator_type', '!=', 'self')
            ->filter(fn (PerformanceFormScore $score): bool => $score->item !== null)
            ->sortBy(fn (PerformanceFormScore $score): int => $score->evaluator_type === 'manager' ? 0 : 1)
            ->unique('performance_form_template_item_id');

        $finalScore = $this->weightedScore($rated);
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

        // A form serving as a KPI card's competency block feeds the card's final score.
        PerformanceScorecard::query()
            ->where('performance_form_id', $form->id)
            ->where('status', '!=', 'closed')
            ->get()
            ->each(fn (PerformanceScorecard $card) => app(ScorecardService::class)->recalculate($card));
    }

    /**
     * Item-weighted score; when sections carry weights, each section is averaged
     * on its own and the sections are then combined by their weights.
     *
     * @param  Collection<int, PerformanceFormScore>  $scores
     */
    private function weightedScore(Collection $scores): ?float
    {
        if ($scores->isEmpty()) {
            return null;
        }

        $itemAverage = fn (Collection $group): float => $this->weightedAverage(
            $group->map(fn (PerformanceFormScore $score): array => [(float) $score->score, (float) $score->item->weight_percent])
        );

        $sections = $scores->groupBy(fn (PerformanceFormScore $score): int => (int) $score->item->performance_form_template_section_id);
        $hasSectionWeights = $scores->contains(fn (PerformanceFormScore $score): bool => (float) $score->item->section?->weight_percent > 0);

        if (! $hasSectionWeights) {
            return round($itemAverage($scores), 2);
        }

        return round($this->weightedAverage(
            $sections->map(fn (Collection $group): array => [$itemAverage($group), (float) $group->first()->item->section?->weight_percent])
        ), 2);
    }

    /**
     * Weighted mean of [value, weight] pairs; a plain mean when no pair carries a weight.
     *
     * @param  Collection<int, array{0: float, 1: float}>  $pairs
     */
    private function weightedAverage(Collection $pairs): float
    {
        $weightSum = $pairs->sum(fn (array $pair): float => $pair[1]);

        if ($weightSum <= 0) {
            return (float) $pairs->avg(fn (array $pair): float => $pair[0]);
        }

        return $pairs->sum(fn (array $pair): float => $pair[0] * $pair[1]) / $weightSum;
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
