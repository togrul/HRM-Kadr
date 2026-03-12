<?php

namespace App\Modules\TrainingNeeds\Application\Services;

use App\Models\EmployeeCompetencyProfile;
use App\Models\RoleCompetencyRequirement;
use App\Models\TrainingNeedItem;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class TrainingNeedSuggestionService
{
    public function suggestions(int $planYear, ?int $planQuarter = null, ?int $limit = null): Collection
    {
        $config = (array) config('training_needs.suggestion', []);
        $sourceWeights = (array) ($config['source_weights'] ?? []);
        $priorityWeights = (array) ($config['priority_weights'] ?? []);
        $rolePriorityBonus = (array) ($config['role_priority_bonus'] ?? []);
        $dueDateBonus = (array) ($config['due_date_bonus'] ?? []);

        $needs = TrainingNeedItem::query()
            ->with([
                'personnel:id,surname,name,patronymic,tabel_no',
                'competency:id,name',
                'position:id,name',
                'recommendedProgram:id,title,duration_hours',
                'targetLevel:id,name,score',
            ])
            ->whereIn('status', ['approved', 'planned'])
            ->get()
            ->filter(fn (TrainingNeedItem $need): bool => $this->matchesPlanWindow($need, $planYear, $planQuarter))
            ->values();

        if ($needs->isEmpty()) {
            return collect();
        }

        $competencyNeedCounts = $needs
            ->pluck('training_competency_id')
            ->filter()
            ->countBy();
        $positionNeedCounts = $needs
            ->pluck('position_id')
            ->filter()
            ->countBy();

        $requirementMap = RoleCompetencyRequirement::query()
            ->with('requiredLevel:id,name,score')
            ->whereIn('position_id', $needs->pluck('position_id')->filter()->unique()->all())
            ->whereIn('training_competency_id', $needs->pluck('training_competency_id')->filter()->unique()->all())
            ->get()
            ->keyBy(fn (RoleCompetencyRequirement $requirement): string => $requirement->position_id.':'.$requirement->training_competency_id);

        $profileMap = EmployeeCompetencyProfile::query()
            ->with('currentLevel:id,name,score')
            ->whereIn('personnel_id', $needs->pluck('personnel_id')->filter()->unique()->all())
            ->whereIn('training_competency_id', $needs->pluck('training_competency_id')->filter()->unique()->all())
            ->get()
            ->keyBy(fn (EmployeeCompetencyProfile $profile): string => $profile->personnel_id.':'.$profile->training_competency_id);

        $scoredNeeds = $needs->map(function (TrainingNeedItem $need) use (
            $requirementMap,
            $profileMap,
            $competencyNeedCounts,
            $positionNeedCounts,
            $sourceWeights,
            $priorityWeights,
            $rolePriorityBonus,
            $dueDateBonus,
            $config
        ): array {
            $requirement = $requirementMap->get($need->position_id.':'.$need->training_competency_id);
            $profile = $profileMap->get($need->personnel_id.':'.$need->training_competency_id);

            $gap = $this->resolveGap($need, $requirement, $profile);
            $scoreBreakdown = $this->scoreNeed(
                need: $need,
                requirement: $requirement,
                gap: $gap,
                competencyNeedCount: (int) ($competencyNeedCounts[$need->training_competency_id] ?? 0),
                positionNeedCount: (int) ($positionNeedCounts[$need->position_id] ?? 0),
                sourceWeights: $sourceWeights,
                priorityWeights: $priorityWeights,
                rolePriorityBonus: $rolePriorityBonus,
                dueDateBonus: $dueDateBonus,
                config: $config,
            );

            return [
                'need' => $need,
                'score' => $scoreBreakdown['score'],
                'reasons' => $scoreBreakdown['reasons'],
                'gap' => $gap,
            ];
        });

        $grouped = $scoredNeeds->groupBy(function (array $row): string {
            /** @var TrainingNeedItem $need */
            $need = $row['need'];

            return implode(':', [
                $need->training_competency_id,
                $need->recommended_program_id ?: 0,
                $need->position_id ?: 0,
                $need->target_level_id ?: 0,
                $need->priority ?: 'medium',
            ]);
        });

        $suggestions = $grouped->map(function (Collection $rows): array {
            /** @var TrainingNeedItem $firstNeed */
            $firstNeed = $rows->first()['need'];
            $participantCount = $rows->pluck('need.personnel_id')->filter()->unique()->count();
            $needCount = $rows->count();
            $hours = (float) ($firstNeed->recommendedProgram?->duration_hours ?? 8);
            $budget = $participantCount * $hours * 25;
            $averageScore = round($rows->avg('score') + min($needCount * 4, 18) + min($participantCount * 1.5, 10), 1);
            $reasons = $rows
                ->flatMap(fn (array $row): array => $row['reasons'])
                ->countBy()
                ->sortDesc()
                ->keys()
                ->take(3)
                ->values()
                ->all();

            $latestDueDate = $rows
                ->pluck('need.target_completion_date')
                ->filter()
                ->sort()
                ->first();

            return [
                'group_key' => implode('-', [
                    $firstNeed->recommended_program_id ?: 'no-program',
                    $firstNeed->training_competency_id ?: 'no-competency',
                    $firstNeed->position_id ?: 'no-position',
                    $firstNeed->target_level_id ?: 'no-level',
                    $firstNeed->priority ?: 'medium',
                ]),
                'training_program_id' => $firstNeed->recommended_program_id,
                'training_program_title' => $firstNeed->recommendedProgram?->title,
                'training_competency_id' => $firstNeed->training_competency_id,
                'training_competency_name' => $firstNeed->competency?->name,
                'position_id' => $firstNeed->position_id,
                'position_name' => $firstNeed->position?->name,
                'target_level_id' => $firstNeed->target_level_id,
                'target_level_name' => $firstNeed->targetLevel?->name,
                'priority' => $firstNeed->priority ?? 'medium',
                'participant_count' => $participantCount,
                'need_count' => $needCount,
                'estimated_budget' => round($budget, 2),
                'source_mix' => $rows->pluck('need.source')->filter()->unique()->implode(', '),
                'suggested_score' => $averageScore,
                'suggested_reasons' => $reasons,
                'latest_due_date' => $latestDueDate,
                'needs' => $rows->pluck('need')->values(),
            ];
        })
            ->sortByDesc('suggested_score')
            ->values();

        if ($limit !== null) {
            return $suggestions->take($limit)->values();
        }

        return $suggestions;
    }

    private function matchesPlanWindow(TrainingNeedItem $need, int $planYear, ?int $planQuarter): bool
    {
        $date = $need->target_completion_date ?? $need->created_at;

        if (! $date instanceof CarbonInterface) {
            return false;
        }

        if ((int) $date->format('Y') !== $planYear) {
            return false;
        }

        if ($planQuarter === null) {
            return true;
        }

        return (int) ceil(((int) $date->format('n')) / 3) === $planQuarter;
    }

    private function resolveGap(
        TrainingNeedItem $need,
        ?RoleCompetencyRequirement $requirement,
        ?EmployeeCompetencyProfile $profile,
    ): int {
        $targetScore = (int) ($need->targetLevel?->score ?? 0);
        $requiredScore = (int) ($requirement?->requiredLevel?->score ?? 0);
        $currentScore = (int) ($profile?->currentLevel?->score ?? 0);

        $expectedScore = max($targetScore, $requiredScore);

        return max($expectedScore - $currentScore, 0);
    }

    private function scoreNeed(
        TrainingNeedItem $need,
        ?RoleCompetencyRequirement $requirement,
        int $gap,
        int $competencyNeedCount,
        int $positionNeedCount,
        array $sourceWeights,
        array $priorityWeights,
        array $rolePriorityBonus,
        array $dueDateBonus,
        array $config,
    ): array {
        $score = 0;
        $reasons = [];

        $score += (int) ($sourceWeights[$need->source] ?? $sourceWeights['default'] ?? 10);

        $score += (int) ($priorityWeights[$need->priority] ?? $priorityWeights['low'] ?? 5);

        if ($gap >= 2) {
            $score += (int) (($config['gap_bonus']['high'] ?? 18));
            $reasons[] = 'high_gap';
        } elseif ($gap === 1) {
            $score += (int) (($config['gap_bonus']['medium'] ?? 8));
            $reasons[] = 'gap_exists';
        }

        if ($requirement?->is_mandatory) {
            $score += (int) ($config['mandatory_bonus'] ?? 16);
            $reasons[] = 'mandatory';
        }

        if ($requirement?->priority === 'high') {
            $score += (int) ($rolePriorityBonus['high'] ?? 12);
            $reasons[] = 'role_critical';
        } elseif ($requirement?->priority === 'medium') {
            $score += (int) ($rolePriorityBonus['medium'] ?? 6);
        }

        if ($need->recommended_program_id) {
            $score += (int) ($config['program_ready_bonus'] ?? 10);
            $reasons[] = 'program_ready';
        }

        if ($competencyNeedCount > 1) {
            $score += min(
                max($competencyNeedCount - 1, 0) * (int) ($config['repeat_competency_bonus_per_need'] ?? 3),
                (int) ($config['repeat_competency_bonus_cap'] ?? 18)
            );
            $reasons[] = 'repeat_gap';
        }

        if ($positionNeedCount > 1) {
            $score += min(
                max($positionNeedCount - 1, 0) * (int) ($config['repeat_position_bonus_per_need'] ?? 2),
                (int) ($config['repeat_position_bonus_cap'] ?? 12)
            );
            $reasons[] = 'position_cluster';
        }

        $dueDate = $need->target_completion_date;
        if ($dueDate instanceof CarbonInterface) {
            $daysUntilDue = now()->startOfDay()->diffInDays($dueDate->startOfDay(), false);

            if ($daysUntilDue < 0) {
                $score += (int) ($dueDateBonus['overdue'] ?? 20);
                $reasons[] = 'overdue';
            } elseif ($daysUntilDue <= 30) {
                $score += (int) ($dueDateBonus['near_30'] ?? 14);
                $reasons[] = 'near_due';
            } elseif ($daysUntilDue <= 90) {
                $score += (int) ($dueDateBonus['near_90'] ?? 8);
            }
        }

        if ($need->source === 'performance_gap' || $need->source === 'skill_gap') {
            $reasons[] = 'evidence_based';
        }

        return [
            'score' => $score,
            'reasons' => array_values(array_unique($reasons)),
        ];
    }
}
