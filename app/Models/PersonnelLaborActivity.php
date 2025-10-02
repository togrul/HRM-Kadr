<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PersonnelLaborActivity extends Model
{
    use DateCastTrait,HasFactory, LogsActivity,PersonnelTrait;

    public $timestamps = false;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('personnel')
            ->dontSubmitEmptyLogs();
    }

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
        'join_date' => self::FORMAT_CAST,
        'leave_date' => self::FORMAT_CAST,
        'order_date' => self::FORMAT_CAST,
    ];
}
