<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TrainingSession extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'training_plan_item_id',
        'training_annual_plan_id',
        'training_program_id',
        'title',
        'scheduled_start_at',
        'scheduled_end_at',
        'location',
        'trainer_name',
        'capacity',
        'planned_budget',
        'auto_fill_participants',
        'status',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'scheduled_start_at' => 'datetime',
        'scheduled_end_at' => 'datetime',
        'completed_at' => 'datetime',
        'capacity' => 'integer',
        'planned_budget' => 'decimal:2',
        'auto_fill_participants' => 'boolean',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(TrainingAnnualPlan::class, 'training_annual_plan_id');
    }

    public function planItem(): BelongsTo
    {
        return $this->belongsTo(TrainingPlanItem::class, 'training_plan_item_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(TrainingSessionParticipant::class)->latest('id');
    }

    public function feedbackForms(): HasMany
    {
        return $this->hasMany(TrainingFeedbackForm::class)->latest('id');
    }

    public function deliveryRecords(): HasMany
    {
        return $this->hasMany(TrainingDeliveryRecord::class)->latest('id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('training_session')
            ->logFillable()
            ->logOnlyDirty();
    }
}
