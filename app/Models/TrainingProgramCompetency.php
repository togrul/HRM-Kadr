<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingProgramCompetency extends Model
{
    use HasFactory;

    protected $table = 'training_program_competency_map';

    protected $fillable = [
        'training_program_id',
        'training_competency_id',
        'target_level_id',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(TrainingCompetency::class, 'training_competency_id');
    }

    public function targetLevel(): BelongsTo
    {
        return $this->belongsTo(TrainingLevel::class, 'target_level_id');
    }
}
