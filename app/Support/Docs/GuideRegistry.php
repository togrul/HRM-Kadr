<?php

namespace App\Support\Docs;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Throwable;

/**
 * The one list of modules the in-app guide (/guide) covers: its sidebar, the lazy section
 * endpoint and the "İstifadə təlimatı" link at the bottom of every context panel all read it.
 * Adding a module's guide = one entry here + its markdown file under docs/scenario/.
 */
class GuideRegistry
{
    /**
     * key => label, sidebar tone and icon (Material Symbols), markdown file, the module's own
     * route (the guide's "Modulu aç" link) and the route-name patterns (Str::is) of its pages.
     * Modules with a hand-built partial (docs/partials/guide-<key>) set `partial` => true.
     *
     * @var array<string, array{label:string,tone:string,icon:string,markdown:string,route:?string,routes:list<string>,partial?:bool}>
     */
    private const MODULES = [
        'home' => ['label' => 'Ana səhifə', 'tone' => 'zinc', 'icon' => 'home', 'markdown' => 'home-user-guide.md', 'route' => 'home', 'routes' => ['home']],
        'personnel' => ['label' => 'Əməkdaşlar', 'tone' => 'sky', 'icon' => 'badge', 'markdown' => 'personnel-user-guide.md', 'route' => 'personnel.index', 'routes' => ['personnel.*']],
        'staff' => ['label' => 'Ştat cədvəli', 'tone' => 'indigo', 'icon' => 'account_tree', 'markdown' => 'staff-user-guide.md', 'route' => 'staffs', 'routes' => ['staffs*']],
        'orders' => ['label' => 'Əmrlər', 'tone' => 'amber', 'icon' => 'gavel', 'markdown' => 'orders-user-guide.md', 'route' => 'orders', 'routes' => ['orders*'], 'partial' => true],
        'vacations' => ['label' => 'Məzuniyyət', 'tone' => 'emerald', 'icon' => 'beach_access', 'markdown' => 'vacation-user-guide.md', 'route' => 'vacations.list', 'routes' => ['vacations*']],
        'leaves' => ['label' => 'İcazələr', 'tone' => 'cyan', 'icon' => 'event_available', 'markdown' => 'leaves-user-guide.md', 'route' => 'leaves', 'routes' => ['leaves*']],
        'business-trips' => ['label' => 'Ezamiyyətlər', 'tone' => 'violet', 'icon' => 'flight_takeoff', 'markdown' => 'business-trips-user-guide.md', 'route' => 'business-trips.list', 'routes' => ['business-trips*']],
        'attendance' => ['label' => 'Davamiyyət', 'tone' => 'indigo', 'icon' => 'schedule', 'markdown' => 'attendance-user-guide.md', 'route' => 'attendance', 'routes' => ['attendance*'], 'partial' => true],
        'candidates' => ['label' => 'Namizədlər', 'tone' => 'rose', 'icon' => 'person_search', 'markdown' => 'candidates-user-guide.md', 'route' => 'candidates', 'routes' => ['candidates*']],
        'employee-lifecycle' => ['label' => 'Əməkdaş həyat dövrü', 'tone' => 'emerald', 'icon' => 'autorenew', 'markdown' => 'employee-lifecycle-user-guide.md', 'route' => 'employee-lifecycle', 'routes' => ['employee-lifecycle*']],
        'compliance' => ['label' => 'Sənəd uyğunluğu', 'tone' => 'amber', 'icon' => 'verified', 'markdown' => 'compliance-user-guide.md', 'route' => 'document-compliance', 'routes' => ['document-compliance*']],
        'performance' => ['label' => 'Performans qiymətləndirməsi', 'tone' => 'emerald', 'icon' => 'analytics', 'markdown' => 'performance-evaluation-user-guide.md', 'route' => 'performance-evaluation', 'routes' => ['performance-evaluation*'], 'partial' => true],
        'training' => ['label' => 'Təlim ehtiyacı', 'tone' => 'sky', 'icon' => 'school', 'markdown' => 'training-needs-user-guide.md', 'route' => 'training-needs', 'routes' => ['training-needs*'], 'partial' => true],
        'learning-library' => ['label' => 'Tədris kitabxanası', 'tone' => 'emerald', 'icon' => 'library_books', 'markdown' => 'learning-library-user-guide.md', 'route' => 'learning-library', 'routes' => ['learning-library*'], 'partial' => true],
        'onboarding-library' => ['label' => 'Adaptasiya kitabxanası', 'tone' => 'amber', 'icon' => 'menu_book', 'markdown' => 'onboarding-library-user-guide.md', 'route' => 'onboarding-library', 'routes' => ['onboarding-library*'], 'partial' => true],
        'my-hr' => ['label' => 'Şəxsi kabinet', 'tone' => 'cyan', 'icon' => 'account_circle', 'markdown' => 'my-hr-user-guide.md', 'route' => 'my-hr', 'routes' => ['my-hr*', 'self-service-reviews*'], 'partial' => true],
        'professional-portfolio' => ['label' => 'Peşəkar portfel', 'tone' => 'violet', 'icon' => 'work_history', 'markdown' => 'professional-portfolio-user-guide.md', 'route' => null, 'routes' => [], 'partial' => true],
        'compensation' => ['label' => 'Kompensasiya', 'tone' => 'sky', 'icon' => 'payments', 'markdown' => 'compensation-user-guide.md', 'route' => 'compensation', 'routes' => ['compensation*']],
        'payroll' => ['label' => 'Əmək haqqı', 'tone' => 'emerald', 'icon' => 'request_quote', 'markdown' => 'payroll-user-guide.md', 'route' => 'payroll', 'routes' => ['payroll*']],
        'reports' => ['label' => 'Hesabatlar', 'tone' => 'indigo', 'icon' => 'bar_chart', 'markdown' => 'reports-user-guide.md', 'route' => 'reports', 'routes' => ['reports*']],
        'audit' => ['label' => 'Audit jurnalı', 'tone' => 'zinc', 'icon' => 'policy', 'markdown' => 'audit-user-guide.md', 'route' => 'audit.logs', 'routes' => ['audit.*']],
        'notifications' => ['label' => 'Bildirişlər', 'tone' => 'rose', 'icon' => 'notifications', 'markdown' => 'notifications-module-guide.md', 'route' => 'notifications', 'routes' => ['notifications*'], 'partial' => true],
        'settings' => ['label' => 'Tənzimləmələr', 'tone' => 'zinc', 'icon' => 'settings', 'markdown' => 'settings-user-guide.md', 'route' => 'services', 'routes' => ['services*']],
        'account' => ['label' => 'Profilim', 'tone' => 'zinc', 'icon' => 'person', 'markdown' => 'account-user-guide.md', 'route' => 'profile.edit', 'routes' => ['profile.*']],
    ];

