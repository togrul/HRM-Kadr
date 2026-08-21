<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayslipLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'payslip_id',
        'component_id',
        'code',
        'name',
        'kind',
        'amount',
        'taxable',
        'affects_social',
        'is_statutory',
        'sort',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'taxable' => 'boolean',
        'affects_social' => 'boolean',
        'is_statutory' => 'boolean',
        'sort' => 'integer',
    ];

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class, 'payslip_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(CompensationComponent::class, 'component_id');
    }
}
