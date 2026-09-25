<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $name
 * @property string|null $code
 * @property string $period_type
 * @property float|string $kpi_weight_share
 * @property float|string $competency_weight_share
 * @property int|null $performance_form_template_id
 * @property string $status
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class PerformanceKpiTemplate extends Model
{
    use LogsActivity;
    use SoftDeletes;

    public const PERIOD_TYPES = ['monthly', 'quarterly', 'semiannual', 'annual'];

    protected $fillable = [
        'name',
        'code',
        'period_type',
        'kpi_weight_share',
        'competency_weight_share',
        'performance_form_template_id',
        'status',
        'created_by',
    ];

    protected $casts = [
        'kpi_weight_share' => 'decimal:2',
        'competency_weight_share' => 'decimal:2',
    ];

    /** @return HasMany<PerformanceKpiTemplateItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(PerformanceKpiTemplateItem::class)->orderBy('sort_order')->orderBy('id');
    }

    /** @return BelongsTo<PerformanceFormTemplate, $this> */
    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(PerformanceFormTemplate::class, 'performance_form_template_id');
    }

    /** @return BelongsToMany<Position, $this> */
    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class, 'performance_kpi_template_positions')->withTimestamps();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('performance_kpi_template')
            ->logFillable()
            ->logOnlyDirty();
    }
}
