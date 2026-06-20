<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonnelMediaMention extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_BROKEN_LINK = 'broken_link';
    public const STATUS_ARCHIVED_ONLY = 'archived_only';

    protected $fillable = [
        'personnel_id',
        'headline',
        'publisher_name',
        'publisher_type',
        'mention_type',
        'published_at',
        'url',
        'publisher_registry_key',
        'summary',
        'sentiment',
        'language',
        'archive_attachment_id',
        'screenshot_attachment_id',
        'visibility',
        'verification_status',
        'link_check_status',
        'link_check_message',
        'link_check_http_code',
        'link_checked_at',
        'archive_health_status',
        'archive_health_message',
        'archive_checked_at',
        'entered_by',
        'verified_by',
        'verified_at',
        'notes',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'link_checked_at' => 'datetime',
        'archive_checked_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    public function archiveAttachment(): BelongsTo
    {
        return $this->belongsTo(ProfessionalRecordAttachment::class, 'archive_attachment_id');
    }

    public function screenshotAttachment(): BelongsTo
    {
        return $this->belongsTo(ProfessionalRecordAttachment::class, 'screenshot_attachment_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('verification_status', self::STATUS_VERIFIED);
    }
}
