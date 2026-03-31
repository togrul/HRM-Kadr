<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CandidateApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'job_opening_id',
        'candidate_source_id',
        'rejection_reason_id',
        'current_stage',
        'status',
        'assigned_recruiter_id',
        'final_decision',
        'applied_at',
        'moved_at',
        'rejected_at',
        'withdrawn_at',
        'hired_at',
        'note',
    ];

    protected $casts = [
        'applied_at' => 'datetime',
        'moved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'withdrawn_at' => 'datetime',
        'hired_at' => 'datetime',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function opening(): BelongsTo
    {
        return $this->belongsTo(JobOpening::class, 'job_opening_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(CandidateSource::class, 'candidate_source_id');
    }

    public function rejectionReason(): BelongsTo
    {
        return $this->belongsTo(CandidateRejectionReason::class, 'rejection_reason_id');
    }

    public function assignedRecruiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_recruiter_id');
    }

    public function stageEvents(): HasMany
    {
        return $this->hasMany(CandidateStageEvent::class)->orderBy('occurred_at')->orderBy('id');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(CandidateApplicationAssessment::class)->orderBy('stage_key')->orderBy('assessment_key');
    }

    public function documentChecks(): HasMany
    {
        return $this->hasMany(CandidateApplicationDocumentCheck::class)->orderBy('stage_key')->orderBy('document_key');
    }

    public function stageProfiles(): HasMany
    {
        return $this->hasMany(CandidateApplicationStageProfile::class)->orderBy('stage_key');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CandidateDocument::class, 'candidate_application_id')->orderBy('stage_key')->orderBy('document_key')->orderBy('id');
    }
}
