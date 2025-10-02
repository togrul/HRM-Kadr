<?php

namespace App\Models;

use App\Traits\PersonnelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Leave extends Model
{
    use HasFactory, PersonnelTrait, SoftDeletes;

    protected $fillable = [
        'tabel_no',
        'leave_type_id',
        'starts_at',
        'ends_at',
        'total_days',
        'reason',
        'status_id',
        'document_path',
        'approved_by',
        'approved_at',
        'deleted_at'
    ];

    protected $casts = [
        'starts_at' => 'date:d.m.Y',
        'ends_at' => 'date:d.m.Y',
        'approved_at' => 'datetime:d.m.Y H:i:s',
        'deleted_at' => 'datetime:d.m.Y H:i:s'
    ];

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'status_id', 'id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(LeaveStatusLog::class);
    }

    public function latestLog(): HasOne
    {
        return $this->hasOne(LeaveStatusLog::class)->latest('changed_at');
    }

    public function approver(): BelongsTo
    {
         return $this->belongsTo(Personnel::class, 'approved_by', 'id');
    }
}
