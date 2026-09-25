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
 * @property int $performance_form_template_id
 * @property string $name
 * @property float|string $weight_percent
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
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

    /** @return BelongsTo<PerformanceFormTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(PerformanceFormTemplate::class, 'performance_form_template_id');
    }

    /** @return HasMany<PerformanceFormTemplateItem, $this> */
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
