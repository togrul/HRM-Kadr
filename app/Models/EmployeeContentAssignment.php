<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EmployeeContentAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
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

    public function asset(): BelongsTo
    {
        return $this->belongsTo(EmployeeContentAsset::class, 'asset_id');
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function view(): HasOne
    {
        return $this->hasOne(EmployeeContentView::class, 'assignment_id');
    }
}
