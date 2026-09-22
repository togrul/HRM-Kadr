<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string $type
 * @property string $direction
 * @property string $unit
 * @property string $data_source
 * @property string|null $source_metric
 * @property string|null $formula
 * @property array|null $integration_config
 * @property \Illuminate\Support\Carbon|null $integration_synced_at
 * @property string|null $integration_error
 * @property string $frequency
 * @property string $aggregation
 * @property string $perspective
 * @property string|null $indicator_kind
 * @property array|null $qualitative_scale
 * @property bool $evidence_required
 * @property string $status
 * @property int $current_version
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
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

    /** @return HasMany<PerformanceKpiVersion, $this> */
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
