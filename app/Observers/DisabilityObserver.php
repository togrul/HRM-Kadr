<?php

namespace App\Observers;

use App\Models\Disability;
use App\Observers\Concerns\FlushesCallPersonnelCache;

class DisabilityObserver
{
    use FlushesCallPersonnelCache;

    public function saved(Disability $disability): void
    {
        $this->forgetCallPersonnelCache('disabilities');
    }

    public function deleted(Disability $disability): void
    {
        $this->saved($disability);
    }
}
