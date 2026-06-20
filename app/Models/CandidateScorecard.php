<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateScorecard extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_interview_id',
        'reviewer_id',
        'criterion',
        'score',
        'comment',
    ];

    protected $casts = [
        'score' => 'integer',
    ];

    public function interview(): BelongsTo
    {
        return $this->belongsTo(CandidateInterview::class, 'candidate_interview_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
