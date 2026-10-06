<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $performance_scorecard_item_id
 * @property float|string|null $current_target
 * @property float|string $proposed_target
 * @property string $reason
 * @property string $status
 * @property int|null $requested_by
 * @property int|null $decided_by
 * @property \Illuminate\Support\Carbon|null $decided_at
 * @property string|null $decision_note
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PerformanceScorecardChangeRequest extends Model
{
    use LogsActivity;

    protected $fillable = [
        'performance_scorecard_item_id',
        'current_target',
        'proposed_target',
        'reason',
        'status',
        'requested_by',
        'decided_by',
        'decided_at',
        'decision_note',
    ];

    protected $casts = [
        'current_target' => 'decimal:4',
        'proposed_target' => 'decimal:4',
        'decided_at' => 'datetime',
    ];

    /** @return BelongsTo<PerformanceScorecardItem, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(PerformanceScorecardItem::class, 'performance_scorecard_item_id');
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** @return BelongsTo<User, $this> */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('performance_scorecard')
            ->logFillable()
            ->logOnlyDirty();
    }
}
