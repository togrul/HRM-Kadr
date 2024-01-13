<?php

namespace App\Models;

use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonnelTakenCaptive extends Model
{
    use HasFactory,PersonnelTrait;

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
}
