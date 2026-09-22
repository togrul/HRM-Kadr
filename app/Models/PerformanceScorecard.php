<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PerformanceScorecard extends Model
{
    use LogsActivity;

    public const STATUSES = ['draft', 'pending_agreement', 'active', 'self_review', 'manager_review', 'calibration', 'approved', 'closed'];

    /**
     * The scorecard workflow (spec §5). Each action names the statuses it leaves, the
     * status it enters, the card roles allowed to take it and whether a reason is required.
     *
     * @var array<string, array{from: array<int, string>, to: string, roles: array<int, string>, reason?: bool}>
     */
    public const TRANSITIONS = [
        'send_for_agreement' => ['from' => ['draft'], 'to' => 'pending_agreement', 'roles' => ['hr', 'manager']],
        'accept' => ['from' => ['pending_agreement'], 'to' => 'active', 'roles' => ['employee', 'hr']],
        'reject' => ['from' => ['pending_agreement'], 'to' => 'draft', 'roles' => ['employee'], 'reason' => true],
        'activate' => ['from' => ['draft'], 'to' => 'active', 'roles' => ['hr']],
        'start_self_review' => ['from' => ['active'], 'to' => 'self_review', 'roles' => ['hr', 'manager']],
        'submit_self_review' => ['from' => ['self_review'], 'to' => 'manager_review', 'roles' => ['employee', 'hr']],
        'submit_manager_review' => ['from' => ['manager_review'], 'to' => 'calibration', 'roles' => ['manager', 'hr']],
        'approve' => ['from' => ['calibration'], 'to' => 'approved', 'roles' => ['hr']],
        'return' => ['from' => ['calibration', 'approved'], 'to' => 'manager_review', 'roles' => ['hr'], 'reason' => true],
        'close' => ['from' => ['approved'], 'to' => 'closed', 'roles' => ['hr']],
    ];

    /** Working days each stage gets before reminders and escalation start (spec §5 defaults). */
    public const STAGE_WORKING_DAYS = [
        'pending_agreement' => 3,
        'self_review' => 5,
        'manager_review' => 5,
        'calibration' => 5,
    ];

    /** Whose move the card waits for in each stage. */
    public const STAGE_OWNER = [
        'draft' => 'manager',
        'pending_agreement' => 'employee',
        'self_review' => 'employee',
        'manager_review' => 'manager',
        'calibration' => 'hr',
        'approved' => 'hr',
    ];

    protected $fillable = [
        'performance_cycle_id',
        'personnel_id',
        'position_id',
        'performance_kpi_template_id',
        'manager_personnel_id',
        'status',
        'performance_form_id',
        'stage_due_at',
        'reminded_at',
        'escalated_at',
        'valid_from',
        'valid_to',
        'prorata_factor',
        'kpi_weight_share',
        'competency_weight_share',
        'kpi_score',
        'competency_score',
        'final_score',
        'calibrated_score',
        'rating_category',
        'snapshot',
        'locked_at',
        'closure_reason',
        'created_by',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_to' => 'date',
        'prorata_factor' => 'decimal:4',
        'kpi_weight_share' => 'decimal:2',
        'competency_weight_share' => 'decimal:2',
        'kpi_score' => 'decimal:4',
        'competency_score' => 'decimal:4',
        'final_score' => 'decimal:4',
        'calibrated_score' => 'decimal:4',
        'snapshot' => 'array',
        'locked_at' => 'datetime',
        'stage_due_at' => 'date',
        'reminded_at' => 'datetime',
        'escalated_at' => 'datetime',
    ];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PerformanceCycle::class, 'performance_cycle_id');
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'manager_personnel_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(PerformanceKpiTemplate::class, 'performance_kpi_template_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PerformanceScorecardItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(PerformanceForm::class, 'performance_form_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(PerformanceScorecardEvent::class)->latest('id');
    }

    public function checkins(): HasMany
    {
        return $this->hasMany(PerformanceScorecardCheckin::class)->latest('checkin_date')->latest('id');
    }

    public function calibrations(): HasMany
    {
        return $this->hasMany(PerformanceCalibrationAdjustment::class)->latest('id');
    }

    public function bonus(): HasOne
    {
        return $this->hasOne(PerformanceBonusCalculation::class);
    }

    /** The score everything downstream uses: calibrated when HR adjusted it, otherwise final. */
    public function effectiveScore(): ?float
    {
        $score = $this->calibrated_score ?? $this->final_score;

        return $score === null ? null : (float) $score;
    }

    public function isLocked(): bool
    {
        return $this->status === 'closed';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('performance_scorecard')
            ->logFillable()
            ->logOnlyDirty();
    }
}
