<?php

namespace App\Models;

use App\Data\LeaveFilterData;
use Carbon\CarbonImmutable;
use App\Enums\OrderStatusEnum;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasOne};

class Leave extends Model
{
    use HasFactory, PersonnelTrait, SoftDeletes;

     /** @var array<int, string> */
    protected $fillable = [
        'tabel_no',
        'leave_type_id',
        'starts_at',
        'ends_at',
        'total_days',
        'reason',
        'status_id',
        'document_path',
        'assigned_to',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'starts_at'   => 'immutable_date',
        'ends_at'     => 'immutable_date',
        'approved_at' => 'immutable_datetime',
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

    public function canBeApprovedBy(?\App\Models\User $user): bool
    {
        $personnelId = optional($user)->personnel_id;

        return $this->isPending
            && (is_null($this->assigned_to) || (int)$this->assigned_to === (int)$personnelId);
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

            $query->whereBetween('starts_at', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->whereDate('starts_at', '>=', $startDate);
        } elseif ($endDate) {
            $query->whereDate('ends_at', '<=', $endDate);
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

    /** Keep model source-of-truth in sync (or move to an Observer) */
    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if (!$model->starts_at || !$model->ends_at) {
                return;
            }

            // If total_days not set or dates changed, recompute
            if ($model->isDirty(['starts_at', 'ends_at']) || is_null($model->total_days)) {
                $model->total_days = $model->durationDays();
            }
        });
    }
}
