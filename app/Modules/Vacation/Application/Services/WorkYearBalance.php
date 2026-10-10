<?php

namespace App\Modules\Vacation\Application\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Bir iş ilinin balansı: hüquq (tərkibi ilə), açılış qalığı, istifadə, geri çağırma ilə qaytarılan,
 * kompensasiya olunan, düzəliş və qalıq. `availableFrom` — günlərdən istifadə oluna biləcəyi ilk tarix.
 * `kind`: `annual` — ümumi iş ili, `conditions` — əmək şəraitinə görə əlavə məzuniyyətin öz iş ili
 * (NK 95, b.7).
 */
final class WorkYearBalance
{
    public function __construct(
        public readonly WorkYearPeriod $period,
        public readonly int $entitled,
        public readonly EntitlementBreakdown $breakdown,
        public readonly string $strategy,
        public readonly CarbonImmutable $availableFrom,
        public readonly int $opening = 0,
        public readonly int $used = 0,
        public readonly int $recalled = 0,
        public readonly int $compensated = 0,
        public readonly int $adjusted = 0,
        public readonly ?int $workYearId = null,
        public readonly ?int $reservedMonth = null,
        public readonly string $kind = 'annual',
    ) {}

    public function isConditions(): bool
    {
        return $this->kind === 'conditions';
    }

    /** Balans sətrinin açarı: növ + sıra nömrəsi. */
    public function key(): string
    {
        return $this->kind.':'.$this->period->sequence;
    }

    /** Hüquq + açılış qalığı + düzəlişlər. */
    public function total(): int
    {
        return $this->entitled + $this->opening + $this->adjusted;
    }

    /** İstifadə (geri çağırmada qaytarılanlar çıxılmaqla) + kompensasiya. */
    public function spent(): int
    {
        return $this->used - $this->recalled + $this->compensated;
    }

    public function remaining(): int
    {
        return $this->total() - $this->spent();
    }

    public function isAvailableOn(CarbonInterface $date): bool
    {
        return $this->period->start->toDateString() <= $date->toDateString()
            && $this->availableFrom->toDateString() <= $date->toDateString();
    }

    public function isImported(): bool
    {
        return in_array($this->strategy, ['legacy', 'opening'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(?CarbonInterface $on = null): array
    {
        return [
            'work_year_id' => $this->workYearId,
            'kind' => $this->kind,
            'sequence' => $this->period->sequence,
            'start' => $this->period->start->toDateString(),
            'end' => $this->period->end->toDateString(),
            'label' => $this->period->label(),
            'strategy' => $this->strategy,
            'breakdown' => $this->breakdown->toArray(),
            'entitled' => $this->entitled,
            'opening' => $this->opening,
            'adjusted' => $this->adjusted,
            'total' => $this->total(),
            'used' => $this->used - $this->recalled,
            'compensated' => $this->compensated,
            'remaining' => $this->remaining(),
            'available_from' => $this->availableFrom->toDateString(),
            'available' => $on === null ? true : $this->isAvailableOn($on),
            'reserved_month' => $this->reservedMonth,
        ];
    }
}
