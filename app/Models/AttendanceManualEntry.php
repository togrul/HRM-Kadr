<?php

namespace App\Models;

use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceManualEntry extends Model
{
    use HasFactory;
    use PersonnelTrait;
    use SoftDeletes;

    protected $fillable = [
        'tabel_no',
        'date',
        'worked_minutes',
        'overtime_minutes',
        'check_in_at',
        'check_out_at',
        'late_minutes',
        'early_leave_minutes',
        'calculation_shift_source',
        'calculation_shift_id',
        'absence_code',
        'reason',
        'approval_status',
        'entered_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'date' => 'date',
        'worked_minutes' => 'integer',
        'overtime_minutes' => 'integer',
        'late_minutes' => 'integer',
        'early_leave_minutes' => 'integer',
        'calculation_shift_id' => 'integer',
        'approved_at' => 'datetime',
    ];

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function calculationShift(): BelongsTo
    {
        return $this->belongsTo(AttendanceShift::class, 'calculation_shift_id');
    }
}
