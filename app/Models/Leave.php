<?php

namespace App\Models;

use App\Data\LeaveFilterData;
use Carbon\CarbonImmutable;
use App\Enums\OrderStatusEnum;
use App\Traits\PersonnelTrait;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasOne};
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Leave extends Model
{
    use HasFactory, PersonnelTrait, SoftDeletes;

     /** @var array<int, string> */
    protected $fillable = [
        'tabel_no',
        'leave_type_id',
        'starts_at',
        'ends_at',
        'duration_unit',
        'partial_day_part',
        'starts_time',
        'ends_time',
        'total_days',
        'total_minutes',
        'reason',
        'status_id',
        'document_path',
        'assigned_to',
        'fallback_approver_personnel_id',
        'approval_route_source',
        'hr_always_included',
        'submission_source',
        'submitted_by_user_id',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'starts_at'   => 'immutable_date',
        'ends_at'     => 'immutable_date',
        'approved_at' => 'immutable_datetime',
        'total_minutes' => 'integer',
    ];

    /* -------------------------------- Relations ------------------------------- */

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'status_id', 'id');
    }

    public function logs(): HasMany
    {
       return $this->hasMany(LeaveStatusLog::class, 'leave_id')->orderBy('changed_at');
    }

    public function latestLog(): HasOne
    {
        return $this->hasOne(LeaveStatusLog::class, 'leave_id')->latestOfMany('changed_at');
    }

    public function approver(): BelongsTo
    {
         return $this->belongsTo(Personnel::class, 'approved_by', 'id');
    }

    public function assigned(): BelongsTo
    {
         return $this->belongsTo(Personnel::class, 'assigned_to', 'id');
    }

    public function fallbackApprover(): BelongsTo
    {
         return $this->belongsTo(Personnel::class, 'fallback_approver_personnel_id', 'id');
    }

    public function submittedBy(): BelongsTo
    {
         return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function changeRequests(): MorphMany
    {
        return $this->morphMany(EmployeeRequestChangeRequest::class, 'requestable');
    }

    /* ----------------------------- Accessors / Attrs -------------------------- */
    protected function periodLabel(): Attribute
    {
        return Attribute::get(function () {
            /** @var CarbonImmutable|null $s */
            $s = $this->starts_at;
            /** @var CarbonImmutable|null $e */
            $e = $this->ends_at;

            if (!$s || !$e) return null;

            $range = $s->format('d.m.Y') . ' – ' . $e->format('d.m.Y');
            return "{$range}";
        });
    }

    /** Boolean helpers */
    protected function isApproved(): Attribute
    {
        return Attribute::get(fn () => !is_null($this->approved_at));
    }

    protected function isPending(): Attribute
    {
        return Attribute::get(fn () => (int)$this->status_id === OrderStatusEnum::PENDING->value);
    }

    public function canBeApprovedBy(?User $user): bool
    {
        if (! $this->isPending) {
            return false;
        }

        if (is_null($this->assigned_to)) {
            return true;
        }

        if (! $user) {
            return false;
        }

        $assignedTo = (int) $this->assigned_to;
        $userId = (int) $user->getKey();

        // Backward compatibility: some old rows may still carry users.id.
        if ($assignedTo === $userId) {
            return true;
        }

        static $personnelIdCache = [];

        if (! array_key_exists($userId, $personnelIdCache)) {
            $personnelId = $user->relationLoaded('personnel')
                ? $user->personnel?->id
                : $user->personnel()->value('id');

            $personnelIdCache[$userId] = $personnelId ? (int) $personnelId : null;
        }

        $personnelId = $personnelIdCache[$userId];

        return $personnelId !== null && $assignedTo === $personnelId;
    }

    /* --------------------------------- Scopes -------------------------------- */

    public function scopePending($q)
    {
        return $q->where('status_id', OrderStatusEnum::PENDING->value);
    }

    public function scopeApproved($q)
    {
        return $q->whereNotNull('approved_at');
    }

    /** Overlapping any part of a given period */
    public function scopeOverlapping($q, CarbonImmutable $from, CarbonImmutable $to)
    {
        return $q->where(function ($w) use ($from, $to) {
            $w->whereDate('starts_at', '<=', $to)
              ->whereDate('ends_at', '>=', $from);
        });
    }

    public function scopeForPeriod($q, ?CarbonImmutable $from, ?CarbonImmutable $to)
    {
        if ($from) $q->whereDate('starts_at', '>=', $from);
        if ($to)   $q->whereDate('ends_at',   '<=', $to);
        return $q;
    }

    public function scopeFilter(Builder $query, array|LeaveFilterData $filters): Builder
    {
        if ($filters instanceof LeaveFilterData) {
            $filters = $filters->toArray();
        }

        if (empty($filters)) {
            return $query;
        }

        $leaveType = data_get($filters, 'leave_type_id');
        if ($leaveType !== null && $leaveType !== '') {
            $query->where('leave_type_id', (int) $leaveType);
        }

        $reason = trim((string) data_get($filters, 'reason', ''));
        if ($reason !== '') {
            $query->where('reason', 'like', "%{$reason}%");
        }

        if (array_key_exists('gender', $filters)) {
            $gender = data_get($filters, 'gender');
            if ($gender !== null && $gender !== '') {
                $query->whereHas('personnel', fn (Builder $q) => $q->where('gender', $gender));
            }
        }

        $fullname = trim((string) data_get($filters, 'fullname', ''));
        if ($fullname !== '') {
            $query->whereHas('personnel', fn (Builder $q) => $q->nameLike($fullname));
        }

        $startsAt = data_get($filters, 'starts_at');
        $endsAt = data_get($filters, 'ends_at');

        $startDate = $startsAt ? CarbonImmutable::parse($startsAt)->startOfDay() : null;
        $endDate = $endsAt ? CarbonImmutable::parse($endsAt)->endOfDay() : null;

        if ($startDate && $endDate) {
            if ($endDate->lessThan($startDate)) {
                [$startDate, $endDate] = [$endDate, $startDate];
            }

            $query->whereDate('starts_at', '<=', $endDate->toDateString())
                ->whereDate('ends_at', '>=', $startDate->toDateString());
        } elseif ($startDate) {
            $query->whereDate('ends_at', '>=', $startDate->toDateString());
        } elseif ($endDate) {
            $query->whereDate('starts_at', '<=', $endDate->toDateString());
        }

        return $query;
    }

    /* ------------------------------ Domain Logic ----------------------------- */

    /** Inclusive day count (calendar days). Replace with business-day calc if needed. */
    public function durationDays(): int
    {
        $s = $this->starts_at;
        $e = $this->ends_at;
        if (!$s || !$e) return 0;

        return $s->diffInDays($e) + 1;
    }

    public function normalizedDurationUnit(): string
    {
        $unit = trim((string) ($this->duration_unit ?? 'day'));

        return in_array($unit, ['day', 'half_day', 'hour'], true) ? $unit : 'day';
    }

    public function durationUnitLabel(): string
    {
        return __('leaves::common.labels.duration_units.'.$this->normalizedDurationUnit());
    }

    public function durationWindowLabel(): ?string
    {
        $durationUnit = $this->normalizedDurationUnit();

        if ($durationUnit === 'half_day') {
            return $this->partial_day_part
                ? __('leaves::common.labels.partial_day_parts.'.$this->partial_day_part)
                : null;
        }

        if ($durationUnit === 'hour' && filled($this->starts_time) && filled($this->ends_time)) {
            return Str::substr((string) $this->starts_time, 0, 5).' - '.Str::substr((string) $this->ends_time, 0, 5);
        }

        return null;
    }

    public function durationSummary(): string
    {
        $durationUnit = $this->normalizedDurationUnit();

        if ($durationUnit === 'hour') {
            $minutes = (int) ($this->total_minutes ?? 0);

            if ($minutes <= 0) {
                return $this->durationUnitLabel();
            }

            return __('leaves::common.labels.duration_summary_hour', [
                'hours' => number_format($minutes / 60, 1),
            ]);
        }

        if ($durationUnit === 'half_day') {
            return __('leaves::common.labels.duration_summary_half_day');
        }

        $days = (int) ($this->total_days ?: $this->durationDays());

        return __('leaves::common.labels.duration_summary_day', ['days' => $days]);
    }

    public function durationDetailLabel(): string
    {
        $window = $this->durationWindowLabel();

        return $window
            ? $this->durationSummary().' • '.$window
            : $this->durationSummary();
    }

    /** Keep model source-of-truth in sync (or move to an Observer) */
    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if (! $model->starts_at) {
                return;
            }

            $durationUnit = $model->normalizedDurationUnit();
            $model->duration_unit = $durationUnit;

            if ($durationUnit !== 'day') {
                $model->ends_at = $model->starts_at;
            } elseif (! $model->ends_at) {
                $model->ends_at = $model->starts_at;
            }

            if ($durationUnit === 'day') {
                $model->partial_day_part = null;
                $model->starts_time = null;
                $model->ends_time = null;
                $model->total_minutes = null;
            } elseif ($durationUnit === 'half_day') {
                $model->starts_time = null;
                $model->ends_time = null;
                $model->total_minutes = null;
            } else {
                $model->partial_day_part = null;

                if (filled($model->starts_time) && filled($model->ends_time)) {
                    $start = CarbonImmutable::parse($model->starts_at->format('Y-m-d').' '.(string) $model->starts_time);
                    $end = CarbonImmutable::parse($model->starts_at->format('Y-m-d').' '.(string) $model->ends_time);
                    $model->total_minutes = $end->greaterThan($start)
                        ? $start->diffInMinutes($end)
                        : null;
                } else {
                    $model->total_minutes = null;
                }
            }

            if ($model->isDirty(['starts_at', 'ends_at', 'duration_unit']) || is_null($model->total_days)) {
                $model->total_days = $durationUnit === 'day'
                    ? $model->durationDays()
                    : 1;
            }
        });
    }
}
