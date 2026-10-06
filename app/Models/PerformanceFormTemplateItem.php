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
 * @property int $performance_form_template_section_id
 * @property int|null $training_competency_id
 * @property string $name
 * @property string|null $description
 * @property float|string $weight_percent
 * @property float|string $low_score_threshold
 * @property bool $requires_comment
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PerformanceFormTemplateItem extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'performance_form_template_section_id',
        'training_competency_id',
        'name',
        'description',
        'weight_percent',
        'low_score_threshold',
        'requires_comment',
        'sort_order',
    ];

    protected $casts = [
        'weight_percent' => 'decimal:2',
        'low_score_threshold' => 'decimal:2',
        'requires_comment' => 'boolean',
    ];

    /** @return BelongsTo<PerformanceFormTemplateSection, $this> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(PerformanceFormTemplateSection::class, 'performance_form_template_section_id');
    }

    /** @return BelongsTo<TrainingCompetency, $this> */
    public function competency(): BelongsTo
    {
        return $this->belongsTo(TrainingCompetency::class, 'training_competency_id');
    }

    /** @return HasMany<PerformanceFormScore, $this> */
    public function scores(): HasMany
    {
        return $this->hasMany(PerformanceFormScore::class, 'performance_form_template_item_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('performance_form_template_item')
            ->logFillable()
            ->logOnlyDirty();
    }
}
