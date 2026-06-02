<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity;

class AuditActivity extends Activity
{
    public function getConnectionName()
    {
        return config('activitylog.database_connection') ?: parent::getConnectionName();
    }
}
