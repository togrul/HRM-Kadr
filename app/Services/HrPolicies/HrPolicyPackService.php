<?php

namespace App\Services\HrPolicies;

use App\Services\Profiles\ProfileState;
use Illuminate\Support\Arr;

class HrPolicyPackService
{
    public function __construct(
        private readonly ProfileState $profileState,
        private readonly array $config = [],
    ) {}

    public function activePack(): string
    {
        $profile = $this->profileState->active();

        return (string) Arr::get($this->config, 'profile_map.'.$profile, 'corporate');
    }

    public function selfServiceApproval(string $requestType): array
    {
        return $this->packValue('self_service_approval.'.$requestType, $this->fallbackValue('self_service_approval.'.$requestType, []));
    }

    public function menuVisible(string $key): bool
    {
        return (bool) $this->packValue('menu_visibility.'.$key, $this->fallbackValue('menu_visibility.'.$key, true));
    }

    public function permissionEnabled(string $key, bool $default = true): bool
    {
        return (bool) $this->packValue('permission_flags.'.$key, $this->fallbackValue('permission_flags.'.$key, $default));
    }

    public function workflow(string $path, mixed $default = null): mixed
    {
        return $this->packValue('workflow_defaults.'.$path, $this->fallbackValue('workflow_defaults.'.$path, $default));
    }

    /**
     * @param  array<int, string>  $default
     * @return array<int, string>
     */
    public function workflowTabs(string $module, array $default): array
    {
        $configured = $this->workflow($module.'.tabs', $default);
        if (! is_array($configured)) {
            return $default;
        }

        $resolved = array_values(array_intersect($default, array_map(static fn ($tab) => (string) $tab, $configured)));

        return $resolved !== [] ? $resolved : $default;
    }

    /**
     * @param  array<int, string>  $default
     * @return array<int, string>
     */
    public function workflowTestTabs(string $module, array $default): array
    {
        $configured = $this->workflow($module.'.test_tabs', $default);
        if (! is_array($configured)) {
            return $default;
        }

        $resolved = array_values(array_intersect($default, array_map(static fn ($tab) => (string) $tab, $configured)));

        return $resolved !== [] ? $resolved : $default;
    }

    private function packValue(string $path, mixed $default = null): mixed
    {
        return Arr::get($this->config, 'packs.'.$this->activePack().'.'.$path, $default);
    }

    private function fallbackValue(string $path, mixed $default = null): mixed
    {
        return Arr::get($this->config, 'packs.corporate.'.$path, $default);
    }
}
