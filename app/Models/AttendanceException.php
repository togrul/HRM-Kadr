<?php

namespace App\Models;

use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceException extends Model
{
    use HasFactory;
    use PersonnelTrait;

    protected $fillable = [
        'tabel_no',
        'date',
        'type',
        'status',
        'message',
        'resolution_note',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'date' => 'date',
        'resolved_at' => 'datetime',
    ];

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}

