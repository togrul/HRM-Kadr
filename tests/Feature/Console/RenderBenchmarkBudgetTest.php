<?php

use App\Models\User;
use App\Support\Livewire\LivewireComponentProfiler;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;

it('enforces benchmark thresholds independently of machine speed', function (float $milliseconds, int $bytes, int $exitCode, array $exceeded): void {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('show-orders', 'web'));
    $profiler = Mockery::mock(LivewireComponentProfiler::class);
    $metrics = ['render_ms' => $milliseconds, 'response_bytes' => $bytes];
    $profiler->shouldReceive('measureRender')->once()->andReturn($metrics);
    $profiler->shouldReceive('measureInteraction')->once()->andReturn($metrics);
    $this->app->instance(LivewireComponentProfiler::class, $profiler);

    expect(Artisan::call('orders:list-render-benchmark', [
        '--json' => true,
        '--render-ms-budget' => 100,
        '--filter-ms-budget' => 100,
        '--render-response-budget' => 1000,
        '--filter-response-budget' => 1000,
    ]))->toBe($exitCode);
    $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
    foreach ($report['results'] as $result) {
        expect($result['exceeded'])->toBe($exceeded)
            ->and((float) $result['render_ms'])->toBe($milliseconds)
            ->and($result['response_bytes'])->toBe($bytes);
    }
})->with([
    'exact boundary passes' => [100.0, 1000, 0, []],
    'time violation fails' => [100.01, 1000, 1, ['render_ms']],
    'payload violation fails' => [100.0, 1001, 1, ['response_bytes']],
    'both violations fail' => [101.0, 1001, 1, ['response_bytes', 'render_ms']],
]);

it('reports component errors as failed probes', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('show-orders', 'web'));
    $profiler = Mockery::mock(LivewireComponentProfiler::class);
    $profiler->shouldReceive('measureRender')->once()->andThrow(new RuntimeException('render failure'));
    $profiler->shouldReceive('measureInteraction')->once()->andReturn(['render_ms' => 1.0, 'response_bytes' => 1]);
    $this->app->instance(LivewireComponentProfiler::class, $profiler);

    expect(Artisan::call('orders:list-render-benchmark', ['--json' => true]))->toBe(1);
    $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
    expect($report['summary']['failed_probes'])->toBe(1)
        ->and($report['results'][0]['error'])->toBe('render failure');
});
