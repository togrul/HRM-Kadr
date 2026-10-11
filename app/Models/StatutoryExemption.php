<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Gəlir vergisi azadolması/güzəşti (Vergi Məcəlləsi m.102), effektiv tarixli və rejim üzrə.
 *
 *  - fixed_amount: vergi tutulan aylıq gəlirdən `amount` çıxılır; `income_up_to` verilibsə yalnız gəlir
 *    həmin həddədək (daxil) olduqda; `main_workplace_only` — yalnız əsas iş yerində (VM 102.1.6, 102.8).
 *  - taxable_components: vergi bazasına yalnız `component_codes` siyahısındakı qazanclar daxil olur
 *    (VM 102.1.14.15 — hərbi qulluqçular).
 *  - personal: işçinin güzəşt kartı olduqda (VM 102.1-1, 102.2–102.5; m.102.7 — ən böyüyü).
 *
 * @property int $id
 * @property int|null $regime_id
 * @property string $code
 * @property string $legal_basis
 * @property string $kind
 * @property string $component_code
 * @property string|null $amount
 * @property string|null $income_up_to
 * @property bool $main_workplace_only
 * @property list<string>|null $component_codes
 * @property \Illuminate\Support\Carbon $effective_from
 * @property \Illuminate\Support\Carbon|null $effective_to
 * @property bool $is_active
 */
class StatutoryExemption extends Model
{
    use HasFactory;

    public const KIND_FIXED_AMOUNT = 'fixed_amount';

    public const KIND_TAXABLE_COMPONENTS = 'taxable_components';

    /**
     * İşçi üzrə güzəşt (VM m.102.1-1, 102.2–102.5): `amount` vergi tutulan aylıq gəlirdən yalnız işçinin
     * həmin ay qüvvədə olan güzəşt kartı (`employee_tax_reliefs.category` = `code`) olduqda çıxılır.
     */
    public const KIND_PERSONAL = 'personal';

    protected $fillable = [
        'regime_id',
        'code',
        'legal_basis',
        'kind',
        'component_code',
        'amount',
        'income_up_to',
        'main_workplace_only',
        'component_codes',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'income_up_to' => 'decimal:2',
        'main_workplace_only' => 'boolean',
        'component_codes' => 'array',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    /** @return BelongsTo<CompensationRegime, $this> */
    public function regime(): BelongsTo
    {
        return $this->belongsTo(CompensationRegime::class, 'regime_id');
    }
}
