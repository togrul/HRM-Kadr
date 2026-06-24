<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceGoalCheckin extends Model
{
    protected $fillable = [
        'performance_goal_id',
        'value',
        'note',
        'created_by',
    ];

    protected $casts = [
        'value' => 'decimal:2',
    ];

    public function goal(): BelongsTo
    {
        return $this->belongsTo(PerformanceGoal::class, 'performance_goal_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
