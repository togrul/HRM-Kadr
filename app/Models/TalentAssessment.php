<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TalentAssessment extends Model
{
    protected $fillable = [
        'personnel_id',
        'performance_cycle_id',
        'performance_level',
        'potential_level',
        'note',
        'assessed_by',
    ];

    protected $casts = [
        'performance_level' => 'integer',
        'potential_level' => 'integer',
    ];

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'personnel_id');
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PerformanceCycle::class, 'performance_cycle_id');
    }

    /**
     * 9-box index 1..9 — column = performance (1 low → 3 high), row = potential.
     * Box 9 = high performance + high potential (top-right star).
     */
    public function getBoxAttribute(): int
    {
        $perf = min(3, max(1, (int) $this->performance_level));
        $pot = min(3, max(1, (int) $this->potential_level));

        return ($pot - 1) * 3 + $perf;
    }
}
