<?php

namespace App\Models;

use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceOvertimeRequest extends Model
{
    use HasFactory;
    use PersonnelTrait;
    use SoftDeletes;

    protected $fillable = [
        'tabel_no',
        'date',
        'requested_minutes',
        'approved_minutes',
        'status',
        'source',
        'reason',
        'requested_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'date' => 'date',
        'requested_minutes' => 'integer',
        'approved_minutes' => 'integer',
        'approved_at' => 'datetime',
    ];

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
