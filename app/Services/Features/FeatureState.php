<?php

namespace App\Services\Features;

use App\Contracts\ToggleStateInterface;
use Illuminate\Support\Arr;

class FeatureState implements ToggleStateInterface
{
    public function __construct(private array $flags = [])
    {
    }

    public function enabled(string $feature): bool
    {
        return (bool) Arr::get($this->flags, $feature, false);
    }

    public function all(): array
    {
        return $this->flags;
    }

    public function refresh(array $flags): void
    {
        $this->flags = $flags;
    }
}
