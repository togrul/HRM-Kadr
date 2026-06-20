<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateTalentPoolEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'candidate_application_id',
        'pool_name',
        'status',
        'valid_until',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'valid_until' => 'date:Y-m-d',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(CandidateApplication::class, 'candidate_application_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
