<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;

class TrustHosts extends Middleware
{
    /**
     * Etibarlı host-lar: APP_URL host-u və subdomenləri, `TRUSTED_HOSTS` siyahısı və
     * konteyner daxili sağlamlıq yoxlamaları üçün localhost. Saxta `Host` /
     * `X-Forwarded-Host` başlığı şifrə bərpası linkini yad domenə yönəldə bilməz.
     *
     * @return array<int, string|null>
     */
    public function hosts(): array
    {
        $extra = array_map(
            fn (string $host): string => '^'.preg_quote($host).'$',
            array_values(array_filter(array_map('trim', explode(',', (string) config('security.trusted_hosts', ''))))),
        );

        return [
            $this->allSubdomainsOfApplicationUrl(),
            '^localhost$',
            '^127\.0\.0\.1$',
            ...$extra,
        ];
    }
}
