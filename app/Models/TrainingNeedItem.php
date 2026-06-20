<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TrainingNeedItem extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'personnel_id',
        'training_competency_id',
        'position_id',
        'recommended_program_id',
        'target_level_id',
        'priority',
        'source',
        'status',
        'target_completion_date',
        'reason',
        'plan_note',
    ];

    protected $casts = [
        'target_completion_date' => 'date',
    ];

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(TrainingCompetency::class, 'training_competency_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function recommendedProgram(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class, 'recommended_program_id');
    }

    public function targetLevel(): BelongsTo
    {
        return $this->belongsTo(TrainingLevel::class, 'target_level_id');
    }

    public function sessionParticipants(): HasMany
    {
        return $this->hasMany(TrainingSessionParticipant::class, 'training_need_item_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('training_need_item')
            ->logFillable()
            ->logOnlyDirty();
    }

    public function presentedPlanNote(): string
    {
        return match ((string) $this->plan_note) {
            'Auto-created from performance weak area.' => __('training_needs::dashboard.messages.auto_created_performance_weak_area'),
            'Auto-created from skill measurement weak area.' => __('training_needs::dashboard.messages.auto_created_skill_weak_area'),
            default => (string) $this->plan_note,
        };
    }

    public function presentedReason(): string
    {
        $reason = (string) ($this->reason ?? '');

        if ($reason === '') {
            return $reason;
        }

        if (preg_match('/^Low performance score detected on form #(\d+), item #(\d+): ([0-9.]+)$/', $reason, $matches) === 1) {
            return __('performance_evaluation::dashboard.messages.performance_gap_reason', [
                'form' => $matches[1],
                'item' => $matches[2],
                'score' => $matches[3],
            ]);
        }

        if (preg_match('/^Weak test result detected on attempt #(\d+) for competency #(\d+): ([0-9.]+)%$/', $reason, $matches) === 1) {
            return __('performance_evaluation::dashboard.messages.skill_gap_reason', [
                'attempt' => $matches[1],
                'competency' => $matches[2],
                'percentage' => $matches[3],
            ]);
        }

        return $reason;
    }
}
