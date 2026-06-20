<?php

namespace App\Services\HrPolicies;

use App\Services\Profiles\ProfileState;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Lang;

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

    public function activeProfile(): string
    {
        return $this->profileState->active();
    }

    public function packConfig(?string $pack = null): array
    {
        return (array) Arr::get($this->config, 'packs.'.($pack ?: $this->activePack()), []);
    }

    public function availablePacks(): array
    {
        return collect((array) Arr::get($this->config, 'packs', []))
            ->map(function (array $config, string $key): array {
                return [
                    'key' => $key,
                    'label' => $this->translatedPackLabel($key, (string) Arr::get($config, 'meta.label', ucfirst($key))),
                    'description' => (string) Arr::get($config, 'meta.description', ''),
                    'recommended_for' => (string) Arr::get($config, 'meta.recommended_for', ''),
                    'menu_count' => count(array_filter((array) Arr::get($config, 'menu_visibility', []))),
                    'permission_count' => count(array_filter((array) Arr::get($config, 'permission_flags', []))),
                ];
            })
            ->values()
            ->all();
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

    public function diagnostics(): array
    {
        $pack = $this->activePack();
        $config = $this->packConfig($pack);
        $moduleEntries = $this->profileState->modules();
        $featureEntries = $this->profileState->features();
        $menuVisibility = (array) Arr::get($config, 'menu_visibility', []);
        $permissionFlags = (array) Arr::get($config, 'permission_flags', []);

        return [
            'active_profile' => $this->translatedProfileLabel($this->activeProfile()),
            'active_pack' => $pack,
            'pack_label' => $this->translatedPackLabel($pack, (string) Arr::get($config, 'meta.label', ucfirst($pack))),
            'pack_description' => (string) Arr::get($config, 'meta.description', ''),
            'recommended_for' => (string) Arr::get($config, 'meta.recommended_for', ''),
            'modules' => collect($moduleEntries)
                ->map(fn (array $entry, string $slug): array => [
                    'key' => $slug,
                    'enabled' => (bool) ($entry['enabled'] ?? false),
                    'name' => (string) ($entry['name'] ?? $slug),
                ])
                ->values()
                ->all(),
            'features' => collect($featureEntries)
                ->map(fn (bool $enabled, string $key): array => ['key' => $key, 'enabled' => $enabled])
                ->values()
                ->all(),
            'menu_visibility' => collect($menuVisibility)
                ->map(fn (bool $visible, string $key): array => ['key' => $key, 'visible' => $visible])
                ->values()
                ->all(),
            'permission_flags' => collect($permissionFlags)
                ->map(fn (bool $enabled, string $key): array => ['key' => $key, 'enabled' => $enabled])
                ->values()
                ->all(),
            'workflow_defaults' => collect((array) Arr::get($config, 'workflow_defaults', []))
                ->mapWithKeys(function (array $settings, string $module): array {
                    return [
                        $module => [
                            'label' => $this->translatedWorkflowModuleLabel($module),
                            'tabs' => collect((array) ($settings['tabs'] ?? []))
                                ->map(fn ($tab): string => $this->translatedWorkflowTabLabel((string) $tab))
                                ->values()
                                ->all(),
                            'test_tabs' => collect((array) ($settings['test_tabs'] ?? []))
                                ->map(fn ($tab): string => $this->translatedWorkflowTestTabLabel((string) $tab))
                                ->values()
                                ->all(),
                        ],
                    ];
                })
                ->all(),
            'self_service_approval' => (array) Arr::get($config, 'self_service_approval', []),
        ];
    }

    private function packValue(string $path, mixed $default = null): mixed
    {
        return Arr::get($this->config, 'packs.'.$this->activePack().'.'.$path, $default);
    }

    private function fallbackValue(string $path, mixed $default = null): mixed
    {
        return Arr::get($this->config, 'packs.corporate.'.$path, $default);
    }

    private function translatedPackLabel(string $key, string $fallback): string
    {
        $translationKey = 'admin::references.diagnostics.pack_labels.'.$key;

        return Lang::has($translationKey) ? __($translationKey) : $fallback;
    }

    private function translatedProfileLabel(string $key): string
    {
        $translationKey = 'admin::references.diagnostics.profiles.'.$key;

        return Lang::has($translationKey) ? __($translationKey) : str($key)->headline()->toString();
    }

    private function translatedWorkflowModuleLabel(string $key): string
    {
        $translationKey = 'admin::references.diagnostics.workflow_modules.'.$key;

        return Lang::has($translationKey) ? __($translationKey) : str($key)->replace('_', ' ')->headline()->toString();
    }

    private function translatedWorkflowTabLabel(string $key): string
    {
        $translationKey = 'admin::references.diagnostics.workflow_tabs.'.$key;

        return Lang::has($translationKey) ? __($translationKey) : str($key)->replace('_', ' ')->headline()->toString();
    }

    private function translatedWorkflowTestTabLabel(string $key): string
    {
        $translationKey = 'admin::references.diagnostics.workflow_test_tabs.'.$key;

        return Lang::has($translationKey) ? __($translationKey) : str($key)->replace('_', ' ')->headline()->toString();
    }
}
