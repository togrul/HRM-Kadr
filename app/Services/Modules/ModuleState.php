<?php

namespace App\Services\Modules;

use Illuminate\Support\Arr;

class ModuleState
{
    public function __construct(private array $catalog = [])
    {
    }

    public function enabled(string $slug): bool
    {
        $entry = $this->get($slug);

        return (bool) ($entry['enabled'] ?? false);
    }

    public function migrationPath(string $slug): ?string
    {
        $entry = $this->get($slug);

        return $entry['migrations'] ?? null;
    }

    public function provider(string $slug): ?string
    {
        $entry = $this->get($slug);

        return $entry['provider'] ?? null;
    }

    public function allEnabledProviders(): array
    {
        return collect($this->catalog)
            ->filter(fn ($entry) => ($entry['enabled'] ?? false) && ! empty($entry['provider']))
            ->pluck('provider')
            ->all();
    }

    public function refresh(array $catalog): void
    {
        $this->catalog = $catalog;
    }

    /**
     * Return migration paths for enabled modules that define one and exist on disk.
     *
     * @return array<int,string>
     */
    public function enabledMigrationPaths(): array
    {
        return collect($this->catalog)
            ->filter(fn ($entry) => ($entry['enabled'] ?? false) && ! empty($entry['migrations']))
            ->map(fn ($entry) => $entry['migrations'])
            ->filter(fn ($path) => is_dir($path))
            ->values()
            ->all();
    }

    private function get(string $slug): array
    {
        return Arr::get($this->catalog, $slug, []);
    }
}
