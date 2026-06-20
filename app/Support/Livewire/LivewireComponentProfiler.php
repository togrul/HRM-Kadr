<?php

namespace App\Support\Livewire;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Livewire\Features\SupportTesting\ComponentState;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

class LivewireComponentProfiler
{
    /**
     * @param  class-string|string  $component
     * @param  array<string, mixed>  $params
     * @param  array<string, mixed>  $queryParams
     * @return array<string, float|int|string|null>
     */
    public function measureRender(Authenticatable $user, string $component, array $params = [], array $queryParams = []): array
    {
        $memoryBefore = memory_get_usage(true);
        $peakBefore = memory_get_peak_usage(true);
        $startedAt = microtime(true);
        $test = $this->makeTestable($user, $component, $params, $queryParams);
        $elapsedMs = round((microtime(true) - $startedAt) * 1000, 2);
        $memoryAfter = memory_get_usage(true);
        $peakAfter = memory_get_peak_usage(true);

        $html = $test->html();
        $domHtml = $test->html(true);

        return [
            'render_ms' => $elapsedMs,
            'response_bytes' => strlen($html),
            'html_bytes' => strlen($domHtml),
            'snapshot_bytes' => strlen($this->extractWireAttribute($html, 'snapshot')),
            'effects_bytes' => strlen($this->extractWireAttribute($html, 'effects')),
            'memory_bytes' => max(0, $memoryAfter - $memoryBefore),
            'peak_memory_bytes' => max(0, $peakAfter - $peakBefore),
        ];
    }

    /**
     * @param  class-string|string  $component
     * @param  array<string, mixed>  $params
     * @param  array<string, mixed>  $queryParams
     * @return array<string, float|int|string|null>
     */
    public function measureInteraction(
        Authenticatable $user,
        string $component,
        callable $interaction,
        array $params = [],
        array $queryParams = [],
        ?callable $boot = null,
    ): array {
        $memoryBefore = memory_get_usage(true);
        $peakBefore = memory_get_peak_usage(true);
        $test = $this->makeTestable($user, $component, $params, $queryParams);

        if ($boot) {
            $boot($test);
        }

        $startedAt = microtime(true);
        $interaction($test);
        $elapsedMs = round((microtime(true) - $startedAt) * 1000, 2);
        $memoryAfter = memory_get_usage(true);
        $peakAfter = memory_get_peak_usage(true);

        $state = $this->extractState($test);
        $responseContent = (string) $state->getResponse()->getContent();
        $effects = (array) $state->getEffects();
        $effectHtml = (string) ($effects['html'] ?? '');
        $snapshot = json_encode($state->getSnapshot(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return [
            'render_ms' => $elapsedMs,
            'response_bytes' => strlen($responseContent),
            'html_bytes' => strlen($effectHtml),
            'snapshot_bytes' => strlen((string) $snapshot),
            'effects_bytes' => strlen(json_encode($effects, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: ''),
            'memory_bytes' => max(0, $memoryAfter - $memoryBefore),
            'peak_memory_bytes' => max(0, $peakAfter - $peakBefore),
        ];
    }

    /**
     * @param  class-string|string  $component
     * @param  array<string, mixed>  $params
     * @param  array<string, mixed>  $queryParams
     */
    private function makeTestable(Authenticatable $user, string $component, array $params = [], array $queryParams = []): Testable
    {
        Livewire::actingAs($user);

        return Livewire::withQueryParams($queryParams)->test($component, $params);
    }

    private function extractState(Testable $test): ComponentState
    {
        return Closure::bind(fn (): ComponentState => $this->lastState, $test, $test)();
    }

    private function extractWireAttribute(string $html, string $attribute): string
    {
        if (! preg_match(sprintf('/wire:%s="([^"]*)"/', preg_quote($attribute, '/')), $html, $matches)) {
            return '';
        }

        return (string) ($matches[1] ?? '');
    }
}
