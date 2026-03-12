<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceTestQuestionOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'performance_test_question_id',
        'label',
        'is_correct',
        'score_value',
        'sort_order',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'score_value' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(PerformanceTestQuestion::class, 'performance_test_question_id');
    }
}
