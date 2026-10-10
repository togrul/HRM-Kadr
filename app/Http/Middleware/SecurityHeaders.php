<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hər cavaba brauzer səviyyəsində qoruyucu HTTP başlıqları əlavə edir.
 *
 * Skript CSP-si qəsdən yoxdur (Alpine/Livewire inline ifadələri pozular); yalnız
 * `frame-ancestors` (clickjacking) verilir. Cavab artıq öz CSP-sini daşıyırsa (fayl
 * endirmələri — `sandbox`), ona `frame-ancestors` əlavə olunur, üzərinə yazılmır.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);
        $headers = $response->headers;

        if (! $headers->has('X-Content-Type-Options')) {
            $headers->set('X-Content-Type-Options', 'nosniff');
        }

        if (! $headers->has('X-Frame-Options')) {
            $headers->set('X-Frame-Options', 'SAMEORIGIN');
        }

        if (! $headers->has('Referrer-Policy')) {
            $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        }

        $csp = (string) $headers->get('Content-Security-Policy', '');
        if (! str_contains($csp, 'frame-ancestors')) {
            $headers->set('Content-Security-Policy', ltrim($csp.'; ', '; ')."frame-ancestors 'self'");
        }

        if ($this->shouldSendHsts($request) && ! $headers->has('Strict-Transport-Security')) {
            $value = 'max-age='.max(0, (int) config('security.hsts.max_age', 31536000));
            if ((bool) config('security.hsts.include_subdomains', false)) {
                $value .= '; includeSubDomains';
            }
            $headers->set('Strict-Transport-Security', $value);
        }

        return $response;
    }

    private function shouldSendHsts(Request $request): bool
    {
        return (bool) config('security.hsts.enabled', true)
            && app()->environment('production')
            && $request->isSecure();
    }
}
