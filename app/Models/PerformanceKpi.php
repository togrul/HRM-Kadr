<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PerformanceKpi extends Model
{
    use LogsActivity;
    use SoftDeletes;

    public const TYPES = ['quantitative', 'qualitative', 'binary'];

    public const DIRECTIONS = ['higher_better', 'lower_better', 'range'];

    public const UNITS = ['percent', 'currency', 'count', 'days', 'hours', 'score'];

    public const FREQUENCIES = ['monthly', 'quarterly', 'semiannual', 'annual'];

    public const AGGREGATIONS = ['sum', 'avg', 'last', 'min', 'max'];

    public const PERSPECTIVES = ['financial', 'customer', 'process', 'growth'];

    public const STATUSES = ['draft', 'active', 'archived'];

    /** Changing any of these creates a new version; open scorecards keep the old one. */
    public const VERSIONED_FIELDS = ['type', 'direction', 'unit', 'aggregation', 'qualitative_scale', 'formula'];

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'direction',
        'unit',
        'data_source',
        'source_metric',
        'formula',
        'integration_config',
        'integration_synced_at',
        'integration_error',
        'frequency',
        'aggregation',
        'perspective',
        'indicator_kind',
        'qualitative_scale',
        'evidence_required',
        'status',
        'current_version',
        'created_by',
    ];

    /** Connector credentials never leave the server. */
    protected $hidden = ['integration_config'];

    protected $casts = [
        'integration_config' => 'encrypted:array',
        'integration_synced_at' => 'datetime',
        'qualitative_scale' => 'array',
        'evidence_required' => 'boolean',
        'current_version' => 'integer',
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(PerformanceKpiVersion::class)->orderByDesc('version');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('performance_kpi')
            ->logFillable()
            ->logOnlyDirty();
    }
}
