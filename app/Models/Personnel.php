<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Personnel extends Model
{
    use HasFactory,SoftDeletes;

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
        'disability_id',
        'disability_given_date',
        'extra_important_information',
        'computer_knowledge',
        'added_by',
        'deleted_by'
    ];

    protected $dates = [
        'join_work_date',
        'leave_work_date'
    ];

    protected $likeFilterFields = [
        'surname','name','patronymic','tabel_no','pin'
    ];

    public function getFullnameAttribute()
    {
        return "{$this->surname} {$this->name} {$this->patronymic}";
    }

    // public function nationality() : BelongsTo
    // {
    //     return $this->belongsTo(Country::class,'nationality_id','id');
    // }


    public function personDidDelete() : BelongsTo
    {
         return $this->belongsTo(User::class,'deleted_by','id');
    }

    public function nationality() : BelongsTo
    {
        return $this->belongsTo(CountryTranslation::class,'nationality_id','country_id')->where('locale',config('app.locale'));
    }

    public function previousNationality() : BelongsTo
    {
        return $this->belongsTo(CountryTranslation::class,'previous_nationality_id','country_id')->where('locale',config('app.locale'));
    }

    public function educationDegree() : BelongsTo
    {
        return $this->belongsTo(EducationDegree::class,'education_degree_id','id');
    }

    public function structure() : BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    public function position() : BelongsTo
    {
        return $this->belongsTo(Position::class,'position_id','id');
    }

    public function disability() : BelongsTo
    {
        return $this->belongsTo(Disability::class,'disability_id','id');
    }

    public function workNorm() : BelongsTo
    {
        return $this->belongsTo(WorkNorm::class,'work_norm_id','id');
    }

    public function creator() : BelongsTo
    {
        return $this->belongsTo(User::class,'added_by','id');
    }

    public function deletedBy() : BelongsTo
    {
        return $this->belongsTo(User::class,'deleted_by','id');
    }

    public function awards() : HasMany
    {
        return $this->hasMany(PersonnelAward::class,'tabel_no','tabel_no');
    }

    public function criminals() : HasMany
    {
        return $this->hasMany(PersonnelCriminal::class,'tabel_no','tabel_no');
    }

    public function idDocuments() : HasOne
    {
        return $this->hasOne(PersonnelIdentityDocument::class,'tabel_no','tabel_no');
    }

    public function education() : HasOne
    {
        return $this->hasOne(PersonnelEducation::class,'tabel_no','tabel_no');
    }

    public function extraEducations() : HasMany
    {
        return $this->hasMany(PersonnelExtraEducation::class,'tabel_no','tabel_no');
    }

    public function foreignLanguages() : HasMany
    {
        return $this->hasMany(PersonnelForeignLanguage::class,'tabel_no','tabel_no');
    }

    public function kinships() : HasMany
    {
        return $this->hasMany(PersonnelKinship::class,'tabel_no','tabel_no');
    }

    public function laborActivities() : HasMany
    {
        return $this->hasMany(PersonnelLaborActivity::class,'tabel_no','tabel_no');
    }

    public function military() : HasMany
    {
        return $this->hasMany(PersonnelMilitaryService::class,'tabel_no','tabel_no');
    }

    public function participations() : HasMany
    {
        return $this->hasMany(PersonnelParticipationEvent::class,'tabel_no','tabel_no');
    }

    public function punishments() : HasMany
    {
        return $this->hasMany(PersonnelPunishment::class,'tabel_no','tabel_no');
    }

    public function ranks() : HasMany
    {
        return $this->hasMany(PersonnelRank::class,'tabel_no','tabel_no');
    }

    public function latestRank() : HasOne
    {
        return $this->hasOne(PersonnelRank::class,'tabel_no','tabel_no')->latestOfMany('given_date');
    }

    public function degreeAndNames() : HasMany
    {
        return $this->hasMany(PersonnelScientificDegreeAndName::class,'tabel_no','tabel_no');
    }

    public function getAgeAttribute() {
        return Carbon::parse($this->birthdate)->diffInYears(\Carbon\Carbon::now());
    }

    public function scopeFilter($query, array $filters)
    {
        // dd($filters);
        foreach ($filters as $field => $value) {
            if($field == 'age')
            {
                $query->whereRaw('timestampdiff(year, birthdate, curdate()) between ? and ?', [$value['min'],$value['max']]);
                continue;
            }
            if($field == 'nationality_id')
            {
                $query->whereHas('nationality',function($q) use($value){
                    $q->where('nationality_id',$value);
                });
                continue;
            }
            if($field == 'born_country_id' || $field == 'born_city_id' || $field == 'is_married')
            {
                $query->whereHas('idDocuments',function($q) use($value,$field){
                    $q->where($field,$value);
                });
                continue;
            }
            if($field == 'rank_id')
            {
                $query->whereHas('ranks',function($q) use($value,$field){
                    $q->where($field,$value);
                });
                continue;
            }
            if($field == 'rank_name')
            {
                $query->whereHas('ranks',function($q) use($value){
                    $q->where('name',$value);
                });
                continue;
            }
            if($field == 'rank')
            {
                $query->whereHas('ranks',function($q) use($value){
                    $q->whereBetween('given_date',[$value['min'],$value['max']]);
                });
                continue;
            }
            if($field == 'educational_institution_id' || $field == 'specialty')
            {
                $query->whereHas('education',function($q) use($value,$field){
                    $q->where($field,$value);
                });
                continue;
            }
            if($field == 'award_id')
            {
                $query->whereHas('awards',function($q) use($value,$field){
                    $q->where($field,$value);
                });
                continue;
            }
            if($field == 'punishment_id')
            {
                $query->whereHas('punishments',function($q) use($value,$field){
                    $q->where($field,$value);
                });
                continue;
            }
            if(in_array($field, $this->likeFilterFields) && $value != null) {
                $query->where($field, 'LIKE', "%$value%");
                continue;
            }
            else if(in_array($field, $this->fillable) && is_array($value))
            {
                $query->whereBetween($field,[$value['min'],$value['max']]);
                continue;
            } 
            else if (in_array($field, $this->fillable) && $value != null) 
            {
                if($field == 'structure_id')
                {
                    $structureModel = Structure::with('subs')->find($value);
                    if ($structureModel) {
                        $structure = $structureModel->getAllNestedIds();
                    }

                    $query->whereIn('structure_id', $structure);
                    continue;
                }
                else
                {
                    $query->where($field, $value);
                }
            }
        }
        return $query;
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->added_by = auth()->user()->id;
        });
        static::deleting(function ($model) {
            $model->deleted_by = auth()->user()->id;
            $model->save();
        });
    }



}
