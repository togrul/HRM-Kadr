<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PerformanceKpiActual extends Model
{
    use LogsActivity;

    protected $fillable = [
        'performance_scorecard_item_id',
        'value',
        'source',
        'evidence_path',
        'evidence_name',
        'note',
        'entered_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'value' => 'decimal:4',
        'approved_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(PerformanceScorecardItem::class, 'performance_scorecard_item_id');
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('performance_scorecard')
            ->logFillable()
            ->logOnlyDirty();
    }
}
