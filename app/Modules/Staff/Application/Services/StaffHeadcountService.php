<?php

namespace App\Modules\Staff\Application\Services;

use App\Models\Personnel;
use App\Models\StaffSchedule;
use App\Modules\Staff\Contracts\StaffingLookup;
use App\Modules\Staff\Contracts\StaffSlotCheck;
use App\Services\Modules\ModuleState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The single source of the ştat (staff schedule) Dolu / Vakant / Artıq figures.
 *
 * "Dolu" is never read from the stored `staff_schedules.filled` counter — order and
 * approval flows nudge that counter up and down and it drifts from reality. Instead the
 * people currently working in each row's exact (structure, position) pair are counted
 * with one grouped query. Active = approved (is_pending false), not soft-deleted, joined
 * on/before today (or no join date), and no leave date or a leave date not yet passed —
 * the same definition the Reports module uses for headcount.
 *
 * Vacancy and over-staffing never net out: per row vacant = max(total − filled, 0) and
 * over = max(filled − total, 0); every aggregate sums those per-row values.
 */
class StaffHeadcountService implements StaffingLookup
{
    /** Bucket key for a row / employee without a position. */
    public const NO_POSITION = 0;

    public function __construct(private readonly ModuleState $modules) {}

    /**
     * Active headcount per (structure, position) — one query.
     *
     * @param  list<int>|null  $structureIds  null = every structure
     * @return array<int, array<int, int>> structure_id => [position_id|0 => count]
     */
    public function activeCounts(?array $structureIds = null): array
    {
        if ($structureIds === []) {
            return [];
        }

        $counts = [];

        $this->activePersonnelQuery()
            ->when($structureIds !== null, fn (Builder $query) => $query->whereIn('structure_id', $structureIds))
            ->whereNotNull('structure_id')
            ->select('structure_id', 'position_id', DB::raw('count(*) as aggregate'))
            ->groupBy('structure_id', 'position_id')
            ->toBase()
            ->get()
            ->each(function (object $row) use (&$counts): void {
                $counts[(int) $row->structure_id][(int) ($row->position_id ?? self::NO_POSITION)] = (int) $row->aggregate;
            });

        return $counts;
    }

    /**
     * Write live `filled`, `vacant`, `over` and `unassigned` onto every row. When several
     * rows share one (structure, position) the people are dealt out across them in turn
     * and any remainder shows as over-staffing on the last one — nobody is counted twice.
     *
     * @param  Collection<int, StaffSchedule>  $rows
     * @param  array<int, array<int, int>>  $counts  {@see activeCounts()}
     */
    public function hydrate(Collection $rows, array $counts): void
    {
        $rows
            ->groupBy(fn (StaffSchedule $row): string => (int) $row->structure_id.':'.(int) ($row->position_id ?? self::NO_POSITION))
            ->each(function (Collection $group) use ($counts): void {
                $first = $group->first();
                $remaining = (int) ($counts[(int) $first->structure_id][(int) ($first->position_id ?? self::NO_POSITION)] ?? 0);
                $last = $group->count() - 1;

                foreach ($group->values() as $index => $row) {
                    $total = max(0, (int) $row->total);
                    $filled = $index === $last ? $remaining : min($remaining, $total);
                    $remaining -= $filled;

                    $row->filled = $filled;
                    $row->vacant = max(0, $total - $filled);
                    $row->setAttribute('over', max(0, $filled - $total));
                    $row->setAttribute('unassigned', empty($row->position_id));
                }
            });
    }

    /**
     * People working in a (structure, position) pair that has no ştat row — "Ştatdankənar".
     *
     * @param  Collection<int, StaffSchedule>  $rows
     * @param  array<int, array<int, int>>  $counts
     * @return array<int, array<int, int>> structure_id => [position_id|0 => count]
     */
    public function offStaff(Collection $rows, array $counts): array
    {
        $covered = [];
        foreach ($rows as $row) {
            $covered[(int) $row->structure_id][(int) ($row->position_id ?? self::NO_POSITION)] = true;
        }

        $offStaff = [];
        foreach ($counts as $structureId => $byPosition) {
            foreach ($byPosition as $positionId => $count) {
                if ($count > 0 && ! isset($covered[$structureId][$positionId])) {
                    $offStaff[$structureId][$positionId] = $count;
                }
            }
        }

        return $offStaff;
    }

    public function check(?int $structureId, ?int $positionId): StaffSlotCheck
    {
        if (! $structureId || ! $positionId || ! $this->modules->enabled('staff')) {
            return StaffSlotCheck::unknown();
        }

        $rows = StaffSchedule::query()
            ->where('structure_id', $structureId)
            ->where('position_id', $positionId)
            ->get(['id', 'structure_id', 'position_id', 'total']);

        if ($rows->isEmpty()) {
            return StaffSlotCheck::missing($this->blocksOverstaffing());
        }

        $total = (int) $rows->sum(fn (StaffSchedule $row): int => max(0, (int) $row->total));
        $filled = $this->activePersonnelQuery()
            ->where('structure_id', $structureId)
            ->where('position_id', $positionId)
            ->count();

        return $filled >= $total
            ? StaffSlotCheck::full($total, $filled, $this->blocksOverstaffing())
            : StaffSlotCheck::available($total, $filled);
    }

    public function vacancy(?int $structureId, ?int $positionId): int
    {
        return $this->check($structureId, $positionId)->vacant();
    }

    public function blocksOverstaffing(): bool
    {
        return (bool) config('staff.hire_guard.block', false);
    }

    /**
     * Total / filled / vacant per structure (own rows only) for the landing page.
     *
     * @return array<int, array{total:int, filled:int, vacant:int}>
     */
    public function structureFill(): array
    {
        $rows = StaffSchedule::query()->get(['id', 'structure_id', 'position_id', 'total']);
        if ($rows->isEmpty()) {
            return [];
        }

        $this->hydrate($rows, $this->activeCounts($rows->pluck('structure_id')->map(fn ($id): int => (int) $id)->unique()->values()->all()));

        return $rows
            ->groupBy(fn (StaffSchedule $row): int => (int) $row->structure_id)
            ->map(fn (Collection $group): array => [
                'total' => (int) $group->sum(fn (StaffSchedule $row): int => max(0, (int) $row->total)),
                'filled' => (int) $group->sum(fn (StaffSchedule $row): int => min((int) $row->filled, max(0, (int) $row->total))),
                'vacant' => (int) $group->sum('vacant'),
            ])
            ->all();
    }

    /**
     * @return Builder<Personnel>
     */
    public function activePersonnelQuery(): Builder
    {
        $today = today()->toDateString();

        return Personnel::query()
            ->where('is_pending', false)
            ->where(fn (Builder $query) => $query->whereNull('join_work_date')->orWhereDate('join_work_date', '<=', $today))
            ->where(fn (Builder $query) => $query->whereNull('leave_work_date')->orWhereDate('leave_work_date', '>=', $today));
    }
}
