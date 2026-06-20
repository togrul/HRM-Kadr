<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Module Catalog
    |--------------------------------------------------------------------------
    |
    | Define each module with a slug, its provider, whether it is enabled and
    | (optionally) where its migrations live. ModuleServiceProvider loads only
    | enabled modules from this catalog.
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
        'audit' => [
            'provider' => \App\Modules\Audit\Providers\AuditServiceProvider::class,
            'enabled' => true,
            'migrations' => app_path('Modules/Audit/Database/Migrations'),
        ],
        'compliance' => [
            'provider' => \App\Modules\Compliance\Providers\ComplianceServiceProvider::class,
            'enabled' => true,
            'migrations' => app_path('Modules/Compliance/Database/Migrations'),
        ],
        'employee-lifecycle' => [
            'provider' => \App\Modules\EmployeeLifecycle\Providers\EmployeeLifecycleServiceProvider::class,
            'enabled' => true,
            'migrations' => app_path('Modules/EmployeeLifecycle/Database/Migrations'),
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
        'attendance' => [
            'provider' => \App\Modules\Attendance\Providers\AttendanceServiceProvider::class,
            'enabled' => true,
            'migrations' => app_path('Modules/Attendance/Database/Migrations'),
        ],
        'training-needs' => [
            'provider' => \App\Modules\TrainingNeeds\Providers\TrainingNeedsServiceProvider::class,
            'enabled' => true,
            'migrations' => app_path('Modules/TrainingNeeds/Database/Migrations'),
        ],
        'performance-evaluation' => [
            'provider' => \App\Modules\PerformanceEvaluation\Providers\PerformanceEvaluationServiceProvider::class,
            'enabled' => true,
            'migrations' => app_path('Modules/PerformanceEvaluation/Database/Migrations'),
        ],
        'reports' => [
            'provider' => \App\Modules\Reports\Providers\ReportsServiceProvider::class,
            'enabled' => true,
            'migrations' => app_path('Modules/Reports/Database/Migrations'),
        ],
        'onboarding-library' => [
            'provider' => \App\Modules\OnboardingLibrary\Providers\OnboardingLibraryServiceProvider::class,
            'enabled' => true,
            'migrations' => app_path('Modules/OnboardingLibrary/Database/Migrations'),
        ],
        'learning-library' => [
            'provider' => \App\Modules\LearningLibrary\Providers\LearningLibraryServiceProvider::class,
            'enabled' => true,
            'migrations' => app_path('Modules/LearningLibrary/Database/Migrations'),
        ],
    ],
];
