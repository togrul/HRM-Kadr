<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TrainingAnnualPlan extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'title',
        'plan_year',
        'plan_quarter',
        'status',
        'estimated_budget',
        'planned_participants',
        'covered_need_count',
        'auto_generated',
        'notes',
    ];

    protected $casts = [
        'plan_year' => 'integer',
        'plan_quarter' => 'integer',
        'estimated_budget' => 'decimal:2',
        'planned_participants' => 'integer',
        'covered_need_count' => 'integer',
        'auto_generated' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(TrainingPlanItem::class)->orderByDesc('participant_count')->orderByDesc('need_count');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class)->latest('scheduled_start_at');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('training_annual_plan')
            ->logFillable()
            ->logOnlyDirty();
    }
}
