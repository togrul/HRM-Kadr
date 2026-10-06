<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatutoryRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'regime_id',
        'component_code',
        'payer',
        'base',
        'brackets',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'brackets' => 'array',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function regime(): BelongsTo
    {
        return $this->belongsTo(CompensationRegime::class, 'regime_id');
    }
}
