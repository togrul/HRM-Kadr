<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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
        'status',
        'created_by',
    ];

    protected $casts = [
        'kpi_weight_share' => 'decimal:2',
        'competency_weight_share' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PerformanceKpiTemplateItem::class)->orderBy('sort_order')->orderBy('id');
    }

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
