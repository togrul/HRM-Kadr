<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single rater's score (and optional comment) for one template competency item.
 */
class PerformanceFeedbackScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'performance_feedback_rater_id',
        'performance_form_template_item_id',
        'score',
        'comment',
    ];

    protected $casts = [
        'score' => 'decimal:2',
    ];

    public function rater(): BelongsTo
    {
        return $this->belongsTo(PerformanceFeedbackRater::class, 'performance_feedback_rater_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(PerformanceFormTemplateItem::class, 'performance_form_template_item_id');
    }
}
