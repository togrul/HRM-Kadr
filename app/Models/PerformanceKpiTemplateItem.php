<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $performance_kpi_template_id
 * @property int $performance_kpi_id
 * @property float|string $weight
 * @property float|string|null $target
 * @property float|string|null $range_min
 * @property float|string|null $range_max
 * @property float|string|null $threshold
 * @property float|string|null $stretch
 * @property float|string|null $cap
 * @property bool $target_editable
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
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

    /** @return BelongsTo<PerformanceKpiTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(PerformanceKpiTemplate::class, 'performance_kpi_template_id');
    }

    /** @return BelongsTo<PerformanceKpi, $this> */
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
