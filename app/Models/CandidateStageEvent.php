<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateStageEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_application_id',
        'stage_key',
        'action',
        'decision',
        'score',
        'actor_id',
        'payload',
        'occurred_at',
        'note',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'payload' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(CandidateApplication::class, 'candidate_application_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
