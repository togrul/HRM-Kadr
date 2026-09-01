<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PayrollPeriod extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'code',
        'year',
        'month',
        'starts_on',
        'ends_on',
        'currency',
        'status',
        'note',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'starts_on' => 'date',
        'ends_on' => 'date',
    ];

    public function runs(): HasMany
    {
        return $this->hasMany(PayrollRun::class, 'payroll_period_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('payroll_period')->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
