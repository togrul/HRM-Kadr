<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A sick-leave certificate (xəstəlik vərəqəsi): the document behind one sick leave.
 *
 * The period lives on the leave (starts_at / ends_at / total_days); an open certificate is
 * a leave with no end date yet. The diagnosis is medical data: it is stored encrypted,
 * hidden from serialisation and never written to the activity log — only a holder of
 * `view-medical-diagnosis` sees it, in the certificate form.
 *
 * @property int $id
 * @property int $leave_id
 * @property string $series
 * @property string $number
 * @property string|null $medical_institution
 * @property string|null $doctor_name
 * @property string|null $diagnosis
 * @property string $status
 * @property \Carbon\CarbonInterface|null $closed_at
 * @property int|null $continuation_of_id
 * @property string|null $notes
 * @property string|null $cancel_reason
 * @property int|null $created_by
 */
class LeaveSickCertificate extends Model
{
    use LogsActivity;

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [self::STATUS_OPEN, self::STATUS_CLOSED, self::STATUS_CANCELLED];

    /** Attributes that must never leave the form: not in arrays/JSON, not in the audit log. */
    public const PRIVATE_ATTRIBUTES = ['diagnosis'];

    /** @var list<string> */
    protected $fillable = [
        'leave_id',
        'series',
        'number',
        'medical_institution',
        'doctor_name',
        'diagnosis',
        'status',
        'closed_at',
        'continuation_of_id',
        'notes',
        'cancel_reason',
        'created_by',
    ];

    /** @var list<string> */
    protected $hidden = self::PRIVATE_ATTRIBUTES;

    protected $casts = [
        'diagnosis' => 'encrypted',
        'closed_at' => 'immutable_datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['created_at', 'updated_at', ...self::PRIVATE_ATTRIBUTES])
            ->logOnlyDirty()
            ->useLogName('leaves')
            ->dontSubmitEmptyLogs();
    }

    /** @return BelongsTo<Leave, $this> */
    public function leave(): BelongsTo
    {
        return $this->belongsTo(Leave::class);
    }

    /** @return BelongsTo<self, $this> */
    public function continuationOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'continuation_of_id');
    }

    /** @return HasMany<self, $this> */
    public function continuations(): HasMany
    {
        return $this->hasMany(self::class, 'continuation_of_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /** "AB-123456", or just the number when the certificate has no series. */
    public function fullNumber(): string
    {
        return $this->series !== '' ? $this->series.'-'.$this->number : $this->number;
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_CANCELLED);
    }
}
