<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $name
 * @property string $cycle_type
 * @property \Illuminate\Support\Carbon|null $period_start
 * @property \Illuminate\Support\Carbon|null $period_end
 * @property string $status
 * @property bool $auto_generate_forms
 * @property string|null $description
 */
class PerformanceCycle extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'name',
        'cycle_type',
        'period_start',
        'period_end',
        'status',
        'auto_generate_forms',
        'description',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'auto_generate_forms' => 'boolean',
    ];

    /**
     * A closed cycle is read-only: its forms and scores can no longer change.
     */
    public static function isClosed(int|string|null $cycleId): bool
    {
        return $cycleId !== null
            && static::query()->whereKey($cycleId)->where('status', 'closed')->exists();
    }

    public function forms(): HasMany
    {
        return $this->hasMany(PerformanceForm::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('performance_cycle')
            ->logFillable()
            ->logOnlyDirty();
    }
}
