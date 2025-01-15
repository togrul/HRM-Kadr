<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonnelMasterDegree extends Model
{
    use DateCastTrait;
    use HasFactory;
    use PersonnelTrait;

    protected $fillable = [
        'tabel_no',
        'degree',
        'approved_date',
        'given_date',
        'redemption_date',
    ];

    protected $dates = [
        'approved_date',
        'given_date',
        'redemption_date',
    ];

    protected $casts = [
        'approved_date' => self::FORMAT_CAST,
        'given_date' => self::FORMAT_CAST,
        'redemption_date' => self::FORMAT_CAST,
    ];
}
