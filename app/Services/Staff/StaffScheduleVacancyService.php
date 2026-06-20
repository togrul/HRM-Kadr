<?php

namespace App\Services\Staff;

use App\Models\StaffSchedule;

/**
 * Reads and adjusts the staff schedule (ştat cədvəli) vacancy for a structure+position.
 *
 * Used by the hire order flow: before an employee is added through an order we check
 * there is a free slot; if there is none the author can have one created on the spot
 * (a brand-new schedule row, or one extra slot on a fully-filled row — e.g. total 4 /
 * filled 4 / vacant 0 becomes total 5 / filled 4 / vacant 1). When the hire is
 * approved the slot is consumed (filled +1, vacant recomputed).
 */
class StaffScheduleVacancyService
{
    /** Current number of vacant slots for the structure+position (0 if no row). */
    public function vacancy(?int $structureId, ?int $positionId): int
    {
        return (int) ($this->row($structureId, $positionId)?->vacant ?? 0);
    }

    /**
     * Guarantee at least one vacant slot, creating or expanding the schedule row as
     * needed. Returns the affected row.
     */
    public function ensureOneVacancy(int $structureId, int $positionId): StaffSchedule
    {
        $row = $this->row($structureId, $positionId);

        if (! $row) {
            return StaffSchedule::query()->create([
                'structure_id' => $structureId,
                'position_id' => $positionId,
                'total' => 1,
                'filled' => 0,
                'vacant' => 1,
            ]);
        }

        if ((int) $row->vacant <= 0) {
            $row->forceFill([
                'total' => (int) $row->total + 1,
                'vacant' => (int) $row->vacant + 1,
            ])->save();
        }

        return $row;
    }

    /**
     * Consume one slot when a hire becomes an active employee: filled +1, vacant
     * recomputed. The total is bumped if it would otherwise be exceeded, so the figures
     * never go negative.
     */
    public function consumeForHire(?int $structureId, ?int $positionId): void
    {
        if (! $structureId || ! $positionId) {
            return;
        }

        $row = $this->row($structureId, $positionId);

        if (! $row) {
            StaffSchedule::query()->create([
                'structure_id' => $structureId,
                'position_id' => $positionId,
                'total' => 1,
                'filled' => 1,
                'vacant' => 0,
            ]);

            return;
        }

        $filled = (int) $row->filled + 1;
        $total = max((int) $row->total, $filled);

        $row->forceFill([
            'filled' => $filled,
            'total' => $total,
            'vacant' => max(0, $total - $filled),
        ])->save();
    }

    private function row(?int $structureId, ?int $positionId): ?StaffSchedule
    {
        if (! $structureId || ! $positionId) {
            return null;
        }

        return StaffSchedule::query()
            ->where('structure_id', $structureId)
            ->where('position_id', $positionId)
            ->first();
    }
}
