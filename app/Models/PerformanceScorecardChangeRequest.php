<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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

    public function item(): BelongsTo
    {
        return $this->belongsTo(PerformanceScorecardItem::class, 'performance_scorecard_item_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

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
