<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PerformanceFormScore extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'performance_form_id',
        'performance_form_template_item_id',
        'evaluator_type',
        'score',
        'comment',
    ];

    protected $casts = [
        'score' => 'decimal:2',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(PerformanceForm::class, 'performance_form_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(PerformanceFormTemplateItem::class, 'performance_form_template_item_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('performance_form_score')
            ->logFillable()
            ->logOnlyDirty();
    }
}
