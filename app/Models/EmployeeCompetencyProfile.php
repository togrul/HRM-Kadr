<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeCompetencyProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'personnel_id',
        'training_competency_id',
        'current_level_id',
        'source',
        'last_assessed_at',
    ];

    protected $casts = [
        'last_assessed_at' => 'datetime',
    ];

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(TrainingCompetency::class, 'training_competency_id');
    }

    public function currentLevel(): BelongsTo
    {
        return $this->belongsTo(TrainingLevel::class, 'current_level_id');
    }

    public function gapRequirements()
    {
        return $this->hasMany(RoleCompetencyRequirement::class, 'training_competency_id', 'training_competency_id');
    }
}
