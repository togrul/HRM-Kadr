<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'trigger',
        'template_id',
        'title',
        'channel',
        'audience_config',
        'payload',
        'format',
        'status',
        'approval_status',
        'scheduled_at',
        'approved_at',
        'approved_by',
        'created_by',
    ];

    protected $casts = [
        'audience_config' => 'array',
        'payload' => 'array',
        'scheduled_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function dispatches(): HasMany
    {
        return $this->hasMany(NotificationDispatch::class, 'campaign_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(NotificationApproval::class, 'campaign_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
