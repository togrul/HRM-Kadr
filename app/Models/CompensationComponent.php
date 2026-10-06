<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CompensationComponent extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'code',
        'name',
        'type',
        'calc_type',
        'taxable',
        'affects_social',
        'is_statutory',
        'gl_code',
        'sort',
        'is_active',
    ];

    protected $casts = [
        'taxable' => 'boolean',
        'affects_social' => 'boolean',
        'is_statutory' => 'boolean',
        'is_active' => 'boolean',
        'sort' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('compensation_component')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
