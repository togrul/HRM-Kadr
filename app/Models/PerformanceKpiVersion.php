<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function kpi(): BelongsTo
    {
        return $this->belongsTo(PerformanceKpi::class, 'performance_kpi_id');
    }
}
