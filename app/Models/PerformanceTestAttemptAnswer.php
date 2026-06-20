<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PerformanceTestAttemptAnswer extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'performance_test_attempt_id',
        'performance_test_question_id',
        'selected_option_id',
        'answer_text',
        'is_correct',
        'auto_score',
        'review_score',
        'final_score',
        'review_status',
        'reviewed_by',
        'reviewed_at',
        'feedback',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'auto_score' => 'decimal:2',
        'review_score' => 'decimal:2',
        'final_score' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(PerformanceTestAttempt::class, 'performance_test_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(PerformanceTestQuestion::class, 'performance_test_question_id');
    }

    public function selectedOption(): BelongsTo
    {
        return $this->belongsTo(PerformanceTestQuestionOption::class, 'selected_option_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('performance_test_attempt_answer')
            ->logFillable()
            ->logOnlyDirty();
    }
}
