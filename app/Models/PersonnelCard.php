<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonnelCard extends Model
{
    use HasFactory;
    use PersonnelTrait;
    use DateCastTrait;

    protected $fillable = [
        'tabel_no',
        'card_number',
        'given_date',
        'valid_date',
    ];

    protected $dates = ['given_date', 'valid_date'];

    protected $casts = [
        'valid_date' => self::FORMAT_CAST,
        'given_date' => self::FORMAT_CAST,
    ];
}
