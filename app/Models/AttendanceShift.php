<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceShift extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'break_minutes',
        'is_night_shift',
        'in_flex_before_minutes',
        'in_flex_after_minutes',
        'out_flex_before_minutes',
        'out_flex_after_minutes',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'break_minutes' => 'integer',
        'is_night_shift' => 'boolean',
        'in_flex_before_minutes' => 'integer',
        'in_flex_after_minutes' => 'integer',
        'out_flex_before_minutes' => 'integer',
        'out_flex_after_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(AttendanceShiftAssignment::class, 'shift_id');
    }
}

