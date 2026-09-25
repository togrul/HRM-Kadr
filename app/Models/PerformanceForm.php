<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $performance_cycle_id
 * @property int $performance_form_template_id
 * @property int $personnel_id
 * @property int|null $manager_id
 * @property int|null $hr_reviewer_id
 * @property string $self_status
 * @property string $manager_status
 * @property string $hr_status
 * @property float|string|null $final_score
 * @property string|null $final_category
 * @property string $result_status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
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

    /** @return BelongsTo<PerformanceCycle, $this> */
    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PerformanceCycle::class, 'performance_cycle_id');
    }

    /** @return BelongsTo<PerformanceFormTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(PerformanceFormTemplate::class, 'performance_form_template_id');
    }

    /** @return BelongsTo<Personnel, $this> */
    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    /** @return BelongsTo<User, $this> */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /** @return BelongsTo<User, $this> */
    public function hrReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hr_reviewer_id');
    }

    /** @return HasMany<PerformanceFormScore, $this> */
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
