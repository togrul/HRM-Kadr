<?php

namespace App\Observers;

use App\Models\SocialOrigin;
use App\Observers\Concerns\FlushesCallPersonnelCache;

class SocialOriginObserver
{
    use FlushesCallPersonnelCache;

    public function saved(SocialOrigin $origin): void
    {
        $this->forgetCallPersonnelCache('social_origins');
    }

    public function deleted(SocialOrigin $origin): void
    {
        $this->saved($origin);
    }
}
