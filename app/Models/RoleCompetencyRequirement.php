<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleCompetencyRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'position_id',
        'training_competency_id',
        'required_level_id',
        'priority',
        'is_mandatory',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
    ];

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(TrainingCompetency::class, 'training_competency_id');
    }

    public function requiredLevel(): BelongsTo
    {
        return $this->belongsTo(TrainingLevel::class, 'required_level_id');
    }
}
