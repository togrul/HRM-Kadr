<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $key
 * @property string $locale
 * @property string $subject
 * @property string $body
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
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
