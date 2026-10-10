<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    /**
     * Etibar edilən proxy-lər `TRUSTED_PROXIES` (config/security.php) ilə verilir.
     * Standart dəyər yalnız loopback və özəl şəbəkələrdir; '*' açıq şəkildə yazılmalıdır.
     *
     * @return array<int, string>|string|null
     */
    protected function proxies()
    {
        if (static::$alwaysTrustProxies) {
            return static::$alwaysTrustProxies;
        }

        $configured = trim((string) config('security.trusted_proxies', ''));

        if ($configured === '') {
            return null;
        }

        if ($configured === '*' || $configured === '**') {
            return $configured;
        }

        return array_values(array_filter(array_map('trim', explode(',', $configured))));
    }
}
