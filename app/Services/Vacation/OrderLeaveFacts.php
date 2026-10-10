<?php

namespace App\Services\Vacation;

use App\Enums\OrderStatusEnum;
use App\Models\OrderLog;
use App\Models\OrderWordTemplate;
use App\Models\PersonnelVacation;
use App\Modules\Vacation\Application\Services\VacationSettings;
use App\Support\Database\InstalledTables;
use Carbon\CarbonImmutable;

/**
 * Məzuniyyət hüququna təsir edən, əmrlərdən çıxan faktlar:
 *   - iş ilinə daxil olmayan dövrlər (ƏM m.132.2: uşağa qulluq məzuniyyəti, m.127);
 *   - m.116 stajına daxil olmayan dövrlər: həm də ödənişsiz məzuniyyət (m.128–130), çünki
 *     m.116.2 staja «yalnız» faktiki işi, xəstəliyi və m.179 dövrlərini daxil edir;
 *   - vəzifə tarixçəsi — əmək şəraitinə görə əlavə məzuniyyət hər vəzifədə işlənmiş vaxta
 *     mütənasibdir (m.131.6; NK 95, b.7, b.11).
 */
class OrderLeaveFacts
{
    public const UNPAID_LEAVE_EFFECT = 'unpaid_leave';

    /**
     * @return list<array{0: CarbonImmutable, 1: CarbonImmutable}>
     */
    public function workYearGaps(string $tabelNo): array
    {
        return $this->vacationPeriods($tabelNo, VacationSettings::WORK_YEAR_EXCLUDED_TEMPLATES);
    }

    /**
     * @return list<array{0: CarbonImmutable, 1: CarbonImmutable}>
     */
    public function seniorityGaps(string $tabelNo): array
    {
        $unpaid = InstalledTables::has('order_word_templates')
            ? OrderWordTemplate::query()->where('effect', self::UNPAID_LEAVE_EFFECT)->pluck('code')->map(fn ($code): string => (string) $code)->all()
            : [];

        return $this->vacationPeriods($tabelNo, [...VacationSettings::WORK_YEAR_EXCLUDED_TEMPLATES, ...$unpaid]);
    }

    /**
     * İşçinin vəzifə tarixçəsi: vəzifəni dəyişmiş təsdiqlənmiş köçürmə əmrləri, tarixə görə
     * artan — [əmrin tarixi, əvvəlki vəzifə]. Köçürmə effekti əvvəlki vəzifəni
     * `effect_state.prev_position_id`-də saxlayır; yalnız struktur dəyişən köçürmədə o, sonrakı
     * vəzifə ilə eynidir və atılır.
     *
     * @return list<array{0: CarbonImmutable, 1: ?int}>
     */
    public function positionChanges(int $personnelId, ?int $positionId): array
    {
        if (! InstalledTables::has('order_logs')) {
            return [];
        }

        $transfers = OrderLog::query()
            ->where('status_id', OrderStatusEnum::APPROVED->value)
            ->where('template_snapshot', 'like', '%prev_position_id%')
            ->whereNotNull('given_date')
            ->orderByDesc('given_date')
            ->orderByDesc('id')
            ->get(['id', 'given_date', 'template_snapshot']);

        $changes = [];
        $after = $positionId;

        foreach ($transfers as $order) {
            $snapshot = (array) $order->template_snapshot;
            $state = (array) ($snapshot['effect_state'] ?? []);

            if ((int) ($snapshot['personnel_id'] ?? 0) !== $personnelId || ! array_key_exists('prev_position_id', $state)) {
                continue;
            }

            $previous = $state['prev_position_id'] !== null ? (int) $state['prev_position_id'] : null;

            if ($previous === $after) {
                continue;
            }

            $changes[] = [CarbonImmutable::parse((string) $order->getRawOriginal('given_date'))->startOfDay(), $previous];
            $after = $previous;
        }

        return array_reverse($changes);
    }

    /**
     * Verilmiş şablonlarla tərtib olunmuş əmrlərin məzuniyyət dövrləri (hər iki tarix daxil).
     *
     * @param  list<string>  $templateCodes
     * @return list<array{0: CarbonImmutable, 1: CarbonImmutable}>
     */
    private function vacationPeriods(string $tabelNo, array $templateCodes): array
    {
        if ($templateCodes === [] || ! InstalledTables::has('order_logs')) {
            return [];
        }

        $vacations = PersonnelVacation::query()
            ->where('tabel_no', $tabelNo)
            ->whereNotNull('order_no')
            ->toBase()
            ->get(['order_no', 'start_date', 'end_date']);

        if ($vacations->isEmpty()) {
            return [];
        }

        $orders = OrderLog::query()
            ->whereIn('order_no', $vacations->pluck('order_no')->unique()->values()->all())
            ->whereNotNull('template_snapshot')
            ->get(['order_no', 'template_snapshot'])
            ->filter(fn (OrderLog $order): bool => in_array((string) data_get($order->template_snapshot, 'template_code'), $templateCodes, true))
            ->pluck('order_no')
            ->all();

        return $vacations
            ->filter(fn ($vacation): bool => in_array($vacation->order_no, $orders, true) && filled($vacation->start_date) && filled($vacation->end_date))
            ->map(fn ($vacation): array => [CarbonImmutable::parse($vacation->start_date)->startOfDay(), CarbonImmutable::parse($vacation->end_date)->startOfDay()])
            ->values()
            ->all();
    }
}
