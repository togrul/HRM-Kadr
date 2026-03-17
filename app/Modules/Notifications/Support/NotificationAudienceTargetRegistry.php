<?php

namespace App\Modules\Notifications\Support;

final class NotificationAudienceTargetRegistry
{
    private const DEFINITIONS = [
        'all_employees' => 'all_employees',
        'employee' => 'employee',
        'same_structure' => 'same_structure',
        'hr' => 'hr',
        'admins' => 'admins',
        'direct_manager' => 'direct_manager',
        'department' => 'department',
        'specific_users' => 'specific_users',
        'notification_permission' => 'notification_permission',
    ];

    private const CONTEXTS = [
        'rules' => [
            'all_employees',
            'employee',
            'same_structure',
            'hr',
            'admins',
            'direct_manager',
            'department',
            'specific_users',
            'notification_permission',
        ],
        'announcements' => [
            'all_employees',
            'hr',
            'admins',
            'direct_manager',
            'department',
            'specific_users',
        ],
    ];

    public static function definitionsFor(string $context): array
    {
        $definitions = [];

        foreach (self::keysFor($context) as $target) {
            $definitions[$target] = [
                'label' => __('notifications::common.audience_targets.'.$target.'.label'),
                'description' => __('notifications::common.audience_targets.'.$target.'.description'),
            ];
        }

        return $definitions;
    }

    public static function keysFor(string $context): array
    {
        return self::CONTEXTS[$context] ?? [];
    }

    public static function normalize(string|array|null $value, string $context): array
    {
        $allowed = self::keysFor($context);
        $selected = self::parse($value);

        return collect($allowed)
            ->filter(fn (string $target) => in_array($target, $selected, true))
            ->values()
            ->all();
    }

    public static function invalid(string|array|null $value, string $context): array
    {
        $allowed = self::keysFor($context);

        return collect(self::parse($value))
            ->reject(fn (string $target) => in_array($target, $allowed, true))
            ->values()
            ->all();
    }

    public static function implodeNormalized(string|array|null $value, string $context): string
    {
        return implode(', ', self::normalize($value, $context));
    }

    public static function label(string $target): string
    {
        if (! array_key_exists($target, self::DEFINITIONS)) {
            return $target;
        }

        return __('notifications::common.audience_targets.'.$target.'.label');
    }

    private static function parse(string|array|null $value): array
    {
        return collect(is_array($value) ? $value : explode(',', (string) $value))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
