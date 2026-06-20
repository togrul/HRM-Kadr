<?php

namespace App\Models;

use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceMonthlySummary extends Model
{
    use HasFactory;
    use PersonnelTrait;

    protected $fillable = [
        'tabel_no',
        'year',
        'month',
        'total_scheduled_minutes',
        'total_worked_minutes',
        'total_overtime_minutes',
        'total_absence_minutes',
        'total_workdays',
        'total_present_days',
        'total_absence_days',
        'is_locked',
        'calculated_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'total_scheduled_minutes' => 'integer',
        'total_worked_minutes' => 'integer',
        'total_overtime_minutes' => 'integer',
        'total_absence_minutes' => 'integer',
        'total_workdays' => 'integer',
        'total_present_days' => 'integer',
        'total_absence_days' => 'integer',
        'is_locked' => 'boolean',
        'calculated_at' => 'datetime',
    ];
}

