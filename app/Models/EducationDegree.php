<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationDegree extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'title_az',
        'title_en',
        'title_ru',
    ];

    public $timestamps = false;
}
