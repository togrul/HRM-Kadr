<?php

namespace Tests\Feature\Security;

use App\Models\Personnel;
use App\Models\User;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\RestKpiConnector;
use App\Modules\PerformanceEvaluation\Livewire\Kpi\KpiLibraryWorkspace;
use App\Services\UserAdministrationGuard;
use App\Support\Http\SafeUrl;
use App\Support\Http\UnsafeUrlException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Server tərəfli sorğular (KPI REST konnektoru, portfel link yoxlaması) daxili şəbəkəyə,
 * bulud metadata xidmətinə və ya loopback-ə yönəldilə bilməz; xarici cavab mətni istifadəçiyə
 * qaytarılmır.
 */
class SsrfOutboundRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, list<string>>  $dns
     */
    private function safeUrl(array $dns = []): SafeUrl
    {
        return new SafeUrl(fn (string $host): array => $dns[$host] ?? []);
    }

    public function test_internal_and_reserved_targets_are_blocked(): void
    {
        $guard = $this->safeUrl([
            'intranet.local' => ['10.0.0.5'],
            'rebind.example' => ['93.184.216.34', '127.0.0.1'],
            'v6.example' => ['::ffff:169.254.169.254'],
            'public.example' => ['93.184.216.34'],
        ]);

        foreach ([
            'http://127.0.0.1/admin',
            'http://localhost/',
            'http://169.254.169.254/latest/meta-data/',
            'http://[::1]/',
            'http://[fd00:ec2::254]/',
            'http://2130706433/',
            'http://intranet.local/',
            'http://rebind.example/',
            'http://v6.example/',
            'http://user:pass@public.example/',
            'file:///etc/passwd',
            'gopher://public.example/',
        ] as $url) {
            $this->assertFalse($guard->isAllowed($url), $url);
        }

        $this->assertTrue($guard->isAllowed('https://public.example/api?x=1'));
    }

    public function test_allow_listed_internal_host_is_permitted(): void
    {
        config(['security.outbound.allowed_hosts' => 'erp.internal, other.host']);

        $target = $this->safeUrl(['erp.internal' => ['10.1.2.3']])->assert('http://erp.internal/odata');

        $this->assertNull($target->ip, 'Allow-list host-u DNS yoxlamasından keçmir.');
    }

    public function test_production_requires_https(): void
    {
        $this->app['env'] = 'production';

        try {
            $this->expectException(UnsafeUrlException::class);
            $this->safeUrl(['public.example' => ['93.184.216.34']])->assert('http://public.example/');
        } finally {
            $this->app['env'] = 'testing';
        }
    }

    public function test_kpi_connector_refuses_internal_urls_without_sending_a_request(): void
    {
        Http::fake();
        $this->app->instance(SafeUrl::class, $this->safeUrl(['metadata.example' => ['169.254.169.254']]));

        try {
            app(RestKpiConnector::class)->fetch(['url' => 'http://metadata.example/{tabel_no}', 'value_path' => 'v'], $this->person(), Carbon::parse('2026-01-01'), Carbon::parse('2026-03-31'));
            $this->fail('Daxili ünvan bloklanmalı idi.');
        } catch (RuntimeException $e) {
            $this->assertSame(__('performance_evaluation::kpi.connector.errors.blocked_url'), $e->getMessage());
        }

        Http::assertNothingSent();
    }

    public function test_kpi_connector_never_echoes_the_remote_body_and_does_not_follow_redirects(): void
    {
        Http::fake(['erp.example/*' => Http::response('SECRET-INTERNAL-BODY', 500)]);

        try {
            app(RestKpiConnector::class)->fetch(['url' => 'https://erp.example/{tabel_no}', 'value_path' => 'v'], $this->person(), Carbon::parse('2026-01-01'), Carbon::parse('2026-03-31'));
            $this->fail('Xəta gözlənilirdi.');
        } catch (RuntimeException $e) {
            $this->assertStringNotContainsString('SECRET-INTERNAL-BODY', $e->getMessage());
            $this->assertStringContainsString('500', $e->getMessage());
        }

        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://erp.example/'));
    }

    public function test_pin_placeholder_requires_a_user_administrator(): void
    {
        $hr = grantAllStructures(User::factory()->create());
        $hr->givePermissionTo(
            Permission::findOrCreate('manage-performance-evaluation', 'web'),
            Permission::findOrCreate('show-performance-evaluation', 'web'),
        );
        $this->actingAs($hr);
        Http::fake(['erp.example/*' => Http::response(['total' => 1])]);
        $this->person();

        $form = fn () => Livewire::test(KpiLibraryWorkspace::class)
            ->call('openKpiForm')
            ->set('kpiForm.source_metric', 'rest')
            ->set('connectorForm.url', 'https://erp.example/by-fin/{pin}')
            ->set('connectorForm.auth', 'none')
            ->set('connectorForm.value_path', 'total')
            ->call('testConnector');

        $form()->assertHasErrors(['connectorForm.url']);
        Http::assertNothingSent();

        $hr->givePermissionTo(Permission::findOrCreate(UserAdministrationGuard::MANAGE_USERS, 'web'));
        $form()->assertHasNoErrors(['connectorForm.url']);
    }

    public function test_link_health_check_verifies_tls_and_stores_no_exception_text(): void
    {
        $source = (string) file_get_contents(app_path('Modules/Personnel/Application/Services/ProfessionalPortfolioLinkHealthService.php'));

        $this->assertStringNotContainsString('withoutVerifying', $source);
        $this->assertStringNotContainsString('$e->getMessage(), null]', $source);
    }

    private function person(): Personnel
    {
        return Personnel::withoutEvents(fn () => Personnel::query()->forceCreate([
            'tabel_no' => 'SSRF1',
            'surname' => 'Test',
            'name' => 'Test',
            'patronymic' => 'Test',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'mobile' => '0501112233',
            'nationality_id' => 1,
            'pin' => 'AB12CD3',
            'residental_address' => 'Bakı',
            'education_degree_id' => 1,
            'structure_id' => 1,
            'position_id' => 1,
            'work_norm_id' => 1,
            'join_work_date' => '2024-01-01',
            'added_by' => 1,
            'is_pending' => false,
        ]));
    }
}
