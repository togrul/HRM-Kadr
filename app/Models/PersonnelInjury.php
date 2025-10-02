<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PersonnelInjury extends Model
{
    use DateCastTrait,HasFactory,LogsActivity,PersonnelTrait;

    protected $fillable = [
        'tabel_no',
        'injury_type',
        'location',
        'date_time',
        'description',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('personnel')
            ->dontSubmitEmptyLogs();
    }

    protected $dates = [
        'date_time',
    ];

    protected $casts = [
        'date_time' => self::FORMAT_CAST,
    ];

    public $timestamps = false;
}
