<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationDocumentType extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'education_document_types';

    protected $fillable = [
        'id',
        'name',
    ];
}
