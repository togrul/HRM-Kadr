<?php

namespace App\Support\Navigation;

use Illuminate\Support\Facades\Lang;
use App\Support\Translations\ModuleTranslation;

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
        return match ((string) ($menu->url ?? '')) {
            'my-hr' => 'icons.my-hr-icon',
            'self-service-reviews' => 'icons.self-service-review-icon',
            'onboarding-library' => 'icons.onboarding-library-icon',
            'learning-library' => 'icons.learning-library-icon',
            default => 'icons.' . ($menu->icon ?? 'document-icon'),
        };
    }

    public static function moduleName(string $routeBase): string
    {
        return [
            'home' => 'personnel',
            'my-hr' => 'personnel',
            'self-service-reviews' => 'personnel',
            'onboarding-library' => 'onboarding-library',
            'learning-library' => 'learning-library',
            'staffs' => 'staff',
            'vacations.list' => 'vacation',
            'business-trips.list' => 'business-trips',
        ][$routeBase] ?? $routeBase;
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
        ];
    }
}
