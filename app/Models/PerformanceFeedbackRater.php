<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One rater invited to a {@see PerformanceFeedbackRequest}: their relationship to the
 * subject (rater_type) and whether they have submitted their scores yet.
 */
class PerformanceFeedbackRater extends Model
{
    use HasFactory;

    /** @var array<int,string> */
    public const TYPES = ['manager', 'peer', 'subordinate', 'self'];

    protected $fillable = [
        'performance_feedback_request_id',
        'rater_personnel_id',
        'rater_user_id',
        'rater_type',
        'status',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(PerformanceFeedbackRequest::class, 'performance_feedback_request_id');
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'rater_personnel_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rater_user_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(PerformanceFeedbackScore::class);
    }
}
