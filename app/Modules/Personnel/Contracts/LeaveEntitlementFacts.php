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
        /** Vəzifənin VTİSK kateqoriyası (App\Support\VtiskCategory), ƏM m.114.3 "b". */
        public readonly ?string $positionCategory = null,
        /**
         * m.116 stajına daxil olmayan dövrlər (hər iki tarix daxil): ödənişsiz məzuniyyət
         * (m.128–130) və uşağa qulluq məzuniyyəti (m.127). m.116.2 staja «yalnız» faktiki işi,
         * xəstəliyi və m.179 dövrlərini daxil edir.
         *
         * @var list<array{0: CarbonImmutable, 1: CarbonImmutable}>
         */
        public readonly array $seniorityGaps = [],
        /**
         * Vəzifəni dəyişmiş köçürmələr, tarixə görə artan: [köçürmə günü, əvvəlki vəzifə].
         * Boşdursa işçi işə qəbuldan hazırkı vəzifədədir.
         *
         * @var list<array{0: CarbonImmutable, 1: ?int}>
         */
        public readonly array $positionChanges = [],
    ) {}

    /** İşçinin verilən gündəki vəzifəsi (köçürmə günü yeni vəzifəyə aiddir). */
    public function positionOn(CarbonInterface $date): ?int
    {
        $day = $date->toDateString();

        foreach ($this->positionChanges as [$changedOn, $previous]) {
            if ($day < $changedOn->toDateString()) {
                return $previous;
            }
        }

        return $this->positionId;
    }

    /** Gün m.116 / NK 95 b.8 stajına daxil olmayan dövrə düşürmü (ödənişsiz, uşağa qulluq). */
    public function isSeniorityGap(CarbonInterface $date): bool
    {
        $day = $date->toDateString();

        foreach ($this->seniorityGaps as [$start, $end]) {
            if ($start->toDateString() <= $day && $day <= $end->toDateString()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Başqa moduldan gələn faktlarla (əmrlər) tamamlanmış nüsxə.
     *
     * @param  list<array{0: CarbonImmutable, 1: CarbonImmutable}>  $seniorityGaps
     * @param  list<array{0: CarbonImmutable, 1: ?int}>  $positionChanges
     */
    public function withOrderFacts(array $seniorityGaps, array $positionChanges): self
    {
        return new self(
            tabelNo: $this->tabelNo,
            gender: $this->gender,
            birthdate: $this->birthdate,
            joinDate: $this->joinDate,
            leaveDate: $this->leaveDate,
            positionId: $this->positionId,
            disabled: $this->disabled,
            disabledSince: $this->disabledSince,
            children: $this->children,
            previousEmployment: $this->previousEmployment,
            positionCategory: $this->positionCategory,
            seniorityGaps: $seniorityGaps,
            positionChanges: $positionChanges,
        );
    }

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
        // Birləşdirilmiş günlər tam illərə (orta il uzunluğu ilə) çevrilir.
        return (int) floor(($this->seniorityDaysOn($date) + 1) / 365.2425);
    }

    /**
     * Ümumi əmək stajı günlərlə verilən tarixə qədər (həmin gün daxil deyil). Üst-üstə düşən
     * dövrlər bir dəfə sayılır, m.116 stajına daxil olmayan dövrlər (`seniorityGaps`) çıxılır.
     */
    public function seniorityDaysOn(CarbonInterface $date): int
    {
        $on = CarbonImmutable::parse($date->toDateString());
        $intervals = [];

        foreach ($this->previousEmployment as [$start, $end]) {
            $intervals[] = [$start, $end ?? $on];
        }

        if ($this->joinDate !== null) {
            $intervals[] = [$this->joinDate, $this->leaveDate !== null && $this->leaveDate->lt($on) ? $this->leaveDate : $on];
        }

        $worked = self::merge($intervals, $on);
        // Boşluqlar hər iki tarix daxil verilir: [başlanğıc, son + 1 gün).
        $gaps = self::merge(array_map(fn (array $gap): array => [$gap[0], $gap[1]->addDay()], $this->seniorityGaps), $on);

        $days = 0;

        foreach ($worked as [$start, $end]) {
            $days += (int) $start->diffInDays($end);

            foreach ($gaps as [$gapStart, $gapEnd]) {
                $from = $gapStart->gt($start) ? $gapStart : $start;
                $to = $gapEnd->lt($end) ? $gapEnd : $end;

                if ($from->lt($to)) {
                    $days -= (int) $from->diffInDays($to);
                }
            }
        }

        return max(0, $days);
    }

    /**
     * Yarımaçıq [başlanğıc, son) aralıqlarını $on-a qədər kəsib birləşdirir.
     *
     * @param  list<array{0: CarbonImmutable, 1: CarbonImmutable}>  $intervals
     * @return list<array{0: CarbonImmutable, 1: CarbonImmutable}>
     */
    private static function merge(array $intervals, CarbonImmutable $on): array
    {
        $intervals = array_values(array_filter(
            array_map(fn (array $i): array => [$i[0], $i[1]->gt($on) ? $on : $i[1]], $intervals),
            fn (array $i): bool => $i[0]->lt($i[1]),
        ));

        usort($intervals, fn (array $a, array $b): int => $a[0] <=> $b[0]);

        $merged = [];

        foreach ($intervals as [$start, $end]) {
            $last = count($merged) - 1;

            if ($last >= 0 && $start->lte($merged[$last][1])) {
                $merged[$last][1] = $end->gt($merged[$last][1]) ? $end : $merged[$last][1];

                continue;
            }

            $merged[] = [$start, $end];
        }

        return $merged;
    }

    /**
     * Staj həmin tarixə düz `$years` ildirmi, ondan çoxdurmu, azdırmı: -1 / 0 / 1.
     *
     * «Düz N il» təqvimlədir: tarixdən N il geriyə olan günlərin sayı. Yalnız bu işəgötürəndə
     * işləyən işçi üçün işə qəbulun ildönümündə staj məhz 0 qaytarır.
     */
    public function compareSeniorityTo(int $years, CarbonInterface $date): int
    {
        $on = CarbonImmutable::parse($date->toDateString());
        $threshold = (int) $on->subYears($years)->diffInDays($on);

        return $this->seniorityDaysOn($on) <=> $threshold;
    }
}
