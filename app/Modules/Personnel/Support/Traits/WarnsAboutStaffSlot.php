<?php

namespace App\Modules\Personnel\Support\Traits;

use App\Modules\Staff\Contracts\StaffingLookup;
use App\Modules\Staff\Contracts\StaffSlotCheck;
use Livewire\Attributes\Computed;

/**
 * Hire form ↔ ştat cədvəli: once a structure and a position are picked, say so when the
 * ştat has no such row or the row is already full. A warning only — companies often hire
 * before the ştat is amended — unless the install hard-blocks (`staff.hire_guard.block`).
 */
trait WarnsAboutStaffSlot
{
    #[Computed]
    public function staffSlotCheck(): StaffSlotCheck
    {
        $personnel = $this->personalForm->personnel ?? [];

        return app(StaffingLookup::class)->check(
            (int) ($personnel['structure_id'] ?? 0) ?: null,
            (int) ($personnel['position_id'] ?? 0) ?: null,
        );
    }

    /**
     * True (with the message on the position field) when the save must stop.
     */
    protected function staffSlotBlocksSave(): bool
    {
        $check = $this->staffSlotCheck();

        if (! $check->blocks()) {
            return false;
        }

        $this->addError('personalForm.personnel.position_id', (string) $check->message());

        return true;
    }
}