    /** @var array<string, ?string> resolved guide key per request path */
    private static array $pageCache = [];

    /**
     * Modules whose guide file exists, in menu order.
     *
     * @return array<string, array{label:string,tone:string,icon:string,markdown:string,route:?string,routes:list<string>,partial?:bool}>
     */
    public static function modules(): array
    {
        return array_filter(self::MODULES, fn (array $module): bool => is_file(self::markdownPath($module['markdown'])));
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::modules());
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, self::modules());
    }

    /** @return array{label:string,tone:string,icon:string,markdown:string,route:?string,routes:list<string>,partial?:bool}|null */
    public static function get(string $key): ?array
    {
        return self::modules()[$key] ?? null;
    }

    public static function markdownPath(string $file): string
    {
        return base_path('docs/scenario/'.$file);
    }

    /**
     * The guide module of the page being viewed. On a Livewire update request the current route
     * is Livewire's own endpoint, so the page is resolved from the original URL instead.
     */
    public static function forCurrentPage(): ?string
    {
        $request = request();
        $url = Livewire::isLivewireRequest() ? Livewire::originalUrl() : $request->fullUrl();
        $path = (string) parse_url((string) $url, PHP_URL_PATH);

        if (array_key_exists($path, self::$pageCache)) {
            return self::$pageCache[$path];
        }

        $name = Livewire::isLivewireRequest() ? self::routeNameFor((string) $url) : $request->route()?->getName();

        return self::$pageCache[$path] = self::forRoute($name);
    }

    public static function forRoute(?string $routeName): ?string
    {
        if ($routeName === null || $routeName === '') {
            return null;
        }

        foreach (self::modules() as $key => $module) {
            if (Str::is($module['routes'], $routeName)) {
                return $key;
            }
        }

        return null;
    }

    private static function routeNameFor(string $url): ?string
    {
        try {
            return app('router')->getRoutes()->match(Request::create($url))->getName();
        } catch (Throwable) {
            return null;
        }
    }
}
