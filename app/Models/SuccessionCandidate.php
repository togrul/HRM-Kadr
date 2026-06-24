<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuccessionCandidate extends Model
{
    public const READINESS = ['ready_now', '1_2_years', '3_5_years'];

    protected $fillable = [
        'succession_plan_id',
        'personnel_id',
        'readiness',
        'sort_order',
        'note',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SuccessionPlan::class, 'succession_plan_id');
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'personnel_id');
    }
}
