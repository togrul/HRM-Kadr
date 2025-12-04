<?php

namespace App\Contracts;

interface ToggleStateInterface
{
    public function enabled(string $key): bool;

    public function all(): array;
}
