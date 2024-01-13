<?php

namespace App\Models;

use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonnelElectedElectoral extends Model
{
    use HasFactory,PersonnelTrait;

    protected $fillable = [
        'election_type',
        'location',
        'elected_date',
    ];

    protected $dates = [
        'elected_date'
    ];
}
