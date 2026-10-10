<?php

namespace Tests\Feature\Console;

use App\Support\Livewire\LivewireComponentProfiler;
use Illuminate\Contracts\Auth\Authenticatable;

/** Executes real component requests and payload measurement; fixes only test clock output. */
trait UsesDeterministicRenderTiming
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(LivewireComponentProfiler::class, new class extends LivewireComponentProfiler
        {
            public function measureRender(Authenticatable $user, string $component, array $params = [], array $queryParams = []): array
            {
                return [...parent::measureRender($user, $component, $params, $queryParams), 'render_ms' => 10.0];
            }

            public function measureInteraction(Authenticatable $user, string $component, callable $interaction, array $params = [], array $queryParams = [], ?callable $boot = null): array
            {
                return [...parent::measureInteraction($user, $component, $interaction, $params, $queryParams, $boot), 'render_ms' => 10.0];
            }
        });
    }
}
