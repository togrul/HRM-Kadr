<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerformanceTestBank extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'pass_score',
        'duration_minutes',
        'max_attempts',
        'is_active',
    ];

    protected $casts = [
        'pass_score' => 'decimal:2',
        'duration_minutes' => 'integer',
        'max_attempts' => 'integer',
        'is_active' => 'boolean',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(PerformanceTestQuestion::class)->orderBy('sort_order')->orderBy('id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(PerformanceTestSession::class);
    }
}
