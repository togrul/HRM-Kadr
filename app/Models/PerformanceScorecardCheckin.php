<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $performance_scorecard_id
 * @property \Illuminate\Support\Carbon $checkin_date
 * @property string $progress
 * @property string|null $risks
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
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

    /** @return BelongsTo<PerformanceScorecard, $this> */
    public function scorecard(): BelongsTo
    {
        return $this->belongsTo(PerformanceScorecard::class, 'performance_scorecard_id');
    }

    /** @return BelongsTo<User, $this> */
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
