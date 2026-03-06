<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'scope_type',
        'scope_id',
        'timezone',
        'default_shift_id',
        'late_grace_minutes',
        'early_leave_grace_minutes',
        'rounding_policy',
        'rounding_step_minutes',
        'overtime_policy',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'scope_id' => 'integer',
        'default_shift_id' => 'integer',
        'late_grace_minutes' => 'integer',
        'early_leave_grace_minutes' => 'integer',
        'rounding_step_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    public function defaultShift(): BelongsTo
    {
        return $this->belongsTo(AttendanceShift::class, 'default_shift_id');
    }
}

