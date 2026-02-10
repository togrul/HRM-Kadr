<?php

namespace App\Models;

use App\Traits\DateCastTrait;
use App\Traits\PersonnelTrait;
use Carbon\Carbon;
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

    public function getPositionLabelAttribute(): string
    {
        $label = (string) ($this->position ?? '');
        if ($label === '') {
            return '';
        }
        $personnel = $this->relationLoaded('personnel') ? $this->personnel : null;

        if (! $personnel) {
            return $label;
        }

        $start = $this->join_date ? Carbon::parse($this->join_date) : Carbon::now();

        $isCurrent = (bool) ($this->is_current ?? false);
        if (! $isCurrent || $this->leave_date) {
            return $label;
        }

        // Tag only if this activity is the person's current work record.
        $currentWork = $personnel->relationLoaded('currentWork')
            ? $personnel->currentWork
            : null;

        if (! $currentWork || $currentWork->id !== $this->id) {
            return $label;
        }

        return $personnel->disposalTaggedLabel($label, $start, true);
    }
}
