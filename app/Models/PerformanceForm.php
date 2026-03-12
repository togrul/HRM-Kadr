<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PerformanceForm extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'performance_cycle_id',
        'performance_form_template_id',
        'personnel_id',
        'manager_id',
        'hr_reviewer_id',
        'self_status',
        'manager_status',
        'hr_status',
        'final_score',
        'final_category',
        'result_status',
    ];

    protected $casts = [
        'final_score' => 'decimal:2',
    ];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PerformanceCycle::class, 'performance_cycle_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(PerformanceFormTemplate::class, 'performance_form_template_id');
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function hrReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hr_reviewer_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(PerformanceFormScore::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('performance_form')
            ->logFillable()
            ->logOnlyDirty();
    }
}
