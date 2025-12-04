<?php

namespace App\Services\Profiles;

class ProfileState
{
    public function __construct(
        private array $profiles = [],
        private string $active = 'default',
        private array $baseModules = [],
    ) {
        //
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
