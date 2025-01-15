<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonnelPassports extends Model
{
    use HasFactory;
    use PersonnelTrait;
    use DateCastTrait;

    protected $fillable = [
        'tabel_no',
        'serial_number',
        'given_date',
        'valid_date',
    ];

    protected $dates = ['valid_date','given_date'];
}
