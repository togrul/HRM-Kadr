<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PerformanceNotificationTemplate extends Model
{
    use LogsActivity;

    protected $fillable = ['key', 'locale', 'subject', 'body', 'updated_by'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('performance_notification_template')
            ->logFillable()
            ->logOnlyDirty();
    }
}
