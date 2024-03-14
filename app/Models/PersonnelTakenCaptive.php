<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonnelTakenCaptive extends Model
{
    use HasFactory,PersonnelTrait,DateCastTrait;

    protected $fillable = [
        'location',
        'condition',
        'taken_captive_date',
        'release_date'
    ];

    protected $dates = [
        'taken_captive_date',
        'release_date'
    ];

    protected $casts = [
        'taken_captive_date' => 'date:d.m.Y',
        'release_date' => 'date:d.m.Y'
    ];
}
