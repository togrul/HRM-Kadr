<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OnboardingDocumentAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'personnel_id',
        'assigned_by',
        'assigned_at',
        'due_at',
        'last_reminder_at',
        'status',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'due_at' => 'datetime',
        'last_reminder_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(OnboardingDocumentTemplate::class, 'template_id');
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(OnboardingDocumentReceipt::class, 'assignment_id');
    }
}
