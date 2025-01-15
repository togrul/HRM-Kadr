<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonnelInjury extends Model
{
    use DateCastTrait,HasFactory,PersonnelTrait;

    protected $fillable = [
        'tabel_no',
        'injury_type',
        'location',
        'date_time',
        'description',
    ];

    protected $dates = [
        'date_time',
    ];

    protected $casts = [
        'date_time' => self::FORMAT_CAST,
    ];

    public $timestamps = false;
}
