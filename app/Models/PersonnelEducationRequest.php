<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonnelEducationRequest extends Model
{
    use DateCastTrait;
    use HasFactory;
    use PersonnelTrait;

    protected $fillable = [
        'tabel_no',
        'education_place',
        'specialty',
        'description',
        'request_date',
        'request_result',
    ];

    protected $dates = ['request_date'];

    protected $casts = [
        'request_date' => self::FORMAT_CAST,
    ];
}
