<?php

namespace App\Models;

use App\Models\Concerns\FiltersPersonnel;
use App\Models\Concerns\HasPersonnelAbsenceRelations;
use App\Models\Concerns\HasPersonnelAttributes;
use App\Models\Concerns\HasPersonnelCareerRelations;
use App\Models\Concerns\HasPersonnelDocumentRelations;
use App\Models\Concerns\HasPersonnelEducationRelations;
use App\Models\Concerns\HasPersonnelEngagementRelations;
use App\Models\Concerns\HasPersonnelOrgRelations;
use App\Observers\PersonnelObserver;
use App\Traits\DateCastTrait;
use App\Traits\NestedStructureTrait;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property string|null $tabel_no
 * @property string|null $person_uid
 * @property string|null $surname
 * @property string|null $name
 * @property string|null $patronymic
 * @property mixed $birthdate
 * @property int|null $gender
 * @property string|null $pin
 * @property string|null $phone
 * @property string|null $mobile
 * @property string|null $email
 * @property int|null $structure_id
 * @property int|null $position_id
 * @property int|null $work_norm_id
 * @property mixed $join_work_date
 * @property mixed $leave_work_date
 * @property-read Position|null $position
 * @property-read Structure|null $structure
 */
#[ObservedBy(PersonnelObserver::class)]
class Personnel extends Model
{
    use DateCastTrait;
    use FiltersPersonnel;
    use HasFactory;
    use HasPersonnelAbsenceRelations;
    use HasPersonnelAttributes;
    use HasPersonnelCareerRelations;
    use HasPersonnelDocumentRelations;
    use HasPersonnelEducationRelations;
    use HasPersonnelEngagementRelations;
    use HasPersonnelOrgRelations;
    use LogsActivity;
    use NestedStructureTrait;
    use SoftDeletes;

    protected $fillable = [
        'tabel_no',
        'surname',
        'name',
        'patronymic',
        'photo',
        'has_changed_initials',
        'previous_surname',
        'previous_name',
        'previous_patronymic',
        'initials_changed_date',
        'initials_change_reason',
        'birthdate',
        'gender',
        'phone',
        'mobile',
        'email',
        'nationality_id',
        'has_changed_nationality',
        'previous_nationality_id',
        'nationality_changed_date',
        'nationality_change_reason',
        'pin',
        'residental_address',
        'registered_address',
        'education_degree_id',
        'structure_id',
        'parent_id',
        'position_id',
        'work_norm_id',
        'join_work_date',
        'leave_work_date',
        'social_origin_id',
        'disability_id',
        'disability_given_date',
        'extra_important_information',
        'computer_knowledge',
        'scientific_works_inventions',
        'participation_in_war',
        'discrediting_information',
        'referenced_by',
        'special_inspection_date',
        'special_inspection_result',
        'medical_inspection_date',
        'medical_inspection_result',
        'added_by',
        'deleted_by',
        'is_pending',
    ];

    protected $dates = [
        'join_work_date',
        'leave_work_date',
        'birthdate',
        'special_inspection_date',
        'medical_inspection_date',
    ];

    protected $casts = [
        'birthdate' => self::FORMAT_CAST,
        'join_work_date' => self::FORMAT_CAST,
        'leave_work_date' => self::FORMAT_CAST,
        'special_inspection_date' => self::FORMAT_CAST,
        'medical_inspection_date' => self::FORMAT_CAST,
    ];

    /**
     * Highly sensitive columns kept OUT of the activity log — audit-log viewers
     * (gated by a single broad permission) must not see these via change history.
     */
    public const ACTIVITY_LOG_EXCLUDED = [
        'pin',
        'discrediting_information',
        'special_inspection_result',
        'medical_inspection_result',
        'scientific_works_inventions',
        'extra_important_information',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(self::ACTIVITY_LOG_EXCLUDED)
            ->logOnlyDirty()
            ->useLogName('personnel')
            ->dontSubmitEmptyLogs();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} personnel";
    }

    protected static function boot()
    {
        parent::boot();

        // These MUST return nothing.
        //
        // `creating` and `deleting` are halting events: Eloquent dispatches them
        // through `until()`, which stops at the first listener returning a
        // non-null value. An arrow function returns the assignment it performs,
        // so `fn ($model) => $model->added_by = ...` returned an id and silently
        // swallowed every listener registered afterwards — including
        // PersonnelObserver. Nothing depended on those listeners before, so the
        // breakage stayed invisible.
        static::creating(function ($model): void {
            $model->added_by = auth()->id() ?? 1;
        });

        static::deleting(function ($model): void {
            $model->forceFill(['deleted_by' => auth()->id() ?? 1])->save();
        });
    }
}
