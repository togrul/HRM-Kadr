<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonnelProjectRecord extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'personnel_id',
        'project_name',
        'project_code',
        'project_type',
        'role_title',
        'responsibility_summary',
        'team_name',
        'sponsor_unit_id',
        'partner_organizations',
        'start_date',
        'end_date',
        'is_ongoing',
        'outcome_summary',
        'impact_summary',
        'reference_url',
        'registry_key',
        'evidence_attachment_id',
        'verification_status',
        'entered_by',
        'verified_by',
        'verified_at',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'verified_at' => 'datetime',
        'is_ongoing' => 'boolean',
    ];

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    public function sponsorUnit(): BelongsTo
    {
        return $this->belongsTo(Structure::class, 'sponsor_unit_id');
    }

    public function evidenceAttachment(): BelongsTo
    {
        return $this->belongsTo(ProfessionalRecordAttachment::class, 'evidence_attachment_id');
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
