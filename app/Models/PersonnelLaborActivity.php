<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonnelLaborActivity extends Model
{
    use DateCastTrait,HasFactory,PersonnelTrait;

    public $timestamps = false;

    protected $fillable = [
        'tabel_no',
        'company_name',
        'position',
        'coefficient',
        'join_date',
        'leave_date',
        'is_special_service',
        'order_given_by',
        'order_no',
        'order_date',
        'is_current',
    ];

    protected $dates = [
        'join_date',
        'leave_date',
        'order_date',
    ];

    protected $casts = [
        'join_date' => 'date:d.m.Y',
        'leave_date' => 'date:d.m.Y',
        'order_date' => 'date:d.m.Y',
    ];
}
