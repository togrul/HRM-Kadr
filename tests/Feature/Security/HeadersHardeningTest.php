<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TrustHosts;
use App\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

/**
 * Qlobal HTTP başlıqları, proxy/host etibarı və repozitori gigiyenası.
 */
class HeadersHardeningTest extends TestCase
{
    public function test_every_page_gets_the_protective_headers_without_a_script_csp(): void
    {
        $response = $this->get(route('login'));

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        $csp = (string) $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        $this->assertStringNotContainsString('script-src', $csp, 'Alpine/Livewire inline ifadələri pozulmamalıdır.');
        $this->assertFalse($response->headers->has('Strict-Transport-Security'), 'HSTS yalnız production + HTTPS-də.');
    }

    public function test_hsts_is_sent_only_in_production_over_https(): void
    {
        $middleware = new SecurityHeaders;
        $next = fn () => new Response('ok');

        $this->app['env'] = 'production';

        try {
            $secure = $middleware->handle(Request::create('https://hr.example.az/', 'GET'), $next);
            $plain = $middleware->handle(Request::create('http://hr.example.az/', 'GET'), $next);
        } finally {
            $this->app['env'] = 'testing';
        }

        $this->assertStringStartsWith('max-age=', (string) $secure->headers->get('Strict-Transport-Security'));
        $this->assertFalse($plain->headers->has('Strict-Transport-Security'));
    }

    public function test_existing_file_csp_is_extended_not_replaced(): void
    {
        $response = (new SecurityHeaders)->handle(Request::create('/x'), function () {
            $response = new Response('file');
            $response->headers->set('Content-Security-Policy', 'sandbox');

            return $response;
        });

        $this->assertSame("sandbox; frame-ancestors 'self'", $response->headers->get('Content-Security-Policy'));
    }

    public function test_trusted_hosts_accept_only_the_app_host_and_configured_ones(): void
    {
        config(['app.url' => 'https://hr.example.az', 'security.trusted_hosts' => 'hrm.example.az']);
        $patterns = (new TrustHosts($this->app))->hosts();

        $matches = fn (string $host): bool => collect($patterns)->filter()->contains(fn (string $p): bool => (bool) preg_match('{'.$p.'}i', $host));

        $this->assertTrue($matches('hr.example.az'));
        $this->assertTrue($matches('demo.hr.example.az'));
        $this->assertTrue($matches('hrm.example.az'));
        $this->assertTrue($matches('localhost'));
        $this->assertFalse($matches('evil.com'));
        $this->assertFalse($matches('hr.example.az.evil.com'));
    }

    public function test_forged_forwarded_host_is_rejected_once_trusted_hosts_are_set(): void
    {
        config(['app.url' => 'https://hr.example.az']);
        Request::setTrustedHosts(array_filter((new TrustHosts($this->app))->hosts()));

        try {
            $request = Request::create('https://evil.com/password/reset');
            $this->expectException(\Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException::class);
            $request->getHost();
        } finally {
            Request::setTrustedHosts([]);
        }
    }

    public function test_trusted_proxies_come_from_configuration(): void
    {
        $proxies = function (string $configured) {
            config(['security.trusted_proxies' => $configured]);
            $middleware = new TrustProxies;

            return (fn () => $this->proxies())->call($middleware);
        };

        $this->assertSame(['10.0.0.0/8', '192.168.0.0/16'], $proxies('10.0.0.0/8, 192.168.0.0/16'));
        $this->assertSame('*', $proxies('*'));
        $this->assertNull($proxies(''));
        $this->assertNotContains('*', (array) $proxies((string) (require config_path('security.php'))['trusted_proxies']));
    }

    public function test_no_ds_store_files_are_tracked_or_served(): void
    {
        $this->assertFileDoesNotExist(public_path('.DS_Store'));
        $this->assertStringContainsString('.DS_Store', (string) file_get_contents(base_path('.gitignore')));
    }
}
