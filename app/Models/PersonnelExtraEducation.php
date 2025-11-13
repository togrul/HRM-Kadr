<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PersonnelExtraEducation extends Model
{
    use DateCastTrait,HasFactory, LogsActivity, PersonnelTrait;

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
        'education_type_id',
        'educational_institution_id',
        'education_form_id',
        'name',
        'shortname',
        'education_language',
        'education_program_name',
        'admission_year',
        'graduated_year',
        'education_document_type_id',
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

    public function educationType(): BelongsTo
    {
        return $this->belongsTo(EducationType::class, 'education_type_id', 'id');
    }

    /**
     * Preserve the old $extraEducation->type accessors.
     */
    public function type(): BelongsTo
    {
        return $this->educationType();
    }

    public function educationalInstitution(): BelongsTo
    {
        return $this->belongsTo(EducationalInstitution::class, 'educational_institution_id', 'id');
    }

    public function educationForm(): BelongsTo
    {
        return $this->belongsTo(EducationForm::class, 'education_form_id', 'id');
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(EducationDocumentType::class, 'education_document_type_id', 'id');
    }

    /**
     * Backwards-compatible alias for legacy templates expecting $extraEducation->institution.
     */
    public function institution(): BelongsTo
    {
        return $this->educationalInstitution();
    }
}
