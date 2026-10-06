<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeCompensationLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_compensation_id',
        'component_id',
        'amount',
        'percent',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'percent' => 'decimal:2',
    ];

    public function compensation(): BelongsTo
    {
        return $this->belongsTo(EmployeeCompensation::class, 'employee_compensation_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(CompensationComponent::class, 'component_id');
    }
}
