<?php

namespace App\Support\Navigation;

use App\Support\Translations\ModuleTranslation;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;

class MenuPresentation
{
    public static function label(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '—';
        }

        $resolved = ModuleTranslation::resolveStoredText($value);

        if ($resolved !== $value) {
            return $resolved;
        }

        $mapped = self::literalMap()[$value] ?? null;

        if (is_string($mapped) && Lang::has($mapped)) {
            return __($mapped);
        }

        return $value;
    }

    public static function iconComponent(object $menu): string
    {
        $definition = self::definition($menu);
        $icon = is_array($definition)
            ? (string) ($definition['icon'] ?? 'document-icon')
            : self::normalizeIcon((string) ($menu->icon ?? 'document-icon'));

        return 'icons.'.$icon;
    }

    public static function moduleName(string $routeBase): string
    {
        return [
            'home' => 'personnel',
            'my-hr' => 'personnel',
            'self-service-reviews' => 'personnel',
            'onboarding-library' => 'onboarding-library',
            'learning-library' => 'learning-library',
            'audit.logs' => 'audit',
            'document-compliance' => 'compliance',
            'employee-lifecycle' => 'employee-lifecycle',
            'staffs' => 'staff',
            'vacations.list' => 'vacation',
            'business-trips.list' => 'business-trips',
        ][$routeBase] ?? $routeBase;
    }

    public static function hasRoute(string $routeName): bool
    {
        $routeName = trim($routeName);

        if ($routeName === '' || $routeName === '#') {
            return true;
        }

        return Route::has($routeName);
    }

    public static function route(string $routeName): string
    {
        return self::hasRoute($routeName) && $routeName !== '#'
            ? route($routeName)
            : '#';
    }

    public static function canonicalKey(object $menu): ?string
    {
        return self::definition($menu)['name'] ?? null;
    }

    public static function routeBase(object $menu): string
    {
        return self::definition($menu)['url'] ?? trim((string) ($menu->url ?? ''));
    }

    public static function permissionName(object $menu): ?string
    {
        return self::definition($menu)['permission_name']
            ?? $menu->permission?->name
            ?? null;
    }

    public static function railLabel(object $menu): string
    {
        $definition = self::definition($menu);

        if (is_array($definition) && Lang::has((string) $definition['name'])) {
            return __((string) $definition['name']);
        }

        return self::label((string) ($menu->name ?? ''));
    }

    public static function visibleInRail(object $menu): bool
    {
        $definition = self::definition($menu);

        return is_array($definition)
            && (bool) ($definition['is_active'] ?? false)
            && self::hasRoute((string) ($definition['url'] ?? ''));
    }

    /**
     * @return array<string, string>
     */
    protected static function literalMap(): array
    {
        return [
            'Staff table' => 'ui::menu.items.staff_table',
            'Orders' => 'ui::menu.items.orders',
            'Personal affairs' => 'ui::menu.items.personal_affairs',
            'Reports' => 'ui::menu.items.reports',
            'Queries' => 'ui::menu.items.queries',
            'Candidates' => 'ui::menu.items.candidates',
            'Vacations' => 'ui::menu.items.vacations',
            'Business trips' => 'ui::menu.items.business_trips',
            'Time off' => 'ui::menu.items.time_off',
            'Attendance' => 'ui::menu.items.attendance',
            'Training' => 'ui::menu.items.training',
            'Performance' => 'ui::menu.items.performance',
            'My HR' => 'ui::menu.items.my_hr',
            'Self-service review' => 'ui::menu.items.self_service_reviews',
            'SELF-SERVICE REVIEW' => 'ui::menu.items.self_service_reviews',
            'Onboarding library' => 'ui::menu.items.onboarding_library',
            'Learning library' => 'ui::menu.items.learning_library',
            'Audit log' => 'ui::menu.items.audit_logs',
            'Document compliance' => 'ui::menu.items.document_compliance',
            'Employee lifecycle' => 'ui::menu.items.employee_lifecycle',
            'Ştat cədvəli' => 'ui::menu.items.staff_table',
            'Əmrlər' => 'ui::menu.items.orders',
            'Şəxsi işlər' => 'ui::menu.items.personal_affairs',
            'Hesabatlar' => 'ui::menu.items.reports',
            'Sorğular' => 'ui::menu.items.queries',
            'Namizədlər' => 'ui::menu.items.candidates',
            'Məzuniyyətlər' => 'ui::menu.items.vacations',
            'Ezamiyyətlər' => 'ui::menu.items.business_trips',
            'İcazələr' => 'ui::menu.items.time_off',
            'Şəxsi kabinet' => 'ui::menu.items.my_hr',
            'Şəxsi kabinet müraciətləri' => 'ui::menu.items.self_service_reviews',
            'Uyğunlaşma kitabxanası' => 'ui::menu.items.onboarding_library',
            'Öyrənmə kitabxanası' => 'ui::menu.items.learning_library',
            'Davamiyyət' => 'ui::menu.items.attendance',
            'Təlim' => 'ui::menu.items.training',
            'Performans' => 'ui::menu.items.performance',
            'Audit jurnalı' => 'ui::menu.items.audit_logs',
            'Sənəd uyğunluğu' => 'ui::menu.items.document_compliance',
            'Əməkdaş həyat dövrü' => 'ui::menu.items.employee_lifecycle',
        ];
    }

    /**
     * @return array<string, array{name: string, url: string, icon: string, is_active: int, permission_name: ?string, aliases: array<int, string>, route_aliases: array<int, string>}>
     */
    protected static function definitions(): array
    {
        static $definitions;

        if (is_array($definitions)) {
            return $definitions;
        }

        $definitions = collect((array) config('menus.global', []))
            ->mapWithKeys(function (array $menu): array {
                $name = (string) ($menu['name'] ?? '');

                if ($name === '') {
                    return [];
                }

                return [
                    $name => [
                        'name' => $name,
                        'url' => (string) ($menu['url'] ?? ''),
                        'icon' => self::normalizeIcon((string) ($menu['icon'] ?? 'document-icon')),
                        'is_active' => (int) ($menu['is_active'] ?? 1),
                        'permission_name' => self::permissionNameForKey($name),
                        'aliases' => self::translationAliases($name),
                        'route_aliases' => self::routeAliases($name),
                    ],
                ];
            })
            ->all();

        foreach ([
            'ui::menu.items.self_service_reviews' => [
                'url' => 'self-service-reviews',
                'icon' => 'comment-icon',
                'is_active' => 1,
                'permission_name' => 'review-self-service-requests',
                'route_aliases' => ['self-service-review', 'self_service_reviews'],
            ],
            'ui::menu.items.onboarding_library' => [
                'url' => 'onboarding-library',
                'icon' => 'document-icon',
                'is_active' => 1,
                'permission_name' => 'view-onboarding-library',
                'route_aliases' => ['onboarding-library.index', 'onboarding'],
            ],
            'ui::menu.items.learning_library' => [
                'url' => 'learning-library',
                'icon' => 'library-icon',
                'is_active' => 1,
                'permission_name' => 'view-learning-library',
                'route_aliases' => ['learning-library.index', 'learning'],
            ],
        ] as $name => $menu) {
            $definitions[$name] = [
                'name' => $name,
                'url' => $menu['url'],
                'icon' => self::normalizeIcon($menu['icon']),
                'is_active' => $menu['is_active'],
                'permission_name' => $menu['permission_name'],
                'aliases' => self::translationAliases($name),
                'route_aliases' => $menu['route_aliases'],
            ];
        }

        return $definitions;
    }

    protected static function definition(object $menu): ?array
    {
        $definitions = self::definitions();
        $url = trim((string) ($menu->url ?? ''));
        $name = trim((string) ($menu->name ?? ''));
        $normalizedUrl = self::normalizeToken($url);
        $normalizedName = self::normalizeToken($name);

        foreach ($definitions as $definition) {
            $routeAliases = array_map(
                static fn (string $alias): string => self::normalizeToken($alias),
                array_merge([$definition['url']], $definition['route_aliases'])
            );

            if ($url !== '' && in_array($normalizedUrl, $routeAliases, true)) {
                return $definition;
            }

            $nameAliases = array_map(
                static fn (string $alias): string => self::normalizeToken($alias),
                array_merge([$definition['name']], $definition['aliases'])
            );

            if ($name !== '' && in_array($normalizedName, $nameAliases, true)) {
                return $definition;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    protected static function translationAliases(string $key): array
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
    protected static function routeAliases(string $key): array
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
            'ui::menu.items.audit_logs' => ['audit-logs', 'audit-log'],
            'ui::menu.items.document_compliance' => ['document-compliance.index', 'compliance', 'document_compliance'],
            'ui::menu.items.employee_lifecycle' => ['employee-lifecycle.index', 'lifecycle', 'employee_lifecycle'],
            default => [],
        };
    }

    protected static function normalizeToken(string $value): string
    {
        $value = trim($value);
        $value = str_replace('_', '-', $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return mb_strtolower($value);
    }

    protected static function normalizeIcon(string $icon): string
    {
        $icon = trim($icon);

        if ($icon === '' || str_contains($icon, '<svg')) {
            return 'document-icon';
        }

        if (str_starts_with($icon, 'icons.')) {
            $icon = substr($icon, 6);
        }

        return preg_match('/^[a-z0-9-]+$/', $icon) === 1
            ? $icon
            : 'document-icon';
    }

    protected static function permissionNameForKey(string $key): ?string
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
            'ui::menu.items.audit_logs' => 'show-audit-logs',
            'ui::menu.items.document_compliance' => 'show-document-compliance',
            'ui::menu.items.employee_lifecycle' => 'show-employee-lifecycle',
            'ui::menu.items.self_service_reviews' => 'review-self-service-requests',
            'ui::menu.items.onboarding_library' => 'view-onboarding-library',
            'ui::menu.items.learning_library' => 'view-learning-library',
            default => null,
        };
    }
}
