<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonnelParticipationEvent extends Model
{
    use HasFactory,PersonnelTrait,DateCastTrait;

    public $timestamps = false;

    protected $fillable = [
        'tabel_no',
        'event_type',
        'event_name',
        'event_date'
    ];

    protected $dates = [
        'event_date',
    ];

    protected $casts = [
        'event_date' => 'date:d.m.Y',
    ];
}
