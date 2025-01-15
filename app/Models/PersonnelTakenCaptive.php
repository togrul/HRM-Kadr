<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonnelTakenCaptive extends Model
{
    use DateCastTrait,HasFactory,PersonnelTrait;

    protected $fillable = [
        'tabel_no',
        'location',
        'condition',
        'taken_captive_date',
        'release_date',
    ];

    protected $dates = [
        'taken_captive_date',
        'release_date',
    ];

    protected $casts = [
        'taken_captive_date' => self::FORMAT_CAST,
        'release_date' => self::FORMAT_CAST,
    ];
}
