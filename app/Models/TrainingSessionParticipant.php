<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingSessionParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_session_id',
        'personnel_id',
        'training_need_item_id',
        'attendance_status',
        'attended_at',
    ];

    protected $casts = [
        'attended_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'training_session_id');
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    public function trainingNeed(): BelongsTo
    {
        return $this->belongsTo(TrainingNeedItem::class, 'training_need_item_id');
    }
}
