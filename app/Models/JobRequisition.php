<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobRequisition extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'structure_id',
        'position_id',
        'profile_pack',
        'employment_type',
        'hiring_reason',
        'headcount',
        'status',
        'opens_at',
        'closes_at',
        'requested_by',
        'owner_id',
        'approved_at',
        'note',
    ];

    protected $casts = [
        'headcount' => 'integer',
        'opens_at' => 'date:Y-m-d',
        'closes_at' => 'date:Y-m-d',
        'approved_at' => 'datetime',
    ];

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function openings(): HasMany
    {
        return $this->hasMany(JobOpening::class);
    }
}
