<?php

namespace App\Services\Profiles;

class ProfileState
{
    public function __construct(
        private array $profiles = [],
        private string $active = 'default',
        private array $baseModules = [],
    ) {
        // Profile keys are lower-case; normalise the active value (e.g. APP_TYPE=PUBLIC) so
        // its module/feature overrides actually apply regardless of how it's cased in env.
        $this->active = strtolower(trim($this->active));
    }

    public function active(): string
    {
        return $this->active;
    }

    public function modules(): array
    {
        $overrides = $this->profiles[$this->active]['modules'] ?? [];

        return collect($this->baseModules)
            ->map(function ($entry, $slug) use ($overrides) {
                if (array_key_exists($slug, $overrides)) {
                    $entry['enabled'] = (bool) $overrides[$slug];
                }

                return $entry;
            })
            ->all();
    }

    public function features(): array
    {
        $overrides = $this->profiles[$this->active]['features'] ?? [];

        return $overrides;
    }

    public function all(): array
    {
        return $this->profiles;
    }
}
