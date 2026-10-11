<?php

namespace App\Modules\Personnel\Support\MyHr;

use App\Services\Modules\ModuleState;

class MyHrTabs
{
    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        $tabs = [
            'overview',
            'requests',
            'notifications',
            'onboarding',
            'development-plan',
            'learning',
            'documents',
            'payslips',
            'hierarchy',
        ];

        // İşçi münasibətləri modulu söndürüləndə onun bölməsi (və Livewire komponenti) yoxdur.
        if (app(ModuleState::class)->enabled('employee-relations')) {
            $tabs[] = 'employee-relations';
        }

        // İmtiyazlar: şirkət növünə/admin seçiminə görə bağlıdırsa ModuleState-də də bağlı görünür.
        if (app(ModuleState::class)->enabled('benefits')) {
            $tabs[] = 'benefits';
        }

        return $tabs;
    }
}
