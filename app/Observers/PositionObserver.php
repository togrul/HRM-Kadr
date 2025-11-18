<?php

namespace App\Observers;

use App\Models\Position;
use App\Observers\Concerns\FlushesCallPersonnelCache;
use Illuminate\Support\Facades\Cache;

class PositionObserver
{
    use FlushesCallPersonnelCache;

    public function saved(Position $position): void
    {
        $this->forgetCallPersonnelCache('positions');
        Cache::forget('staff:positions');
    }

    public function deleted(Position $position): void
    {
        $this->saved($position);
    }
}
