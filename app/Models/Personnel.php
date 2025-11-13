<?php

namespace App\Models;

use Carbon\Carbon;
use App\Traits\DateCastTrait;
use Spatie\Activitylog\LogOptions;
use App\Observers\PersonnelObserver;
use App\Traits\NestedStructureTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy(PersonnelObserver::class)]
class Personnel extends Model
{
    use DateCastTrait;
    use HasFactory;
    use LogsActivity;
    use NestedStructureTrait;
    use SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('personnel')
            ->dontSubmitEmptyLogs();
    }

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

    protected $likeFilterFields = [
        'surname', 'name', 'patronymic', 'tabel_no', 'pin',
    ];

    public function getFullnameAttribute(): string
    {
        return "{$this->surname} {$this->name} {$this->patronymic}";
    }

    public function getFullnameMaxAttribute(): string
    {
        return $this->fullname.' '.($this->gender == 2 ? 'qızı' : 'oğlu');
    }

    public function personDidDelete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }

    public function nationality(): BelongsTo
    {
        return $this->belongsTo(CountryTranslation::class, 'nationality_id', 'country_id')
            ->where('locale', config('app.locale'));
    }

    public function previousNationality(): BelongsTo
    {
        return $this->belongsTo(CountryTranslation::class, 'previous_nationality_id', 'country_id')
            ->where('locale', config('app.locale'));
    }

    public function educationDegree(): BelongsTo
    {
        return $this->belongsTo(EducationDegree::class, 'education_degree_id', 'id');
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id', 'id');
    }

    public function disability(): BelongsTo
    {
        return $this->belongsTo(Disability::class, 'disability_id', 'id');
    }

    public function workNorm(): BelongsTo
    {
        return $this->belongsTo(WorkNorm::class, 'work_norm_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by', 'id');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }

    public function awards(): HasMany
    {
        return $this->hasMany(PersonnelAward::class, 'tabel_no', 'tabel_no')->orderByDesc('given_date');
    }

    //    public function criminals() : HasMany
    //    {
    //        return $this->hasMany(PersonnelCriminal::class,'tabel_no','tabel_no')->orderByDesc('given_date');
    //    }

    public function idDocuments(): HasOne
    {
        return $this->hasOne(PersonnelIdentityDocument::class, 'tabel_no', 'tabel_no');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(PersonnelCard::class, 'tabel_no', 'tabel_no');
    }

    public function validCard(): HasOne
    {
        return $this->hasOne(PersonnelCard::class, 'tabel_no', 'tabel_no')
            ->where('valid_date', '>', Carbon::now()->format('Y-m-d'));
    }

    public function passports(): HasMany
    {
        return $this->hasMany(PersonnelPassports::class, 'tabel_no', 'tabel_no');
    }

    public function validPassport(): HasOne
    {
        return $this->hasOne(PersonnelPassports::class, 'tabel_no', 'tabel_no')
            ->where('valid_date', '>', Carbon::now()->format('Y-m-d'));
    }

    public function education(): HasOne
    {
        return $this->hasOne(PersonnelEducation::class, 'tabel_no', 'tabel_no');
    }

    public function extraEducations(): HasMany
    {
        return $this->hasMany(PersonnelExtraEducation::class, 'tabel_no', 'tabel_no')->orderByDesc('graduated_year');
    }

    public function foreignLanguages(): HasMany
    {
        return $this->hasMany(PersonnelForeignLanguage::class, 'tabel_no', 'tabel_no');
    }

    public function files(): HasMany
    {
        return $this->hasMany(PersonnelDocument::class, 'tabel_no', 'tabel_no');
    }

    public function kinships(): HasMany
    {
        return $this->hasMany(PersonnelKinship::class, 'tabel_no', 'tabel_no')->orderBy('kinship_id');
    }

    public function fatherMother(): HasMany
    {
        return $this->kinships()->whereBetween('kinship_id', [11, 12]);
    }

    public function wifeChildren(): HasMany
    {
        return $this->kinships()->whereBetween('kinship_id', [21, 29]);
    }

    public function laborActivities(): HasMany
    {
        return $this->hasMany(PersonnelLaborActivity::class, 'tabel_no', 'tabel_no')
            ->orderByRaw('leave_date IS NULL DESC, leave_date DESC');
    }

    public function specialServices(): HasMany
    {
        return $this->hasMany(PersonnelLaborActivity::class, 'tabel_no', 'tabel_no')
            ->where('is_special_service', 1)
            ->orderByDesc('leave_date');
    }

    public function currentWork(): HasOne
    {
        return $this->hasOne(PersonnelLaborActivity::class, 'tabel_no', 'tabel_no')
            ->where('is_current', true);
    }

    public function military(): HasMany
    {
        return $this->hasMany(PersonnelMilitaryService::class, 'tabel_no', 'tabel_no')->orderByDesc('end_date');
    }

    public function participations(): HasMany
    {
        return $this->hasMany(PersonnelParticipationEvent::class, 'tabel_no', 'tabel_no')->orderByDesc('event_date');
    }

    public function punishments(): HasMany
    {
        return $this->hasMany(PersonnelPunishment::class, 'tabel_no', 'tabel_no')->orderByDesc('given_date');
    }

    public function ranks(): HasMany
    {
        return $this->hasMany(PersonnelRank::class, 'tabel_no', 'tabel_no')->orderByDesc('given_date');
    }

    public function ranksASC(): HasMany
    {
        return $this->hasMany(PersonnelRank::class, 'tabel_no', 'tabel_no')->orderBy('given_date');
    }

    public function latestRank(): HasOne
    {
        return $this->hasOne(PersonnelRank::class, 'tabel_no', 'tabel_no')->latestOfMany('given_date');
    }

    public function weapons(): HasMany
    {
        return $this->hasMany(PersonnelWeapon::class, 'tabel_no', 'tabel_no')->orderByDesc('given_date');
    }

    public function yearlyVacation(): HasMany
    {
        return $this->hasMany(Vacation::class, 'tabel_no', 'tabel_no')->orderByDesc('year');
    }

    public function latestYearlyVacation(): HasOne
    {
        return $this->hasOne(Vacation::class, 'tabel_no', 'tabel_no')
            ->where(function ($query) {
                $query->where('year', Carbon::now()->year);
            });
    }

    public function activeWeapons(): HasMany
    {
        return $this->hasMany(PersonnelWeapon::class, 'tabel_no', 'tabel_no')->whereNull('return_date')->orderByDesc('given_date');
    }

    public function vacations(): HasMany
    {
        return $this->hasMany(PersonnelVacation::class, 'tabel_no', 'tabel_no')->orderByDesc('return_work_date');
    }

    public function latestVacation(): HasOne
    {
        return $this->hasOne(PersonnelVacation::class, 'tabel_no', 'tabel_no')->latestOfMany('return_work_date');
    }

    public function hasActiveVacation()
    {
        return $this->latestVacation()
            ->where('start_date', '<=', Carbon::now())
            ->where('return_work_date', '>', Carbon::now());
    }

    public function getActiveVacationAttribute()
    {
        if (! $this->relationLoaded('latestVacation')) {
            return null;
        }

        $vacation = $this->latestVacation;

        if (! $vacation) {
            return null;
        }

        $now = Carbon::now();

        return ($vacation->start_date <= $now && $vacation->return_work_date > $now)
            ? $vacation
            : null;
    }

    public function businessTrips(): HasMany
    {
        return $this->hasMany(PersonnelBusinessTrip::class, 'tabel_no', 'tabel_no')->orderByDesc('end_date');
    }

    public function latestBusinessTrip(): HasOne
    {
        return $this->hasOne(PersonnelBusinessTrip::class, 'tabel_no', 'tabel_no')->latestOfMany('end_date');
    }

    public function hasActiveBusinessTrip()
    {
        return $this->latestBusinessTrip()
            ->where('start_date', '<=', Carbon::now())
            ->where('end_date', '>', Carbon::now());
    }

    public function getActiveBusinessTripAttribute()
    {
        if (! $this->relationLoaded('latestBusinessTrip')) {
            return null;
        }

        $trip = $this->latestBusinessTrip;

        if (! $trip) {
            return null;
        }

        $now = Carbon::now();

        return ($trip->start_date <= $now && $trip->end_date > $now)
            ? $trip
            : null;
    }

    public function degreeAndNames(): HasMany
    {
        return $this->hasMany(PersonnelScientificDegreeAndName::class, 'tabel_no', 'tabel_no')->orderByDesc('given_date');
    }

    public function elections(): HasMany
    {
        return $this->hasMany(PersonnelElectedElectoral::class, 'tabel_no', 'tabel_no')->orderByDesc('elected_date');
    }

    public function injuries(): HasMany
    {
        return $this->hasMany(PersonnelInjury::class, 'tabel_no', 'tabel_no')->orderByDesc('date_time');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(PersonnelContract::class, 'tabel_no', 'tabel_no')->orderByDesc('contract_ends_at');
    }

    public function latestContract(): HasOne
    {
        return $this->hasOne(PersonnelContract::class, 'tabel_no', 'tabel_no')->latestOfMany('contract_ends_at');
    }

    public function hasActiveContract(): HasOne
    {
        return $this->latestContract()
            ->where('contract_start_date', '<=', Carbon::now())
            ->where('contract_ends_at', '>', Carbon::now());
    }

    public function pensionCards(): HasMany
    {
        return $this->hasMany(PersonnelPensionCard::class, 'tabel_no', 'tabel_no');
    }

    public function latestPensionCard(): HasOne
    {
        return $this->hasOne(PersonnelPensionCard::class, 'tabel_no', 'tabel_no')->latestOfMany('expiry_date');
    }

    public function hasActivePensionCard(): HasOne
    {
        return $this->latestPensionCard()
            ->where('given_date', '<=', Carbon::now())
            ->where('expiry_date', '>', Carbon::now());
    }

    public function disposals(): HasMany
    {
        return $this->hasMany(PersonnelDisposal::class, 'tabel_no', 'tabel_no');
    }

    public function latestDisposal(): HasOne
    {
        return $this->hasOne(PersonnelDisposal::class, 'tabel_no', 'tabel_no')->latestOfMany('disposal_date');
    }

    public function hasActiveDisposal(): HasOne
    {
        return $this->latestDisposal()
            ->where('disposal_date', '<=', Carbon::now())
            ->whereNull('disposal_end_date');
    }

    public function educationRequests(): HasMany
    {
        return $this->hasMany(PersonnelEducationRequest::class, 'tabel_no', 'tabel_no')->orderByDesc('request_date');
    }

    public function latestEducationRequest(): HasOne
    {
        return $this->hasOne(PersonnelEducationRequest::class, 'tabel_no', 'tabel_no')->latestOfMany('request_date');
    }

    public function masterDegrees(): HasMany
    {
        return $this->hasMany(PersonnelMasterDegree::class, 'tabel_no', 'tabel_no');
    }

    public function latestMasterDegree(): HasOne
    {
        return $this->hasOne(PersonnelMasterDegree::class, 'tabel_no', 'tabel_no')->latestOfMany('given_date');
    }

    public function captives(): HasMany
    {
        return $this->hasMany(PersonnelTakenCaptive::class, 'tabel_no', 'tabel_no')->orderByDesc('taken_captive_date');
    }

    public function socialOrigin(): BelongsTo
    {
        return $this->belongsTo(SocialOrigin::class, 'social_origin_id', 'id');
    }

    public function inActiveVacation(): HasOne
    {
        return $this->hasMany(PersonnelVacation::class, 'tabel_no', 'tabel_no')
            ->where('end_date', '>', Carbon::now())
            ->where('return_work_date', '>', Carbon::now())
            ->one();
    }

    public function inActiveBusinessTrip(): HasOne
    {
        return $this->hasMany(PersonnelBusinessTrip::class, 'tabel_no', 'tabel_no')
            ->where('end_date', '>', Carbon::now())
            ->one();
    }

    public function getAgeAttribute()
    {
        return Carbon::parse($this->birthdate)->age;
    }

    public function scopeActive($query)
    {
        return $query->where('is_pending', false)
            ->whereNull('leave_work_date');
    }

    public function scopeFilter($query, array $filters)
    {
        foreach ($filters as $field => $value) {
            if($value)
            {
                if (is_array($value)) {
                    $this->applyRangeFilter($query, $field, $value);
                } else {
                    $this->applyExactFilter($query, $field, $value);
                }
            }
        }
        return $query;
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} personnel";
    }

    protected function applyRangeFilter($query, $field, array $value)
    {
        if ($field === 'age') {
            $query->whereRaw('timestampdiff(year, birthdate, curdate()) between ? and ?', [$value['min'], $value['max']]);
        } elseif ($field === 'rank') {
            $query->whereHas('ranks', fn ($q) => $q->whereBetween('given_date', [$value['min'], $value['max']]));
        } elseif (in_array($field, $this->fillable)) {
            $min = empty($value['min']) ? '1990-01-01' : Carbon::parse($value['min'])->format('Y-m-d');
            $max = empty($value['max']) ? Carbon::now()->format('Y-m-d') : Carbon::parse($value['max'])->format('Y-m-d');
            $query->whereBetween($field, [$min, $max]);
        }
    }

    protected function applyExactFilter($query, $field, $value)
    {
        switch ($field) {
            case 'nationality_id':
                $query->whereHas('nationality', fn ($q) => $q->where('nationality_id', $value));
                break;

            case 'born_country_id':
            case 'born_city_id':
            case 'is_married':
                $query->whereHas('idDocuments', fn ($q) => $q->where($field, $value));
                break;

            case 'rank_id':
                $query->whereHas('ranks', fn ($q) => $q->where($field, $value));
                break;

            case 'rank_name':
                $query->whereHas('ranks', fn ($q) => $q->where('name', $value));
                break;

            case 'punishment_reason':
                $query->whereHas('punishments', fn ($q) => $q->where('reason', 'LIKE', "%$value%"));
                break;

            case 'educational_institution_id':
            case 'specialty':
                $query->whereHas('education', fn ($q) => $q->where($field, $value));
                break;

            case 'award_id':
                $query->whereHas('awards', fn ($q) => $q->where($field, $value));
                break;

            case 'punishment_id':
                $query->whereHas('punishments', fn ($q) => $q->where($field, $value));
                break;

            case 'structure_id':
                $this->applyStructureFilter($query, $value);
                break;

            default:
                if (in_array($field, $this->likeFilterFields) && $value !== null) {
                    $query->where($field, 'LIKE', "%$value%");
                } elseif (in_array($field, $this->fillable) && $value !== null) {
                    $query->where($field, $value);
                }
                break;
        }
    }

    protected function applyStructureFilter($query, $value)
    {
        $structureIds = $this->getNestedStructure($value);
        $query->whereIn('structure_id', $structureIds);
    }

     public function scopeNameLike(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('surname', 'like', "%{$term}%");
        });
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($model) => $model->added_by = auth()->id() ?? 1);
        static::deleting(fn ($model) => $model->forceFill(['deleted_by' => auth()->id() ?? 1])->save());
    }
}
