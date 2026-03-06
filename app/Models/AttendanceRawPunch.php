<?php

namespace App\Models;

use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRawPunch extends Model
{
    use HasFactory;
    use PersonnelTrait;

    protected $fillable = [
        'tabel_no',
        'punched_at',
        'direction',
        'source',
        'device_ref',
        'external_id',
        'payload_hash',
        'meta',
        'is_processed',
        'processed_at',
    ];

    protected $casts = [
        'punched_at' => 'datetime',
        'processed_at' => 'datetime',
        'is_processed' => 'boolean',
        'meta' => 'array',
    ];
}

