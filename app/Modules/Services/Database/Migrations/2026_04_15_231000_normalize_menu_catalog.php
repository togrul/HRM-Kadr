<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        if (! app()->runningInConsole()) {
            return;
        }

        if (! Schema::hasTable('menus')) {
            return;
        }

        $definitions = collect($this->definitions());
        $permissionIds = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $definitions->pluck('permission_name')->filter()->unique()->values())
            ->pluck('id', 'name');

        $rows = DB::table('menus')
            ->select('id', 'name', 'url', 'icon', 'color', 'order', 'is_active', 'permission_id')
            ->orderBy('id')
            ->get();

        if (! $this->needsNormalization($rows, $definitions->all(), $permissionIds->all())) {
            return;
        }

        foreach ($definitions as $definition) {
            $matches = DB::table('menus')
                ->select('id', 'name', 'url', 'permission_id')
                ->orderBy('id')
                ->get()
                ->filter(fn (object $row): bool => $this->matchesDefinition($row, $definition))
                ->values();

            $payload = [
                'name' => $definition['name'],
                'icon' => $definition['icon'],
                'color' => $definition['color'],
                'order' => $definition['order'],
                'is_active' => $definition['is_active'],
                'url' => $definition['url'],
            ];

            if (filled($definition['permission_name'] ?? null)) {
                $payload['permission_id'] = $permissionIds->get($definition['permission_name']);
            }

            if ($matches->isEmpty()) {
                DB::table('menus')->insert($payload);

                continue;
            }

            $keeper = $matches->first(function (object $row) use ($definition): bool {
                return $row->name === $definition['name'] || $row->url === $definition['url'];
            }) ?? $matches->first();

            DB::table('menus')
                ->where('id', $keeper->id)
                ->update($payload);

            $duplicateIds = $matches
                ->pluck('id')
                ->reject(fn (int $id): bool => $id === (int) $keeper->id)
                ->values()
                ->all();

            if ($duplicateIds !== []) {
                DB::table('menus')->whereIn('id', $duplicateIds)->delete();
            }
        }
    }

    public function down(): void
    {
        // Data normalization is intentionally irreversible.
    }

    /**
     * @param  array<int, array{name: string, url: string, icon: string, color: string, order: int, is_active: int, permission_name: ?string, aliases: array<int, string>, route_aliases: array<int, string>}>  $definitions
     * @param  array<string, int>  $permissionIds
     */
    private function needsNormalization(object $rows, array $definitions, array $permissionIds): bool
    {
        foreach ($definitions as $definition) {
            $matches = $rows
                ->filter(fn (object $row): bool => $this->matchesDefinition($row, $definition))
                ->values();

            if ($matches->isEmpty()) {
                return true;
            }

            if ($matches->count() > 1) {
                return true;
            }

            $payload = $this->payloadFor($definition, $permissionIds);
            $keeper = $matches->first();

            foreach ($payload as $column => $expected) {
                if (($keeper->{$column} ?? null) != $expected) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array<int, array{name: string, url: string, icon: string, color: string, order: int, is_active: int, permission_name: ?string, aliases: array<int, string>, route_aliases: array<int, string>}>
     */
    private function definitions(): array
    {
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
                    'permission_name' => $this->permissionName($name),
                    'aliases' => $this->translationAliases($name),
                    'route_aliases' => $this->routeAliases($name),
                ];
            })
            ->values();

        return $definitions
            ->merge([
                [
                    'name' => 'ui::menu.items.self_service_reviews',
                    'url' => 'self-service-reviews',
                    'icon' => 'comment-icon',
                    'color' => 'zinc',
                    'order' => 15,
                    'is_active' => 1,
                    'permission_name' => 'review-self-service-requests',
                    'aliases' => $this->translationAliases('ui::menu.items.self_service_reviews'),
                    'route_aliases' => ['self-service-review', 'self_service_reviews'],
                ],
                [
                    'name' => 'ui::menu.items.onboarding_library',
                    'url' => 'onboarding-library',
                    'icon' => 'document-icon',
                    'color' => 'zinc',
                    'order' => 13,
                    'is_active' => 1,
                    'permission_name' => 'view-onboarding-library',
                    'aliases' => $this->translationAliases('ui::menu.items.onboarding_library'),
                    'route_aliases' => ['onboarding-library.index', 'onboarding'],
                ],
                [
                    'name' => 'ui::menu.items.learning_library',
                    'url' => 'learning-library',
                    'icon' => 'library-icon',
                    'color' => 'zinc',
                    'order' => 14,
                    'is_active' => 1,
                    'permission_name' => 'view-learning-library',
                    'aliases' => $this->translationAliases('ui::menu.items.learning_library'),
                    'route_aliases' => ['learning-library.index', 'learning'],
                ],
            ])
            ->all();
    }

    /**
     * @param  array{name: string, url: string, aliases: array<int, string>, route_aliases: array<int, string>}  $definition
     */
    private function matchesDefinition(object $row, array $definition): bool
    {
        $name = $this->normalizeToken((string) ($row->name ?? ''));
        $url = $this->normalizeToken((string) ($row->url ?? ''));
        $definitionName = $this->normalizeToken($definition['name']);
        $definitionUrl = $this->normalizeToken($definition['url']);

        if ($name === $definitionName) {
            return true;
        }

        foreach ($definition['aliases'] as $alias) {
            if ($name === $this->normalizeToken($alias)) {
                return true;
            }
        }

        if ($this->sharesAmbiguousUrl($definitionName, $definitionUrl)) {
            return false;
        }

        if ($url === $definitionUrl) {
            return true;
        }

        foreach ($definition['route_aliases'] as $alias) {
            if ($url === $this->normalizeToken($alias)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{name: string, url: string, icon: string, color: string, order: int, is_active: int, permission_name: ?string}  $definition
     * @param  array<string, int>  $permissionIds
     * @return array{name: string, icon: string, color: string, order: int, is_active: int, url: string, permission_id?: mixed}
     */
    private function payloadFor(array $definition, array $permissionIds): array
    {
        $payload = [
            'name' => $definition['name'],
            'icon' => $definition['icon'],
            'color' => $definition['color'],
            'order' => $definition['order'],
            'is_active' => $definition['is_active'],
            'url' => $definition['url'],
        ];

        if (filled($definition['permission_name'] ?? null)) {
            $payload['permission_id'] = $permissionIds[$definition['permission_name']] ?? null;
        }

        return $payload;
    }

    /**
     * @return array<int, string>
     */
    private function translationAliases(string $key): array
    {
        $aliases = [$key];

        foreach (['en', 'az'] as $locale) {
            $translated = Lang::get($key, [], $locale);

            if (is_string($translated) && $translated !== $key) {
                $aliases[] = $translated;
            }
        }

        return array_values(array_unique(array_filter(array_map('trim', $aliases))));
    }

    /**
     * @return array<int, string>
     */
    private function routeAliases(string $key): array
    {
        return match ($key) {
            'ui::menu.items.staff_table' => ['staff', 'staffs.index'],
            'ui::menu.items.orders' => ['orders.index', 'order'],
            'ui::menu.items.personal_affairs' => ['personnels', 'personnel', 'personal-affairs'],
            'ui::menu.items.reports' => ['report', 'reports.index'],
            'ui::menu.items.queries' => ['query', 'service', 'services'],
            'ui::menu.items.candidates' => ['candidate', 'candidates.index'],
            'ui::menu.items.vacations' => ['vacations', 'vacation'],
            'ui::menu.items.business_trips' => ['business-trips', 'business_trip', 'business_trips'],
            'ui::menu.items.time_off' => ['leaves.list', 'leave', 'time-off'],
            'ui::menu.items.my_hr' => ['my_hr'],
            'ui::menu.items.attendance' => ['attendance.index'],
            'ui::menu.items.training' => ['training', 'training-needs.index'],
            'ui::menu.items.performance' => ['performance', 'performance-evaluation.index'],
            default => [],
        };
    }

    private function permissionName(string $key): ?string
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

    private function normalizeToken(string $value): string
    {
        $value = trim($value);
        $value = str_replace('_', '-', $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return mb_strtolower($value);
    }

    private function sharesAmbiguousUrl(string $definitionName, string $definitionUrl): bool
    {
        return $definitionUrl === 'home'
            && in_array($definitionName, [
                'ui::menu.items.personal-affairs',
                'ui::menu.items.personal_affairs',
                'ui::menu.items.queries',
            ], true);
    }
};
