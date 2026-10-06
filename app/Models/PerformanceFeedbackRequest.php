<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A 360° feedback campaign for one subject (ratee) within a performance cycle. It reuses
 * a form template's competency items as the rating criteria, gathers scores from multiple
 * raters (manager/peer/subordinate/self), then HR calibrates them into a final score.
 */
class PerformanceFeedbackRequest extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'performance_cycle_id',
        'performance_form_template_id',
        'subject_personnel_id',
        'is_anonymous',
        'due_date',
        'status',
        'final_score',
        'calibrated_scores',
        'calibration_status',
        'calibrated_by',
        'calibration_note',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'due_date' => 'date',
        'final_score' => 'decimal:2',
        'calibrated_scores' => 'array',
    ];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PerformanceCycle::class, 'performance_cycle_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(PerformanceFormTemplate::class, 'performance_form_template_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'subject_personnel_id');
    }

    public function calibrator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calibrated_by');
    }

    public function raters(): HasMany
    {
        return $this->hasMany(PerformanceFeedbackRater::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('performance_feedback_request')
            ->logFillable()
            ->logOnlyDirty();
    }
}
