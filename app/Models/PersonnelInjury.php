<?php

namespace App\Models;

use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonnelInjury extends Model
{
    use HasFactory,PersonnelTrait;

    protected $fillable = [
        'tabel_no',
        'injury_type',
        'location',
        'date_time',
        'description'
    ];

    public $timestamps = false;
}
