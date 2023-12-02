<?php

namespace App\Models;

use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PersonnelLaborActivity extends Model
{
    use HasFactory,PersonnelTrait;

    public $timestamps = false;

    protected $fillable = [
        'tabel_no',
        'company_name',
        'position',
        'join_date',
        'leave_date'
    ];

    protected $dates = [
        'join_date',
        'leave_date'
    ];
}
