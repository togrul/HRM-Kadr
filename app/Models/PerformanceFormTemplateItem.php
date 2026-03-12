<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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

    public function section(): BelongsTo
    {
        return $this->belongsTo(PerformanceFormTemplateSection::class, 'performance_form_template_section_id');
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(TrainingCompetency::class, 'training_competency_id');
    }

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
