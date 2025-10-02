<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PersonnelCard extends Model
{
    // xidmeti vesiqeler
    use HasFactory;
    use PersonnelTrait;
    use DateCastTrait;
    use LogsActivity;

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
        'card_number',
        'given_date',
        'valid_date',
    ];

    protected $dates = ['given_date', 'valid_date'];

    protected $casts = [
        'valid_date' => self::FORMAT_CAST,
        'given_date' => self::FORMAT_CAST,
    ];
}
