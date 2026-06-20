<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TrainingPlanItem extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'training_annual_plan_id',
        'training_competency_id',
        'training_program_id',
        'position_id',
        'target_level_id',
        'priority',
        'participant_count',
        'need_count',
        'estimated_budget',
        'source_mix',
        'review_status',
        'suggested_score',
        'review_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'participant_count' => 'integer',
        'need_count' => 'integer',
        'estimated_budget' => 'decimal:2',
        'suggested_score' => 'decimal:1',
        'reviewed_at' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(TrainingAnnualPlan::class, 'training_annual_plan_id');
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(TrainingCompetency::class, 'training_competency_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function targetLevel(): BelongsTo
    {
        return $this->belongsTo(TrainingLevel::class, 'target_level_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('training_plan_item')
            ->logFillable()
            ->logOnlyDirty();
    }
}
