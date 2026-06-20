<?php

namespace App\Modules\Personnel\Support\MyHr;

class MyHrTabs
{
    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            'overview',
            'requests',
            'notifications',
            'onboarding',
            'development-plan',
            'learning',
            'documents',
            'hierarchy',
        ];
    }
}
