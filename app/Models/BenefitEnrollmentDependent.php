<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Qoşulmaya daxil edilmiş ailə üzvü. Əməkdaşın qohumluq qeydindən (personnel_kinships) seçilir;
 * ad, qohumluq və doğum tarixi qoşulma anında köçürülür — qohumluq qeydi sonradan dəyişsə də
 * sığorta siyahısı tarixi olaraq qalır.
 *
 * @property int $id
 * @property int $benefit_enrollment_id
 * @property int|null $personnel_kinship_id
 * @property string $full_name
 * @property string|null $relation
 * @property \Illuminate\Support\Carbon|null $birthdate
 */
class BenefitEnrollmentDependent extends Model
{
    use LogsActivity;

    protected $fillable = [
        'benefit_enrollment_id',
        'personnel_kinship_id',
        'full_name',
        'relation',
        'birthdate',
    ];

    protected $casts = [
        'birthdate' => 'date',
    ];

    /** @return BelongsTo<BenefitEnrollment, $this> */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(BenefitEnrollment::class, 'benefit_enrollment_id');
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
