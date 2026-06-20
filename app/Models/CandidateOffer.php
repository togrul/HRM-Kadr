<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_application_id',
        'salary_amount',
        'currency',
        'start_date',
        'expires_at',
        'status',
        'terms',
        'created_by',
    ];

    protected $casts = [
        'salary_amount' => 'decimal:2',
        'start_date' => 'date:Y-m-d',
        'expires_at' => 'date:Y-m-d',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(CandidateApplication::class, 'candidate_application_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
