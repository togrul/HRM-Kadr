<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeContentView extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'opened_at',
        'completed_at',
        'watch_progress_percent',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'completed_at' => 'datetime',
        'watch_progress_percent' => 'integer',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(EmployeeContentAssignment::class, 'assignment_id');
    }
}
