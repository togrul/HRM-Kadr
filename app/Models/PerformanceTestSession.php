<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PerformanceTestSession extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'performance_cycle_id',
        'performance_test_bank_id',
        'personnel_id',
        'reviewer_id',
        'assigned_by',
        'scheduled_at',
        'available_until',
        'pass_score',
        'duration_minutes',
        'max_attempts',
        'status',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'available_until' => 'datetime',
        'pass_score' => 'decimal:2',
        'duration_minutes' => 'integer',
        'max_attempts' => 'integer',
    ];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PerformanceCycle::class, 'performance_cycle_id');
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(PerformanceTestBank::class, 'performance_test_bank_id');
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(PerformanceTestAttempt::class, 'performance_test_session_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('performance_test_session')
            ->logFillable()
            ->logOnlyDirty();
    }
}
