<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $performance_cycle_id
 * @property int $personnel_id
 * @property int|null $position_id
 * @property float|string $fte
 * @property bool $is_additional
 * @property int|null $performance_kpi_template_id
 * @property int|null $performance_form_id
 * @property int|null $manager_personnel_id
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $stage_due_at
 * @property \Illuminate\Support\Carbon|null $reminded_at
 * @property \Illuminate\Support\Carbon|null $escalated_at
 * @property \Illuminate\Support\Carbon|null $checkin_reminded_at
 * @property \Illuminate\Support\Carbon|null $actuals_reminded_at
 * @property \Illuminate\Support\Carbon $valid_from
 * @property \Illuminate\Support\Carbon $valid_to
 * @property float|string $prorata_factor
 * @property int $leave_days
 * @property float|string $kpi_weight_share
 * @property float|string $competency_weight_share
 * @property float|string|null $kpi_score
 * @property float|string|null $competency_score
 * @property float|string|null $final_score
 * @property float|string|null $calibrated_score
 * @property string|null $rating_category
 * @property array|null $snapshot
 * @property \Illuminate\Support\Carbon|null $locked_at
 * @property string|null $closure_reason
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
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
        'fte',
        'is_additional',
        'performance_kpi_template_id',
        'manager_personnel_id',
        'status',
        'performance_form_id',
        'stage_due_at',
        'reminded_at',
        'escalated_at',
        'checkin_reminded_at',
        'actuals_reminded_at',
        'valid_from',
        'valid_to',
        'prorata_factor',
        'leave_days',
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
        'fte' => 'decimal:2',
        'is_additional' => 'boolean',
        'checkin_reminded_at' => 'datetime',
        'actuals_reminded_at' => 'datetime',
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

    /** @return BelongsTo<PerformanceCycle, $this> */
    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PerformanceCycle::class, 'performance_cycle_id');
    }

    /** @return BelongsTo<Personnel, $this> */
    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    /** @return BelongsTo<Personnel, $this> */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'manager_personnel_id');
    }

    /** @return BelongsTo<Position, $this> */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /** @return BelongsTo<PerformanceKpiTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(PerformanceKpiTemplate::class, 'performance_kpi_template_id');
    }

    /** @return HasMany<PerformanceScorecardItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(PerformanceScorecardItem::class)->orderBy('sort_order')->orderBy('id');
    }

    /** @return BelongsTo<PerformanceForm, $this> */
    public function form(): BelongsTo
    {
        return $this->belongsTo(PerformanceForm::class, 'performance_form_id');
    }

    /** @return HasMany<PerformanceScorecardEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(PerformanceScorecardEvent::class)->latest('id');
    }

    /** @return HasMany<PerformanceScorecardCheckin, $this> */
    public function checkins(): HasMany
    {
        return $this->hasMany(PerformanceScorecardCheckin::class)->latest('checkin_date')->latest('id');
    }

    /** @return HasMany<PerformanceCalibrationAdjustment, $this> */
    public function calibrations(): HasMany
    {
        return $this->hasMany(PerformanceCalibrationAdjustment::class)->latest('id');
    }

    /** @return HasOne<PerformanceBonusCalculation, $this> */
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
