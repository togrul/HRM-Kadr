<?php

use App\Models\Menu;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        if (! app()->runningInConsole() || ! Schema::hasTable('menus')) {
            return;
        }

        $definitions = collect((array) config('menus.global', []))
            ->map(function (array $menu): array {
                $name = (string) ($menu['name'] ?? '');

                return [
                    'name' => $name,
                    'url' => (string) ($menu['url'] ?? ''),
                    'icon' => (string) ($menu['icon'] ?? 'document-icon'),
                    'color' => (string) ($menu['color'] ?? 'zinc'),
                    'order' => (int) ($menu['order'] ?? 0),
                    'is_active' => (int) ($menu['is_active'] ?? 1),
                    'permission_name' => $this->permissionNameFor($name),
                ];
            })
            ->merge([
                [
                    'name' => 'ui::menu.items.self_service_reviews',
                    'url' => 'self-service-reviews',
                    'icon' => 'comment-icon',
                    'color' => 'zinc',
                    'order' => 15,
                    'is_active' => 1,
                    'permission_name' => 'review-self-service-requests',
                ],
                [
                    'name' => 'ui::menu.items.onboarding_library',
                    'url' => 'onboarding-library',
                    'icon' => 'document-icon',
                    'color' => 'zinc',
                    'order' => 13,
                    'is_active' => 1,
                    'permission_name' => 'view-onboarding-library',
                ],
                [
                    'name' => 'ui::menu.items.learning_library',
                    'url' => 'learning-library',
                    'icon' => 'library-icon',
                    'color' => 'zinc',
                    'order' => 14,
                    'is_active' => 1,
                    'permission_name' => 'view-learning-library',
                ],
            ]);

        $permissionIds = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $definitions->pluck('permission_name')->filter()->values())
            ->pluck('id', 'name');

        foreach ($definitions as $definition) {
            $query = Menu::query()->where('name', $definition['name']);

            if (! $this->sharesAmbiguousUrl($definition['name'], $definition['url'])) {
                $query->orWhere('url', $definition['url']);
            }

            $menu = $query->orderBy('id')->first();

            $payload = [
                'name' => $definition['name'],
                'url' => $definition['url'],
                'icon' => $definition['icon'],
                'color' => $definition['color'],
                'order' => $definition['order'],
                'is_active' => $definition['is_active'],
                'permission_id' => filled($definition['permission_name'])
                    ? $permissionIds->get($definition['permission_name'])
                    : null,
            ];

            if ($menu) {
                $menu->update($payload);
                continue;
            }

            Menu::query()->create($payload);
        }
    }

    public function down(): void
    {
        // Backfill migration is intentionally irreversible.
    }

    private function permissionNameFor(string $key): ?string
    {
        return match ($key) {
            'ui::menu.items.staff_table' => 'show-staff',
            'ui::menu.items.orders' => 'show-orders',
            'ui::menu.items.personal_affairs' => 'show-personnels',
            'ui::menu.items.reports' => 'show-reports',
            'ui::menu.items.candidates' => 'show-candidates',
            'ui::menu.items.vacations' => 'show-vacations',
            'ui::menu.items.business_trips' => 'show-business_trips',
            'ui::menu.items.time_off' => 'show-leaves',
            'ui::menu.items.my_hr' => 'show-my-hr',
            'ui::menu.items.attendance' => 'show-attendance',
            'ui::menu.items.training' => 'show-training-needs',
            'ui::menu.items.performance' => 'show-performance-evaluation',
            default => null,
        };
    }

    private function sharesAmbiguousUrl(string $name, string $url): bool
    {
        return $url === 'home'
            && in_array($name, [
                'ui::menu.items.personal_affairs',
                'ui::menu.items.queries',
            ], true);
    }
};
