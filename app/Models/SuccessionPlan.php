<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuccessionPlan extends Model
{
    public const RISK_LEVELS = ['low', 'medium', 'high'];

    protected $fillable = [
        'role_title',
        'position_id',
        'structure_id',
        'incumbent_personnel_id',
        'risk_of_loss',
        'impact_of_loss',
        'notes',
        'created_by',
    ];

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class, 'structure_id');
    }

    public function incumbent(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'incumbent_personnel_id');
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(SuccessionCandidate::class)->orderBy('sort_order')->orderBy('id');
    }
}
