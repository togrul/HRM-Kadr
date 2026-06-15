<?php

namespace App\Models;

use App\Models\Concerns\FiltersPersonnel;
use App\Models\Concerns\HasPersonnelAttributes;
use App\Models\Concerns\HasPersonnelRelations;
use App\Observers\PersonnelObserver;
use App\Traits\DateCastTrait;
use App\Traits\NestedStructureTrait;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy(PersonnelObserver::class)]
class Personnel extends Model
{
    use DateCastTrait;
    use FiltersPersonnel;
    use HasFactory;
    use HasPersonnelAttributes;
    use HasPersonnelRelations;
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
        static::creating(fn ($model) => $model->added_by = auth()->id() ?? 1);
        static::deleting(fn ($model) => $model->forceFill(['deleted_by' => auth()->id() ?? 1])->save());
    }
}
