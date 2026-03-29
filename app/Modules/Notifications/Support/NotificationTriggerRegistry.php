<?php

namespace App\Modules\Notifications\Support;

final class NotificationTriggerRegistry
{
    private const DEFINITIONS = [
        'birthday_due' => [
            'category' => 'birthday',
            'kind' => 'system',
            'default' => true,
        ],
        'birthday_reminder' => [
            'category' => 'birthday',
            'kind' => 'scheduled',
            'default' => false,
        ],
        'position_changed' => [
            'category' => 'position_change',
            'kind' => 'system',
            'default' => true,
        ],
        'employment_started' => [
            'category' => 'employment_started',
            'kind' => 'system',
            'default' => true,
        ],
        'holiday_due' => [
            'category' => 'holiday',
            'kind' => 'system',
            'default' => true,
        ],
        'holiday_reminder' => [
            'category' => 'holiday',
            'kind' => 'scheduled',
            'default' => false,
        ],
        'manual_holiday' => [
            'category' => 'holiday',
            'kind' => 'manual',
            'default' => false,
        ],
        'announcement_published' => [
            'category' => 'announcement',
            'kind' => 'system',
            'default' => true,
        ],
        'announcement_scheduled' => [
            'category' => 'announcement',
            'kind' => 'scheduled',
            'default' => false,
        ],
        'manual_announcement' => [
            'category' => 'announcement',
            'kind' => 'manual',
            'default' => false,
        ],
        'training_result_published' => [
            'category' => 'training_result',
            'kind' => 'system',
            'default' => true,
        ],
        'leave_status_changed' => [
            'category' => 'leave_status',
            'kind' => 'system',
            'default' => true,
        ],
    ];

    public static function categories(): array
    {
        $categories = [];

        foreach (self::DEFINITIONS as $definition) {
            $category = $definition['category'];

            if (! in_array($category, $categories, true)) {
                $categories[] = $category;
            }
        }

        return $categories;
    }

    public static function label(string $trigger): string
    {
        return __('notifications::common.triggers.'.$trigger);
    }

    public static function optionsForCategory(string $category): array
    {
        $options = [];

        foreach (self::DEFINITIONS as $trigger => $definition) {
            if ($definition['category'] !== $category) {
                continue;
            }

            $options[$trigger] = self::label($trigger);
        }

        return $options;
    }

    public static function firstForCategory(string $category, string $kind = 'system'): ?string
    {
        foreach (self::DEFINITIONS as $trigger => $definition) {
            if ($definition['category'] !== $category) {
                continue;
            }

            if (($definition['kind'] ?? 'system') === $kind && ($definition['default'] ?? false)) {
                return $trigger;
            }
        }

        foreach (self::DEFINITIONS as $trigger => $definition) {
            if ($definition['category'] === $category) {
                return $trigger;
            }
        }

        return null;
    }

    public static function trigger(string $category, string $kind = 'system'): ?string
    {
        foreach (self::DEFINITIONS as $trigger => $definition) {
            if ($definition['category'] !== $category) {
                continue;
            }

            if (($definition['kind'] ?? 'system') === $kind) {
                return $trigger;
            }
        }

        return self::firstForCategory($category);
    }
}
