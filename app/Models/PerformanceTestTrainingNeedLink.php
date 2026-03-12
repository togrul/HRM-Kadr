<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceTestTrainingNeedLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'performance_test_attempt_id',
        'training_need_item_id',
        'training_competency_id',
        'source',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(PerformanceTestAttempt::class, 'performance_test_attempt_id');
    }

    public function trainingNeed(): BelongsTo
    {
        return $this->belongsTo(TrainingNeedItem::class, 'training_need_item_id');
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(TrainingCompetency::class, 'training_competency_id');
    }
}
