<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PersonnelScientificDegreeAndName extends Model
{
    use HasFactory,PersonnelTrait,DateCastTrait;

    public $timestamps = false;

    protected $fillable = [
        'tabel_no',
        'degree_and_name_id',
        'science',
        'given_date',
        'subject',
        'edu_doc_type_id',
        'diplom_serie',
        'diplom_no',
        'diplom_given_date',
        'document_issued_by'
    ];

    protected $dates = [
        'diplom_given_date',
        'given_date',
    ];

    protected $casts = [
        'diplom_given_date' => 'date:d.m.Y',
        'given_date' => 'date:d.m.Y'
    ];

    public function degreeAndName() : BelongsTo
    {
        return $this->belongsTo(ScientificDegreeAndName::class,'degree_and_name_id','id');
    }

    public function documentType() : BelongsTo
    {
        return $this->belongsTo(EducationDocumentType::class,'edu_doc_type_id','id');
    }
}
