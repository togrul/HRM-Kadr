<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enabled Modules
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
];
