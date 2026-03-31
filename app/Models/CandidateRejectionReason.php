<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CandidateRejectionReason extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'profile_pack',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function applications(): HasMany
    {
        return $this->hasMany(CandidateApplication::class, 'rejection_reason_id');
    }
}
