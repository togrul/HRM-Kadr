<?php

namespace App\Models;

use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceDailyLedger extends Model
{
    use HasFactory;
    use PersonnelTrait;

    protected $fillable = [
        'tabel_no',
        'date',
        'shift_id',
        'scheduled_minutes',
        'worked_minutes',
        'break_minutes',
        'overtime_minutes',
        'late_minutes',
        'early_leave_minutes',
        'attendance_status',
        'absence_code',
        'source_summary',
        'is_locked',
        'approved_by',
        'approved_at',
        'meta',
    ];

    protected $casts = [
        'date' => 'date',
        'scheduled_minutes' => 'integer',
        'worked_minutes' => 'integer',
        'break_minutes' => 'integer',
        'overtime_minutes' => 'integer',
        'late_minutes' => 'integer',
        'early_leave_minutes' => 'integer',
        'is_locked' => 'boolean',
        'approved_at' => 'datetime',
        'meta' => 'array',
    ];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(AttendanceShift::class, 'shift_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

