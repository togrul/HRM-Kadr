<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PerformanceScorecardCheckin extends Model
{
    use LogsActivity;

    protected $fillable = [
        'performance_scorecard_id',
        'checkin_date',
        'progress',
        'risks',
        'created_by',
    ];

    protected $casts = [
        'checkin_date' => 'date',
    ];

    public function scorecard(): BelongsTo
    {
        return $this->belongsTo(PerformanceScorecard::class, 'performance_scorecard_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('performance_scorecard')
            ->logFillable()
            ->logOnlyDirty();
    }
}
