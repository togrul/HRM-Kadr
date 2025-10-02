<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveStatusLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_id',
        'status_id',
        'changed_by',
        'comment',
        'changed_at'
    ];

    public $timestamps = false;

    public function leave(): BelongsTo
    {
        return $this->belongsTo(Leave::class, 'leave_id', 'id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'status_id', 'id');
    }

    public function changedBy(): BelongsTo
    {
         return $this->belongsTo(Personnel::class, 'changed_by', 'id');
    }
}
