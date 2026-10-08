<?php

namespace App\Modules\Leaves\Application\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Generation counter for the leaves list's short-lived page/stat caches. Every leave
 * write bumps it, so a deleted (or added, approved…) leave disappears from the table on
 * the very next render instead of lingering until the cache expires while the
 * uncached header counters already moved on.
 */
class LeaveListCacheVersion
{
    private const KEY = 'leaves:list-version';

    public function current(): int
    {
        return (int) Cache::get(self::KEY, 0);
    }

    public function bump(): void
    {
        Cache::add(self::KEY, 0);
        Cache::increment(self::KEY);
    }
}
