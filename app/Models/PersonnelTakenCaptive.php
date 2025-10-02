<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PersonnelTakenCaptive extends Model
{
    use DateCastTrait,HasFactory,LogsActivity,PersonnelTrait;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('personnel')
            ->dontSubmitEmptyLogs();
    }

    protected static $recordEvents = ['deleted','created','updated'];

    public function getDescriptionForEvent(string $eventName): string
    {
        return "You have {$eventName} personnel";
    }

    protected $fillable = [
        'tabel_no',
        'location',
        'condition',
        'taken_captive_date',
        'release_date',
    ];

    protected $dates = [
        'taken_captive_date',
        'release_date',
    ];

    protected $casts = [
        'taken_captive_date' => self::FORMAT_CAST,
        'release_date' => self::FORMAT_CAST,
    ];
}
