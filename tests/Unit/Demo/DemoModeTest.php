<?php

use App\Modules\Demo\Application\Services\DemoTenantRepository;
use App\Modules\Demo\Application\Services\DemoTenantSwitcher;
use App\Modules\Demo\Http\Middleware\ResolveDemoTenant;
use App\Modules\Demo\Models\DemoTenant;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

/**
 * Demo rejimi: bayraq sönülü olanda heç nə dəyişmir; aktiv olanda hər müştəri öz
 * bazası, cache-i və faylları ilə işləyir, müddəti bitən müştəri içəri buraxılmır.
 */
beforeEach(function (): void {
    $this->dir = storage_path('framework/testing/demo-'.uniqid());
    File::ensureDirectoryExists($this->dir);
    config(['database.connections.'.DemoTenant::CONNECTION => demoSqlite($this->dir.'/control.sqlite')]);
});

afterEach(function (): void {
    DB::purge(DemoTenant::CONNECTION);
    foreach (['abc', 'xyz'] as $key) {
        foreach (app(DemoTenantSwitcher::class)->storagePaths($key) as $path) {
            File::deleteDirectory($path);
        }
    }
    File::deleteDirectory($this->dir);
});

function demoSqlite(string $path): array
{
    touch($path);

    return ['driver' => 'sqlite', 'database' => $path, 'prefix' => '', 'foreign_key_constraints' => false];
}

/** İdarə bazası (demo_tenants) və bir müştəri bazası (users) qurur. */
function demoTenant(string $dir, string $key, string $email, string $expiresAt): DemoTenant
{
    app(DemoTenantRepository::class)->ensureTable();

    $database = $dir.'/'.$key.'.sqlite';
    config(['database.connections.demo_seed' => demoSqlite($database)]);
    Schema::connection('demo_seed')->create('users', function ($table): void {
        $table->id();
        $table->string('email');
    });
    DB::connection('demo_seed')->table('users')->insert(['email' => $email]);
    DB::purge('demo_seed');

    return DemoTenant::query()->create([
        'key' => $key,
        'name' => strtoupper($key),
        'email' => $email,
        'database' => $database,
        'audit_database' => $database,
        'expires_at' => $expiresAt,
    ]);
}

function demoRequest(string $method, string $uri, array $data = [], array $session = []): Request
{
    $request = Request::create($uri, $method, $data);
    $store = new Store('test', new ArraySessionHandler(10));
    foreach ($session as $key => $value) {
        $store->put($key, $value);
    }
    $request->setLaravelSession($store);

    return $request;
}

it('does nothing when DEMO_MODE is off', function (): void {
    expect(config('demo.enabled'))->toBeFalse()
        ->and(app(Kernel::class)->getMiddlewareGroups()['web'])->not->toContain(ResolveDemoTenant::class)
        ->and(array_keys(Artisan::all()))->not->toContain('demo:create');
});

it('gives each tenant its own database, cache folder and file disks', function (): void {
    $tenant = new DemoTenant([
        'key' => 'abc',
        'database' => 'hrm_demo_abc',
        'audit_database' => 'hrm_demo_abc_audit',
        'expires_at' => now()->addDay(),
    ]);

    app(DemoTenantSwitcher::class)->activate($tenant);

    expect(config('database.connections.'.config('database.default').'.database'))->toBe('hrm_demo_abc')
        ->and(config('database.connections.audit.database'))->toBe('hrm_demo_abc_audit')
        ->and(config('cache.stores.file.path'))->toEndWith('/demo/abc')
        ->and(Storage::disk('local')->path('x.pdf'))->toContain('app/demo/abc')
        ->and(Storage::disk('public')->path('x.png'))->toContain('app/public/demo/abc')
        ->and(Storage::disk('public')->url('x.png'))->toContain('/storage/demo/abc/')
        ->and(Storage::disk('employee_content')->path('v.mp4'))->toContain('app/public/demo/abc/employee-content');

    Cache::put('menus:header', 'abc');
    app(DemoTenantSwitcher::class)->activate(new DemoTenant([
        'key' => 'xyz', 'database' => 'hrm_demo_xyz', 'audit_database' => 'hrm_demo_xyz_audit', 'expires_at' => now()->addDay(),
    ]));

    expect(Cache::get('menus:header'))->toBeNull();
});

it('logs an expired tenant out on the next request', function (): void {
    demoTenant($this->dir, 'old', 'old@demo.test', now()->subMinute()->toDateTimeString());
    $request = demoRequest('GET', '/admin/structures', session: [ResolveDemoTenant::SESSION_KEY => 'old']);

    $response = app(ResolveDemoTenant::class)->handle($request, fn () => response('içəri'));

    expect($response->isRedirect(route('login')))->toBeTrue()
        ->and($request->session()->has(ResolveDemoTenant::SESSION_KEY))->toBeFalse();
});

it('refuses login for an expired tenant and routes an active one to its own database', function (): void {
    demoTenant($this->dir, 'old', 'old@demo.test', now()->subMinute()->toDateTimeString());
    $active = demoTenant($this->dir, 'new', 'new@demo.test', now()->addDays(3)->toDateTimeString());

    $expired = app(ResolveDemoTenant::class)->handle(
        demoRequest('POST', '/login', ['email' => 'old@demo.test']),
        fn () => response('içəri'),
    );
    expect($expired->isRedirect(route('login')))->toBeTrue();

    $request = demoRequest('POST', '/login', ['email' => 'NEW@demo.test']);
    $response = app(ResolveDemoTenant::class)->handle($request, fn () => response('içəri'));

    expect($response->getContent())->toBe('içəri')
        ->and($request->session()->get(ResolveDemoTenant::SESSION_KEY))->toBe('new')
        ->and(config('database.connections.'.config('database.default').'.database'))->toBe($active->database);
});
