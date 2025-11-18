<?php

namespace App\Observers;

use App\Models\WorkNorm;
use App\Observers\Concerns\FlushesCallPersonnelCache;

class WorkNormObserver
{
    use FlushesCallPersonnelCache;

    public function saved(WorkNorm $workNorm): void
    {
        $this->forgetLocaleAwareCache('work_norms');
    }

    public function deleted(WorkNorm $workNorm): void
    {
        $this->saved($workNorm);
    }
}
