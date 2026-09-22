<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One step of a scorecard's workflow history: who moved it, from where to where and why.
 */
class PerformanceScorecardEvent extends Model
{
    protected $fillable = [
        'performance_scorecard_id',
        'action',
        'from_status',
        'to_status',
        'reason',
        'user_id',
    ];

    protected $casts = [
    ];

    public function scorecard(): BelongsTo
    {
        return $this->belongsTo(PerformanceScorecard::class, 'performance_scorecard_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
