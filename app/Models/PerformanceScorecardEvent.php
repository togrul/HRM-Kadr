<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One step of a scorecard's workflow history: who moved it, from where to where and why.
 *
 * @property int $id
 * @property int $performance_scorecard_id
 * @property string $action
 * @property string|null $from_status
 * @property string|null $to_status
 * @property string|null $reason
 * @property int|null $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
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

    /** @return BelongsTo<PerformanceScorecard, $this> */
    public function scorecard(): BelongsTo
    {
        return $this->belongsTo(PerformanceScorecard::class, 'performance_scorecard_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
