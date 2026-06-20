<?php

namespace App\Modules\Reports\Application\Services;

use Illuminate\Auth\Access\AuthorizationException;

class ReportsAccessService
{
    /**
     * @throws AuthorizationException
     */
    public function authorizeView(): void
    {
        abort_unless(
            auth()->user()?->canAny(['show-reports', 'export-reports']),
            403
        );
    }

    /**
     * @throws AuthorizationException
     */
    public function authorizeExport(): void
    {
        abort_unless(
            auth()->user()?->can('export-reports'),
            403
        );
    }
}
