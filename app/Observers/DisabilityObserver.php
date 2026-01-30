<?php

namespace App\Observers;

use App\Models\Disability;
use App\Observers\Concerns\FlushesCallPersonnelCache;
use App\Support\PersonnelDropdownCache;

class DisabilityObserver
{
    use FlushesCallPersonnelCache;

    public function saved(Disability $disability): void
    {
        $this->forgetCallPersonnelCache('disabilities');
        PersonnelDropdownCache::forgetDisabilities();
    }

    public function deleted(Disability $disability): void
    {
        $this->saved($disability);
    }
}
