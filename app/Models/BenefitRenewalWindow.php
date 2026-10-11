<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Paketin müqavilə dövrü bitməzdən əvvəl açılan seçim pəncərəsi: işçilər paketi yeniləyir,
 * imtina edir və ya başqa paketə keçir. Pəncərə bağlananda qərarlar tətbiq olunur və paketin
 * müqavilə dövrü növbəti ilə keçir.
 *
 * @property int $id
 * @property int $benefit_plan_id
 * @property \Illuminate\Support\Carbon $contract_end
 * @property \Illuminate\Support\Carbon $next_contract_end
 * @property \Illuminate\Support\Carbon $opens_on
 * @property \Illuminate\Support\Carbon $closes_on
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $notified_at
 * @property \Illuminate\Support\Carbon|null $closed_at
 * @property-read BenefitPlan|null $plan
 */
class BenefitRenewalWindow extends Model
{
    use LogsActivity;

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'benefit_plan_id',
        'contract_end',
        'next_contract_end',
        'opens_on',
        'closes_on',
        'status',
        'notified_at',
        'closed_at',
    ];

    protected $casts = [
        'contract_end' => 'date',
        'next_contract_end' => 'date',
        'opens_on' => 'date',
        'closes_on' => 'date',
        'notified_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /** @return BelongsTo<BenefitPlan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(BenefitPlan::class, 'benefit_plan_id');
    }

    /** @return HasMany<BenefitEnrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(BenefitEnrollment::class, 'renewal_window_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('benefits')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
