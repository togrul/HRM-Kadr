<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonnelElectedElectoral extends Model
{
    use HasFactory,PersonnelTrait,DateCastTrait;

    protected $fillable = [
        'election_type',
        'location',
        'elected_date',
    ];

    protected $dates = [
        'elected_date'
    ];

    protected $casts = [
        'elected_date' => 'date:d.m.Y',
    ];
}
