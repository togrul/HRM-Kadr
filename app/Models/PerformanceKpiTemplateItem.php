<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PerformanceKpiTemplateItem extends Model
{
    use LogsActivity;

    protected $fillable = [
        'performance_kpi_template_id',
        'performance_kpi_id',
        'weight',
        'target',
        'range_min',
        'range_max',
        'threshold',
        'stretch',
        'cap',
        'target_editable',
        'sort_order',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'target' => 'decimal:4',
        'range_min' => 'decimal:4',
        'range_max' => 'decimal:4',
        'threshold' => 'decimal:4',
        'stretch' => 'decimal:4',
        'cap' => 'decimal:4',
        'target_editable' => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(PerformanceKpiTemplate::class, 'performance_kpi_template_id');
    }

    public function kpi(): BelongsTo
    {
        return $this->belongsTo(PerformanceKpi::class, 'performance_kpi_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('performance_kpi_template')
            ->logFillable()
            ->logOnlyDirty();
    }
}
