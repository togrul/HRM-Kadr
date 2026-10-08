<?php

namespace App\Modules\Staff\Contracts;

/**
 * The sanctioned surface other modules (Personnel, Orders, the landing page) use to ask
 * the ştat cədvəli whether a (structure, position) has room, computed from the live
 * headcount rather than the stored counters.
 *
 * @see \App\Modules\Staff\Application\Services\StaffHeadcountService
 */
interface StaffingLookup
{
    /** Ştat state of a structure + position: room / full / no such ştat row. */
    public function check(?int $structureId, ?int $positionId): StaffSlotCheck;

    /** Open slots for the pair (0 when there is no row). */
    public function vacancy(?int $structureId, ?int $positionId): int;

    /** Install setting (`staff.hire_guard.block`): refuse hires/transfers with no free slot. */
    public function blocksOverstaffing(): bool;

    /**
     * Total / filled / vacant per structure (own rows, descendants not included).
     *
     * @return array<int, array{total:int, filled:int, vacant:int}>
     */
    public function structureFill(): array;
}
