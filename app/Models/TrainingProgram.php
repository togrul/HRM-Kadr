<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'code',
        'delivery_type',
        'duration_hours',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'duration_hours' => 'decimal:2',
    ];

    public function competencyMappings(): HasMany
    {
        return $this->hasMany(TrainingProgramCompetency::class);
    }
}
