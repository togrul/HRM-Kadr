<?php

namespace App\Providers\Concerns;

use Livewire\Livewire;

trait RegistersLivewireAliases
{
    /**
     * Register Livewire aliases from a map of alias => class.
     */
    protected function registerAliases(array $map, string $prefix = ''): void
    {
        foreach ($map as $alias => $class) {
            $name = $prefix ? "{$prefix}.{$alias}" : $alias;
            Livewire::component($name, $class);
        }
    }
}
