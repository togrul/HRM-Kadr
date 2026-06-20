<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonnelEventRecord extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'personnel_id',
        'event_type',
        'participation_role',
        'title',
        'topic',
        'organizer_name',
        'start_date',
        'end_date',
        'location',
        'country_id',
        'attendance_format',
        'strategic_level',
        'result_summary',
        'impact_summary',
        'source_url',
        'registry_key',
        'visibility',
        'certificate_attachment_id',
        'agenda_attachment_id',
        'verification_status',
        'hr_value_reason',
        'entered_by',
        'verified_by',
        'verified_at',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'verified_at' => 'datetime',
    ];

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    public function certificateAttachment(): BelongsTo
    {
        return $this->belongsTo(ProfessionalRecordAttachment::class, 'certificate_attachment_id');
    }

    public function agendaAttachment(): BelongsTo
    {
        return $this->belongsTo(ProfessionalRecordAttachment::class, 'agenda_attachment_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('verification_status', self::STATUS_VERIFIED);
    }
}
