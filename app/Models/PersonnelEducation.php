<?php

namespace App\Models;

use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonnelEducation extends Model
{
    use HasFactory,PersonnelTrait;

    public $timestamps = false;

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
        'diplom_given_date'
    ];

    public function institution() : BelongsTo
    {
        return $this->belongsTo(EducationalInstitution::class,'educational_institution_id','id');
    }

    public function form() : BelongsTo
    {
        return $this->belongsTo(EducationForm::class,'education_form_id','id');
    }
}
