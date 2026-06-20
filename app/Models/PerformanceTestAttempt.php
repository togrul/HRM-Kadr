<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PerformanceTestAttempt extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'performance_test_session_id',
        'attempt_no',
        'started_at',
        'submitted_at',
        'duration_seconds',
        'score',
        'percentage',
        'passed',
        'status',
        'meta',
        'auto_scored_at',
        'reviewed_at',
        'reviewed_by',
        'weak_area_synced_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'duration_seconds' => 'integer',
        'score' => 'decimal:2',
        'percentage' => 'decimal:2',
        'passed' => 'boolean',
        'meta' => 'array',
        'auto_scored_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'weak_area_synced_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(PerformanceTestSession::class, 'performance_test_session_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(PerformanceTestAttemptAnswer::class, 'performance_test_attempt_id');
    }

    public function trainingNeedLinks(): HasMany
    {
        return $this->hasMany(PerformanceTestTrainingNeedLink::class, 'performance_test_attempt_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('performance_test_attempt')
            ->logFillable()
            ->logOnlyDirty();
    }
}
