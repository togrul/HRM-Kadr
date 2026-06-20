<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingFeedbackForm extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_session_id',
        'title',
        'status',
        'questions',
    ];

    protected $casts = [
        'questions' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'training_session_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(TrainingFeedbackResponse::class)->latest('id');
    }
}
