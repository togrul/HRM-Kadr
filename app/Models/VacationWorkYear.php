<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Bir işçinin bir iş ili üzrə məzuniyyət hüququ (ƏM m.113.3). Qalıq = entitled_days +
 * hərəkətlərin (VacationBalanceEntry) cəmi.
 *
 * @property int $id
 * @property string $tabel_no
 * @property int $sequence
 * @property \Illuminate\Support\Carbon $starts_on
 * @property \Illuminate\Support\Carbon $ends_on
 * @property int $entitled_days
 * @property array<string, mixed>|null $breakdown
 * @property string $strategy civil|ranked|legacy|opening
 * @property int|null $legacy_vacation_id
 * @property int|null $reserved_month
 */
class VacationWorkYear extends Model
{
    public const STRATEGY_LEGACY = 'legacy';

    public const STRATEGY_OPENING = 'opening';

    protected $fillable = [
        'tabel_no',
        'sequence',
        'starts_on',
        'ends_on',
        'entitled_days',
        'breakdown',
        'strategy',
        'legacy_vacation_id',
        'reserved_month',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'starts_on' => 'date',
        'ends_on' => 'date',
        'entitled_days' => 'integer',
        'breakdown' => 'array',
        'legacy_vacation_id' => 'integer',
        'reserved_month' => 'integer',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(VacationBalanceEntry::class, 'work_year_id');
    }

    /** Hüququ hesablama ilə yox, köçürülmüş məlumatla müəyyən olunan iş ili. */
    public function isImported(): bool
    {
        return in_array($this->strategy, [self::STRATEGY_LEGACY, self::STRATEGY_OPENING], true);
    }
}
