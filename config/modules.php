<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enabled Modules (legacy list)
    |--------------------------------------------------------------------------
    |
    | List the Service Providers that represent your modules. Keep this empty
    | until you are ready to activate a module. Providers can live under
    | App\Modules\<Name>\Providers\<Name>ServiceProvider.
    |
    */
    'enabled' => [
        \App\Modules\Personnel\Providers\PersonnelServiceProvider::class,
        \App\Modules\Orders\Providers\OrdersServiceProvider::class,
        \App\Modules\Staff\Providers\StaffServiceProvider::class,
        \App\Modules\Candidates\Providers\CandidatesServiceProvider::class,
        \App\Modules\Leaves\Providers\LeavesServiceProvider::class,
        \App\Modules\BusinessTrips\Providers\BusinessTripsServiceProvider::class,
        \App\Modules\Vacation\Providers\VacationServiceProvider::class,
        \App\Modules\Admin\Providers\AdminServiceProvider::class,
        \App\Modules\Services\Providers\ServicesServiceProvider::class,
        \App\Modules\Notifications\Providers\NotificationsServiceProvider::class,
        \App\Modules\SidebarStructure\Providers\SidebarStructureServiceProvider::class,
        \App\Modules\UI\Providers\UIServiceProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Module Catalog (preferred)
    |--------------------------------------------------------------------------
    |
    | Define each module with a slug, its provider, whether it is enabled,
    | and (optionally) where its migrations live. ModuleServiceProvider will
    | load only the enabled modules. Keep the legacy "enabled" list for
    | backward compatibility while migrating.
    |
    */
    'catalog' => [
        'personnel' => [
            'provider' => \App\Modules\Personnel\Providers\PersonnelServiceProvider::class,
            'enabled' => true,
            'migrations' => app_path('Modules/Personnel/Database/Migrations'),
        ],
        'orders' => [
            'provider' => \App\Modules\Orders\Providers\OrdersServiceProvider::class,
            'enabled' => true,
            'migrations' => app_path('Modules/Orders/Database/Migrations'),
        ],
        'staff' => [
            'provider' => \App\Modules\Staff\Providers\StaffServiceProvider::class,
            'enabled' => true,
            'migrations' => app_path('Modules/Staff/Database/Migrations'),
        ],
        'candidates' => [
            'provider' => \App\Modules\Candidates\Providers\CandidatesServiceProvider::class,
            'enabled' => true,
            'migrations' => app_path('Modules/Candidates/Database/Migrations'),
        ],
        'leaves' => [
            'provider' => \App\Modules\Leaves\Providers\LeavesServiceProvider::class,
            'enabled' => true,
            'migrations' => app_path('Modules/Leaves/Database/Migrations'),
        ],
        'business-trips' => [
            'provider' => \App\Modules\BusinessTrips\Providers\BusinessTripsServiceProvider::class,
            'enabled' => true,
            'migrations' => app_path('Modules/BusinessTrips/Database/Migrations'),
        ],
        'vacation' => [
            'provider' => \App\Modules\Vacation\Providers\VacationServiceProvider::class,
            'enabled' => true,
            'migrations' => app_path('Modules/Vacation/Database/Migrations'),
        ],
        'admin' => [
            'provider' => \App\Modules\Admin\Providers\AdminServiceProvider::class,
            'enabled' => true,
            'migrations' => app_path('Modules/Admin/Database/Migrations'),
        ],
        'services' => [
            'provider' => \App\Modules\Services\Providers\ServicesServiceProvider::class,
            'enabled' => true,
            'migrations' => app_path('Modules/Services/Database/Migrations'),
        ],
        'notifications' => [
            'provider' => \App\Modules\Notifications\Providers\NotificationsServiceProvider::class,
            'enabled' => true,
            'migrations' => app_path('Modules/Notifications/Database/Migrations'),
        ],
        'structure' => [
            'provider' => \App\Modules\SidebarStructure\Providers\SidebarStructureServiceProvider::class,
            'enabled' => true,
            'migrations' => app_path('Modules/SidebarStructure/Database/Migrations'),
        ],
        'ui' => [
            'provider' => \App\Modules\UI\Providers\UIServiceProvider::class,
            'enabled' => true,
            'migrations' => app_path('Modules/UI/Database/Migrations'),
        ],
    ],
];
