<?php

namespace App\Observers;

use App\Models\WorkNorm;
use App\Observers\Concerns\FlushesCallPersonnelCache;
use App\Support\PersonnelDropdownCache;

class WorkNormObserver
{
    use FlushesCallPersonnelCache;

    public function saved(WorkNorm $workNorm): void
    {
        $this->forgetLocaleAwareCache('work_norms');
        PersonnelDropdownCache::forgetWorkNorms();
    }

    public function deleted(WorkNorm $workNorm): void
    {
        $this->saved($workNorm);
    }
}
