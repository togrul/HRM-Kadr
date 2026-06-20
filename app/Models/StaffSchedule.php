<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StaffSchedule extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('staff_schedule')
            ->logOnly(['structure_id', 'position_id', 'total', 'filled', 'vacant'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public $timestamps = false;

    protected $fillable = [
        'structure_id',
        'position_id',
        'total',
        'filled',
        'vacant',
    ];

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id', 'id');
    }

    public function personnels(): HasMany
    {
        return $this->hasMany(Personnel::class, 'position_id', 'position_id')
            ->whereColumn('structure_id', 'staff_schedules.structure_id');
    }

    protected static function updateMainStructureVacancy(): void
    {
        $_mainStructure = StaffSchedule::where('structure_id', 1)->first();
        if ($_mainStructure) {
            $filled = Personnel::active()?->count() ?? 0;
            $vacant = max(0, (int) $_mainStructure->total - (int) $filled);

            if ((int) $_mainStructure->filled === (int) $filled && (int) $_mainStructure->vacant === $vacant) {
                return;
            }

            $_mainStructure->forceFill([
                'filled' => (int) $filled,
                'vacant' => $vacant,
            ])->saveQuietly();
        }
    }

    protected static function boot()
    {
        parent::boot();
        static::created(function ($model) {
            self::updateMainStructureVacancy();
        });
        static::updated(function ($model) {
            self::updateMainStructureVacancy();
        });
        static::deleted(function ($model) {
            self::updateMainStructureVacancy();
        });
    }
}
