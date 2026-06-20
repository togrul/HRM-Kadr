<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SelfServiceApprovalRoute extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_type',
        'include_primary_approver',
        'include_upper_approver',
        'personnel_id',
        'structure_id',
        'position_id',
        'approver_personnel_id',
        'fallback_approver_personnel_id',
        'hr_always_included',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'include_primary_approver' => 'boolean',
        'include_upper_approver' => 'boolean',
        'hr_always_included' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'approver_personnel_id');
    }

    public function fallbackApprover(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'fallback_approver_personnel_id');
    }
}
