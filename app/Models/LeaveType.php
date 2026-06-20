<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'attendance_code',
        'max_days',
        'requires_document'
    ];

    protected $casts = [
        'requires_document' => 'boolean'
    ];

    public function setAttendanceCodeAttribute(mixed $value): void
    {
        $normalized = strtoupper(trim((string) $value));

        $this->attributes['attendance_code'] = $normalized !== ''
            ? preg_replace('/\s+/', '', $normalized)
            : null;
    }
}
