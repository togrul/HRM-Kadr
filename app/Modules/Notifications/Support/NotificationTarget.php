<?php

namespace App\Modules\Notifications\Support;

/**
 * Where opening a notification takes the reader: the bell and the inbox share it.
 */
class NotificationTarget
{
    /**
     * @param  array<string,mixed>  $data
     */
    public static function route(array $data): string
    {
        return match ($data['type'] ?? 'default') {
            'Personnel', 'Birthday' => 'home',
            'Leave', 'leave' => 'leaves',
            default => 'notifications',
        };
    }
}
