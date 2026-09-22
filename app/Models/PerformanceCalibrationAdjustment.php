<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PerformanceCalibrationAdjustment extends Model
{
    use LogsActivity;

    protected $fillable = [
        'performance_scorecard_id',
        'delta',
        'reason',
        'adjusted_by',
    ];

    protected $casts = [
        'delta' => 'decimal:4',
    ];

    public function scorecard(): BelongsTo
    {
        return $this->belongsTo(PerformanceScorecard::class, 'performance_scorecard_id');
    }

    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('performance_scorecard')
            ->logFillable()
            ->logOnlyDirty();
    }
}
