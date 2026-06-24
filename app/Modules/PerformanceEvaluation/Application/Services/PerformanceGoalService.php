<?php

namespace App\Modules\PerformanceEvaluation\Application\Services;

use App\Models\PerformanceGoal;
use App\Models\PerformanceGoalCheckin;
use Illuminate\Support\Collection;

/**
 * Goals / OKR for a performance cycle: builds the aligned goal tree (objective →
 * cascaded key results / sub-goals) with weighted progress roll-up, and handles
 * goal creation + progress check-ins. A leaf's progress comes from its measurable
 * target (current/target); a parent's roll-up is the weighted average of its
 * children (by weight_percent, falling back to a simple average).
 */
class PerformanceGoalService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function tree(int $cycleId): array
    {
        $goals = PerformanceGoal::query()
            ->where('performance_cycle_id', $cycleId)
            ->with(['personnel:id,surname,name,patronymic'])
            ->orderBy('parent_goal_id')
            ->orderBy('id')
            ->get();

        $childrenByParent = $goals->groupBy(fn (PerformanceGoal $g): int => (int) ($g->parent_goal_id ?? 0));

        $build = function (PerformanceGoal $goal) use (&$build, $childrenByParent): array {
            $children = $childrenByParent->get($goal->id, collect())
                ->map(fn (PerformanceGoal $child): array => $build($child))
                ->all();

            $node = [
                'id' => $goal->id,
                'title' => $goal->title,
                'goal_type' => $goal->goal_type,
                'status' => $goal->status,
                'unit' => (string) $goal->unit,
                'target' => (float) $goal->target_value,
                'current' => (float) $goal->current_value,
                'weight' => (float) $goal->weight_percent,
                'progress_pct' => $goal->progress_pct,
                'personnel_name' => $goal->personnel
                    ? trim($goal->personnel->surname.' '.$goal->personnel->name)
                    : null,
                'due_date' => optional($goal->due_date)->format('d.m.Y'),
                'children' => $children,
            ];

            $node['rollup_pct'] = $this->rollup($node);

            return $node;
        };

        return $childrenByParent->get(0, collect())
            ->map(fn (PerformanceGoal $goal): array => $build($goal))
            ->all();
    }

    /**
     * Weighted roll-up of a node: a leaf uses its own progress; a parent uses the
     * weighted average of its children (weight_percent), or a plain average if the
     * children carry no weights.
     *
     * @param  array<string, mixed>  $node
     */
    public function rollup(array $node): int
    {
        $children = $node['children'] ?? [];
        if ($children === []) {
            return (int) $node['progress_pct'];
        }

        $totalWeight = array_sum(array_column($children, 'weight'));
        if ($totalWeight > 0) {
            $weighted = 0.0;
            foreach ($children as $child) {
                $weighted += $child['rollup_pct'] * $child['weight'];
            }

            return (int) round($weighted / $totalWeight);
        }

        return (int) round(array_sum(array_column($children, 'rollup_pct')) / count($children));
    }

    /**
     * @return array{total:int, active:int, at_risk:int, done:int, avg_progress:int}
     */
    public function summary(int $cycleId): array
    {
        $tree = $this->tree($cycleId);
        $byStatus = PerformanceGoal::query()
            ->where('performance_cycle_id', $cycleId)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $avg = count($tree) > 0
            ? (int) round(array_sum(array_column($tree, 'rollup_pct')) / count($tree))
            : 0;

        return [
            'total' => (int) $byStatus->sum(),
            'active' => (int) $byStatus->get('active', 0),
            'at_risk' => (int) $byStatus->get('at_risk', 0),
            'done' => (int) $byStatus->get('done', 0),
            'avg_progress' => $avg,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createGoal(array $data): PerformanceGoal
    {
        return PerformanceGoal::create([
            'performance_cycle_id' => $data['performance_cycle_id'],
            'personnel_id' => $data['personnel_id'] ?? null,
            'parent_goal_id' => $data['parent_goal_id'] ?? null,
            'goal_type' => in_array($data['goal_type'] ?? '', PerformanceGoal::TYPES, true) ? $data['goal_type'] : 'objective',
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'weight_percent' => (float) ($data['weight_percent'] ?? 0),
            'unit' => $data['unit'] ?? null,
            'target_value' => $data['target_value'] ?? null,
            'current_value' => $data['current_value'] ?? 0,
            'status' => in_array($data['status'] ?? '', PerformanceGoal::STATUSES, true) ? $data['status'] : 'active',
            'due_date' => $data['due_date'] ?? null,
            'created_by' => auth()->id(),
        ]);
    }

    public function addCheckin(PerformanceGoal $goal, float $value, ?string $note = null): PerformanceGoalCheckin
    {
        $checkin = $goal->checkins()->create([
            'value' => $value,
            'note' => $note,
            'created_by' => auth()->id(),
        ]);

        // The latest reading becomes the goal's current value.
        $goal->update(['current_value' => $value]);

        return $checkin;
    }
}
