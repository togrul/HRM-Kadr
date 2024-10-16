<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonnelExtraEducation extends Model
{
    use DateCastTrait,HasFactory,PersonnelTrait;

    public $timestamps = false;

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
        'diplom_given_date' => 'date:d.m.Y',
        'admission_year' => 'date:d.m.Y',
        'graduated_year' => 'date:d.m.Y',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(EducationType::class, 'education_type_id', 'id');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(EducationalInstitution::class, 'educational_institution_id', 'id');
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(EducationForm::class, 'education_form_id', 'id');
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(EducationDocumentType::class, 'education_document_type_id', 'id');
    }
}
