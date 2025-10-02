<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PersonnelMilitaryService extends Model
{
    use DateCastTrait,HasFactory,LogsActivity,PersonnelTrait;

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
        'attitude_to_military_service',
        'rank_id',
        'given_date',
        'start_date',
        'end_date',
    ];

    protected $dates = [
        'given_date',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'given_date' => self::FORMAT_CAST,
        'start_date' => self::FORMAT_CAST,
        'end_date' => self::FORMAT_CAST,
    ];

    public function rank(): BelongsTo
    {
        return $this->belongsTo(Rank::class);
    }
}
