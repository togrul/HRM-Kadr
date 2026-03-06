<?php

namespace App\Modules\Attendance\Application\Services;

use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AttendanceCacheService
{
    public function rememberOverview(
        int $year,
        int $month,
        ?int $structureId,
        Closure $resolver,
        ?int $minutes = null
    ): array {
        $ttl = $minutes ?? (int) config('attendance.performance.overview_cache_minutes', 10);
        $key = $this->overviewKey($year, $month, $structureId);

        /** @var array $resolved */
        $resolved = Cache::remember($key, now()->addMinutes(max(1, $ttl)), $resolver);

        return $resolved;
    }

    /**
     * @param  array<int,int>|null  $structureIds
     */
    public function forgetOverviewRange(Carbon $from, Carbon $to, ?array $structureIds = null): void
    {
        $cursor = $from->copy()->startOfMonth();
        $end = $to->copy()->startOfMonth();

        while ($cursor->lte($end)) {
            $this->forgetOverviewMonth((int) $cursor->year, (int) $cursor->month, $structureIds);
            $cursor->addMonthNoOverflow();
        }
    }

    /**
     * @param  array<int,int>|null  $structureIds
     */
    public function forgetOverviewMonth(int $year, int $month, ?array $structureIds = null): void
    {
        Cache::forget($this->overviewKey($year, $month, null));

        if (is_array($structureIds)) {
            foreach ($structureIds as $structureId) {
                Cache::forget($this->overviewKey($year, $month, (int) $structureId));
            }
        }
    }

    public function overviewKey(int $year, int $month, ?int $structureId = null): string
    {
        $org = (string) (config('app.company') ?: config('app.name', 'hrm'));
        $orgKey = Str::slug($org, '-');
        $scope = $structureId !== null ? 's:'.$structureId : 'all';

        return sprintf('attendance:%s:%04d:%02d:%s', $orgKey, $year, $month, $scope);
    }
}
