<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $performance_scorecard_item_id
 * @property float|string $value
 * @property string $source
 * @property string|null $evidence_path
 * @property string|null $evidence_name
 * @property string|null $note
 * @property int|null $entered_by
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
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

    /** @return BelongsTo<PerformanceScorecardItem, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(PerformanceScorecardItem::class, 'performance_scorecard_item_id');
    }

    /** @return BelongsTo<User, $this> */
    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    /** @return BelongsTo<User, $this> */
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
