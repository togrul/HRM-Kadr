<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $payslip_id
 * @property int|null $component_id
 * @property string $code
 * @property string $name
 * @property string $kind
 * @property float|string $amount
 * @property bool $taxable
 * @property bool $affects_social
 * @property bool $is_statutory
 * @property int $sort
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
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

    /** @return BelongsTo<Payslip, $this> */
    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class, 'payslip_id');
    }

    /** @return BelongsTo<CompensationComponent, $this> */
    public function component(): BelongsTo
    {
        return $this->belongsTo(CompensationComponent::class, 'component_id');
    }
}
