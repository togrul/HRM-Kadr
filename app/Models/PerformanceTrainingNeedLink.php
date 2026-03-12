<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceTrainingNeedLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'performance_form_id',
        'performance_form_score_id',
        'training_need_item_id',
        'training_competency_id',
        'source',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(PerformanceForm::class, 'performance_form_id');
    }

    public function score(): BelongsTo
    {
        return $this->belongsTo(PerformanceFormScore::class, 'performance_form_score_id');
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
