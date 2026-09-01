<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayScale extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'regime_id',
        'currency',
        'effective_from',
        'effective_to',
        'is_active',
        'description',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function regime(): BelongsTo
    {
        return $this->belongsTo(CompensationRegime::class, 'regime_id');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(PayGrade::class, 'pay_scale_id');
    }
}
