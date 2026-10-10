<?php

namespace App\Contracts;

use App\Models\AuditActivity;
use Illuminate\Database\Eloquent\Builder;

interface VisibleAuditActivities
{
    /** @return Builder<AuditActivity> */
    public function visibleQuery(): Builder;
}
