<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingDocumentReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'opened_at',
        'acknowledged_at',
        'acknowledged_ip',
        'acknowledged_user_agent',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(OnboardingDocumentAssignment::class, 'assignment_id');
    }
}
