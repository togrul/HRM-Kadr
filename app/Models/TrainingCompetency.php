<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingCompetency extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_competency_group_id',
        'name',
        'slug',
        'description',
        'is_mandatory',
        'is_active',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(TrainingCompetencyGroup::class, 'training_competency_group_id');
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(RoleCompetencyRequirement::class, 'training_competency_id');
    }
}
