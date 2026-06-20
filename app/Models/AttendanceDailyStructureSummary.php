<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceDailyStructureSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'structure_id',
        'ledger_rows',
        'scheduled_days',
        'present_days',
        'absence_days',
        'compliant_days',
        'scheduled_minutes_sum',
        'worked_minutes_sum',
        'overtime_minutes_sum',
        'late_minutes_sum',
        'early_leave_minutes_sum',
    ];

    protected $casts = [
        'date' => 'date',
        'structure_id' => 'integer',
        'ledger_rows' => 'integer',
        'scheduled_days' => 'integer',
        'present_days' => 'integer',
        'absence_days' => 'integer',
        'compliant_days' => 'integer',
        'scheduled_minutes_sum' => 'integer',
        'worked_minutes_sum' => 'integer',
        'overtime_minutes_sum' => 'integer',
        'late_minutes_sum' => 'integer',
        'early_leave_minutes_sum' => 'integer',
    ];
}
