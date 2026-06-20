<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateApplicationDocumentCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_application_id',
        'stage_key',
        'document_key',
        'is_provided',
        'note',
        'actor_id',
        'recorded_at',
    ];

    protected $casts = [
        'is_provided' => 'boolean',
        'recorded_at' => 'datetime',
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
