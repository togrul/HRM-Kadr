<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PerformanceFormTemplateSection extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'performance_form_template_id',
        'name',
        'weight_percent',
        'sort_order',
    ];

    protected $casts = [
        'weight_percent' => 'decimal:2',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(PerformanceFormTemplate::class, 'performance_form_template_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PerformanceFormTemplateItem::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('performance_form_template_section')
            ->logFillable()
            ->logOnlyDirty();
    }
}
