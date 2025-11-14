<?php

namespace App\Concerns;

use App\Models\AppealStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

trait LoadsAppealStatuses
{
    protected ?Collection $appealStatusesCache = null;

    protected function appealStatuses(): Collection
    {
        if ($this->appealStatusesCache instanceof Collection) {
            return $this->appealStatusesCache;
        }

        $locale = app()->getLocale();

        return $this->appealStatusesCache = Cache::remember(
            "appeal-statuses:{$locale}",
            now()->addHours(6),
            fn () => AppealStatus::query()
                ->select('id', 'name', 'locale')
                ->where('locale', $locale)
                ->orderBy('id')
                ->get()
        );
    }
}
