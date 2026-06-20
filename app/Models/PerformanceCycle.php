<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PerformanceCycle extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'name',
        'cycle_type',
        'period_start',
        'period_end',
        'status',
        'auto_generate_forms',
        'description',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'auto_generate_forms' => 'boolean',
    ];

    public function forms(): HasMany
    {
        return $this->hasMany(PerformanceForm::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('performance_cycle')
            ->logFillable()
            ->logOnlyDirty();
    }
}
