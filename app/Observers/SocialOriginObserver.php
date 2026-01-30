<?php

namespace App\Observers;

use App\Models\SocialOrigin;
use App\Observers\Concerns\FlushesCallPersonnelCache;
use App\Support\PersonnelDropdownCache;

class SocialOriginObserver
{
    use FlushesCallPersonnelCache;

    public function saved(SocialOrigin $origin): void
    {
        $this->forgetCallPersonnelCache('social_origins');
        PersonnelDropdownCache::forgetSocialOrigins();
    }

    public function deleted(SocialOrigin $origin): void
    {
        $this->saved($origin);
    }
}
