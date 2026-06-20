<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChiefDelegation extends Model
{
    use HasFactory;

    protected $fillable = [
        'chief_personnel_id',
        'delegate_personnel_id',
        'starts_at',
        'ends_at',
        'reason',
        'basis_order_id',
        'basis_document',
        'is_active',
        'created_by',
        'revoked_at',
        'revoked_by',
    ];

    protected $casts = [
        'starts_at' => 'date:Y-m-d',
        'ends_at' => 'date:Y-m-d',
        'revoked_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function chief(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'chief_personnel_id');
    }

    public function delegate(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'delegate_personnel_id');
    }

    public function basisOrder(): BelongsTo
    {
        return $this->belongsTo(OrderLog::class, 'basis_order_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }
}
