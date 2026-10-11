<?php

namespace App\Models;

use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * İşçinin imtiyaz paketinə qoşulması. `status`: active (başlanğıcı gələcəkdə ola bilər, bitmə
 * tarixi təyin oluna bilər), ended, cancelled (səhvən yaradılıb — heç vaxt qüvvədə olmayıb).
 *
 * @property int $id
 * @property int $benefit_plan_id
 * @property string $tabel_no
 * @property string $status
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property string|null $end_reason
 * @property \Illuminate\Support\Carbon|null $end_date_before_termination
 * @property string $source
 * @property string $employee_amount
 * @property string $employer_amount
 * @property string $currency
 * @property bool $eligibility_overridden
 * @property string|null $renewal_decision
 * @property int|null $renewal_window_id
 * @property int|null $renewal_of_id
 * @property int|null $enrolled_by
 * @property string|null $note
 * @property-read BenefitPlan|null $plan
 * @property-read Personnel|null $personnel
 * @property-read \Illuminate\Database\Eloquent\Collection<int, BenefitEnrollmentDependent> $dependents
 */
class BenefitEnrollment extends Model
{
    use LogsActivity;
    use PersonnelTrait;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ENDED = 'ended';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'benefit_plan_id',
        'tabel_no',
        'status',
        'start_date',
        'end_date',
        'end_reason',
        'end_date_before_termination',
        'source',
        'employee_amount',
        'employer_amount',
        'currency',
        'eligibility_overridden',
        'renewal_decision',
        'renewal_window_id',
        'renewal_of_id',
        'enrolled_by',
        'note',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'end_date_before_termination' => 'date',
        'employee_amount' => 'decimal:2',
        'employer_amount' => 'decimal:2',
        'eligibility_overridden' => 'boolean',
    ];

    /** @return BelongsTo<BenefitPlan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(BenefitPlan::class, 'benefit_plan_id');
    }

    /** @return HasMany<BenefitEnrollmentDependent, $this> */
    public function dependents(): HasMany
    {
        return $this->hasMany(BenefitEnrollmentDependent::class);
    }

    /** @return BelongsTo<BenefitRenewalWindow, $this> */
    public function renewalWindow(): BelongsTo
    {
        return $this->belongsTo(BenefitRenewalWindow::class);
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
