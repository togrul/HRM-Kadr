<?php

namespace App\Livewire\Concerns;

use Closure;

trait WithRuntimeMemo
{
    protected array $memo = [];

    protected function rememberRuntime(string $key, Closure $resolver): mixed
    {
        if (! array_key_exists($key, $this->memo)) {
            $this->memo[$key] = $resolver();
        }

        return $this->memo[$key];
    }

    protected function forgetRuntime(array|string|null $keys = null): void
    {
        if ($keys === null) {
            $this->memo = [];

            return;
        }

        foreach ((array) $keys as $key) {
            unset($this->memo[$key]);
        }
    }

    protected function resetRuntimeMemo(): void
    {
        $this->memo = [];
    }
}
