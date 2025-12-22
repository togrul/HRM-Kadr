<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
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

    public function getPositionLabelAttribute(): string
    {
        $label = (string) ($this->position ?? '');
        if ($label === '') {
            return '';
        }

        $personnel = $this->relationLoaded('personnel')
            ? $this->personnel
            : $this->personnel()->with('latestDisposal')->first();

        if (! $personnel) {
            return $label;
        }

        $start = $this->join_date ? Carbon::parse($this->join_date) : Carbon::now();
        $end = $this->leave_date ? Carbon::parse($this->leave_date) : Carbon::now();

        return $personnel->disposalTaggedLabel($label, $start, $end);
    }
}
