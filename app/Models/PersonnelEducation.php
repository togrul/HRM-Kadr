<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PersonnelEducation extends Model
{
    use DateCastTrait, HasFactory, LogsActivity, PersonnelTrait;

    public $timestamps = false;

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
        'educational_institution_id',
        'education_form_id',
        'education_language',
        'specialty',
        'admission_year',
        'graduated_year',
        'profession_by_document',
        'diplom_serie',
        'diplom_no',
        'diplom_given_date',
        'coefficient',
        'calculate_as_seniority',
        'is_military',
    ];

    protected $dates = [
        'diplom_given_date',
        'admission_year',
        'graduated_year',
    ];

    protected $casts = [
        'diplom_given_date' => self::FORMAT_CAST,
        'admission_year' => self::FORMAT_CAST,
        'graduated_year' => self::FORMAT_CAST,
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(EducationalInstitution::class, 'educational_institution_id', 'id');
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(EducationForm::class, 'education_form_id', 'id');
    }
}
