<?php

namespace App\Models;

use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonnelDocument extends Model
{
    use HasFactory,PersonnelTrait;

    protected $fillable = [
        'tabel_no',
        'file',
        'filename',
        'employee_visibility',
        'visible_from',
        'visible_until',
    ];

    protected $casts = [
        'visible_from' => 'datetime',
        'visible_until' => 'datetime',
    ];
}
