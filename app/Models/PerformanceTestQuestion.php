<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerformanceTestQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'performance_test_bank_id',
        'training_competency_id',
        'question_type',
        'prompt',
        'description',
        'max_score',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'max_score' => 'decimal:2',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function bank(): BelongsTo
    {
        return $this->belongsTo(PerformanceTestBank::class, 'performance_test_bank_id');
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(TrainingCompetency::class, 'training_competency_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(PerformanceTestQuestionOption::class)->orderBy('sort_order')->orderBy('id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(PerformanceTestAttemptAnswer::class, 'performance_test_question_id');
    }

    public function isAutoScored(): bool
    {
        return $this->question_type === 'multiple_choice';
    }
}
