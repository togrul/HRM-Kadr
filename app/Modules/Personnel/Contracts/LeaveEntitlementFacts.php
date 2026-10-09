<?php

namespace App\Modules\Personnel\Contracts;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Əmək məzuniyyəti hüququnu (ƏM m.114–117) müəyyən edən işçi faktları: cins, doğum tarixi,
 * işə qəbul / xitam tarixi, vəzifə, əlillik, uşaqlar (doğum tarixi və əlillik qeydi ilə) və
 * əvvəlki əmək fəaliyyəti dövrləri (ümumi əmək stajı üçün). Saf obyektdir — verilənlər bazasına
 * müraciət etmir; Vacation modulu normaları bu faktlara tətbiq edir.
 */
final class LeaveEntitlementFacts
{
    public const GENDER_FEMALE = 2;

    /**
     * @param  list<array{birthdate: ?CarbonImmutable, disabled: bool}>  $children
     * @param  list<array{0: CarbonImmutable, 1: ?CarbonImmutable}>  $previousEmployment  [başlanğıc, son] — son boşdursa açıqdır
     */
    public function __construct(
        public readonly string $tabelNo,
        public readonly ?int $gender = null,
        public readonly ?CarbonImmutable $birthdate = null,
        public readonly ?CarbonImmutable $joinDate = null,
        public readonly ?CarbonImmutable $leaveDate = null,
        public readonly ?int $positionId = null,
        public readonly bool $disabled = false,
        public readonly ?CarbonImmutable $disabledSince = null,
        public readonly array $children = [],
        public readonly array $previousEmployment = [],
    ) {}

    public function isFemale(): bool
    {
        return $this->gender === self::GENDER_FEMALE;
    }

    /** Verilən tarixdə 18 yaşına çatmayıb. */
    public function isUnder18On(CarbonInterface $date): bool
    {
        return $this->birthdate !== null
            && $this->birthdate->lte($date)
            && $this->birthdate->addYears(18)->gt($date);
    }

    /** Verilən tarixdə əlilliyi var (əlillik tarixi boşdursa, qeyd olunduğu andan). */
    public function isDisabledOn(CarbonInterface $date): bool
    {
        return $this->disabled && ($this->disabledSince === null || $this->disabledSince->lte($date));
    }

    /** Verilən tarixdə $age yaşınadək olan uşaqların sayı. */
    public function childrenUnder(int $age, CarbonInterface $date, bool $disabledOnly = false): int
    {
        $count = 0;

        foreach ($this->children as $child) {
            $birth = $child['birthdate'];

            if ($birth === null || $birth->gt($date) || $birth->addYears($age)->lte($date)) {
                continue;
            }

            if ($disabledOnly && ! $child['disabled']) {
                continue;
            }

            $count++;
        }

        return $count;
    }

    /**
     * Ümumi əmək stajı (tam illər) verilən tarixə: əvvəlki iş dövrləri + bu işəgötürəndəki iş
     * (işə qəbul tarixindən). Üst-üstə düşən dövrlər bir dəfə sayılır.
     */
    public function seniorityYearsOn(CarbonInterface $date): int
    {
        $on = CarbonImmutable::parse($date->toDateString());
        $intervals = [];

        foreach ($this->previousEmployment as [$start, $end]) {
            $intervals[] = [$start, $end ?? $on];
        }

        if ($this->joinDate !== null) {
            $intervals[] = [$this->joinDate, $this->leaveDate !== null && $this->leaveDate->lt($on) ? $this->leaveDate : $on];
        }

        $intervals = array_values(array_filter(
            array_map(fn (array $i): array => [$i[0], $i[1]->gt($on) ? $on : $i[1]], $intervals),
            fn (array $i): bool => $i[0]->lt($i[1]),
        ));

        usort($intervals, fn (array $a, array $b): int => $a[0] <=> $b[0]);

        $days = 0;
        $current = null;

        foreach ($intervals as [$start, $end]) {
            if ($current === null) {
                $current = [$start, $end];

                continue;
            }

            if ($start->lte($current[1])) {
                $current[1] = $end->gt($current[1]) ? $end : $current[1];

                continue;
            }

            $days += (int) $current[0]->diffInDays($current[1]);
            $current = [$start, $end];
        }

        if ($current !== null) {
            $days += (int) $current[0]->diffInDays($current[1]);
        }

        // Birləşdirilmiş günlər tam illərə (orta il uzunluğu ilə) çevrilir.
        return (int) floor(($days + 1) / 365.2425);
    }
}
