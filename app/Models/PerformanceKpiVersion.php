<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $performance_kpi_id
 * @property int $version
 * @property array $snapshot
 * @property \Illuminate\Support\Carbon $effective_from
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PerformanceKpiVersion extends Model
{
    protected $fillable = [
        'performance_kpi_id',
        'version',
        'snapshot',
        'effective_from',
        'created_by',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'effective_from' => 'datetime',
        'version' => 'integer',
    ];

    /** @return BelongsTo<PerformanceKpi, $this> */
    public function kpi(): BelongsTo
    {
        return $this->belongsTo(PerformanceKpi::class, 'performance_kpi_id');
    }
}
