<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class OrderLookupCache
{
    public static function key(string $group, string $suffix): string
    {
        $version = self::version($group);

        return "order_lookup:{$group}:v{$version}:{$suffix}";
    }

    public static function bump(string $group): void
    {
        $key = self::versionKey($group);
        $current = (int) Cache::get($key, 1);
        Cache::forever($key, $current + 1);
    }

    private static function version(string $group): int
    {
        return (int) Cache::rememberForever(self::versionKey($group), fn () => 1);
    }

    private static function versionKey(string $group): string
    {
        return "order_lookup:{$group}:version";
    }
}
