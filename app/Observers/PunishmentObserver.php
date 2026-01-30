<?php

namespace App\Observers;

use App\Models\Punishment;
use App\Support\PersonnelDropdownCache;

class PunishmentObserver
{
    public function saved(Punishment $punishment): void
    {
        PersonnelDropdownCache::forgetPunishments();
    }

    public function deleted(Punishment $punishment): void
    {
        $this->saved($punishment);
    }
}
