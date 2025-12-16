<?php

namespace App\Providers\Concerns;

use Illuminate\Support\Str;
use Livewire\Livewire;
use Symfony\Component\Finder\Finder;

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

    /**
     * Auto-register Livewire components by scanning a directory.
     *
     * @param  string  $directory  Absolute path to the Livewire directory.
     * @param  string  $baseNamespace  Base namespace for the directory (e.g., App\Modules\Orders\Livewire).
     * @param  string  $prefix  Optional alias prefix (e.g., 'orders').
     */
    protected function registerAliasesFromPath(string $directory, string $baseNamespace, string $prefix = ''): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $finder = (new Finder())->files()->in($directory)->name('*.php');

        foreach ($finder as $file) {
            $relativePath = Str::after($file->getRealPath(), rtrim($directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR);
            $relativeClass = str_replace(['/', '\\', '.php'], ['\\', '\\', ''], $relativePath);
            $class = rtrim($baseNamespace, '\\').'\\'.$relativeClass;

            if (! class_exists($class) || ! is_subclass_of($class, \Livewire\Component::class)) {
                continue;
            }

            $aliasSegments = collect(explode('.', str_replace('\\', '.', $relativeClass)))
                ->map(fn ($segment) => Str::kebab($segment));
            $aliasPart = $aliasSegments->implode('.');
            $alias = $prefix ? "{$prefix}.{$aliasPart}" : $aliasPart;

            Livewire::component($alias, $class);
        }
    }
}
