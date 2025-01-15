<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonnelPensionCard extends Model
{
    use DateCastTrait;
    use HasFactory;
    use PersonnelTrait;

    protected $fillable = [
        'tabel_no',
        'card_no',
        'given_date',
        'expiry_date',
    ];

    protected $dates = ['given_date', 'expiry_date'];

    protected $casts = [
        'given_date' => self::FORMAT_CAST,
        'expiry_date' => self::FORMAT_CAST,
    ];
}
