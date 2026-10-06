<?php

namespace App\Modules\Integration\Application\Services;

use App\Models\AttendanceCalendar;
use App\Models\AttendanceDailyLedger;
use App\Models\AttendanceExportMark;
use App\Models\AttendanceMonthlySummary;
use App\Models\Personnel;
use App\Modules\Integration\Support\Contract;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The month's attendance, per employee, for the payroll side.
 *
 * ## Minutes here, day codes there
 *
 * This system records minutes — scheduled, worked, overtime, late. The payroll
 * side records one code per day. The translation is therefore **lossy**, and it
 * is deliberately not performed here: a day that is half worked and half unpaid
 * leave has no single correct code, and the rule for choosing one is an
 * accounting decision, not an attendance one.
 *
 * So the feed ships the raw facts — status, absence code and the minutes — and
 * lets the payroll side map them against its own catalogue. What it must not do
 * is guess, because the guess decides whether a day is paid.
 *
 * ## The summary travels with the days
 *
 * Both are sent so the reader can check its own translation against ours. If its
 * reconstructed totals disagree with `summary`, the mapping is wrong and the
 * package must be refused — long before the figure reaches a payslip.
 *
 * ## The calendar hash
 *
 * Norm days depend on which dates are holidays. If the two systems disagree
 * about that, every norm and therefore every salary drifts, silently. The hash
 * lets the reader detect the disagreement instead of discovering it in a
 * payslip.
 */
class AttendanceFeedService
{
    /**
     * One page of employees for a given month.
     *
     * @return array{items: list<array<string, mixed>>, last_sequence: int, has_more: bool, calendar_hash: string, period: array{year: int, month: int}}
     */
    public function page(int $year, int $month, int $after = 0, int $limit = Contract::DEFAULT_LIMIT): array
    {
        $limit = max(1, min($limit, Contract::MAX_LIMIT));

        $people = Personnel::query()
            ->where('id', '>', $after)
            ->orderBy('id')
            ->limit($limit + 1)
            ->get(['id', 'tabel_no', 'person_uid']);

        $hasMore = $people->count() > $limit;
        $people = $people->take($limit);

        $tabelNos = $people->pluck('tabel_no')
            ->map(fn ($no) => trim((string) $no))
            ->filter()
            ->values()
            ->all();

        $ledgers = $this->ledgers($tabelNos, $year, $month);
        $summaries = $this->summaries($tabelNos, $year, $month);

        // A locked month handed over is a month the other side will pay from.
        // Recording that is what lets `unlockMonth()` refuse to change it
        // underneath them later.
        if ($summaries->contains(fn (AttendanceMonthlySummary $s): bool => (bool) $s->is_locked)) {
            AttendanceExportMark::mark($year, $month);
        }

        return [
            'period' => ['year' => $year, 'month' => $month],
            'calendar_hash' => $this->calendarHash($year, $month),
            'items' => $people->map(fn (Personnel $person): array => $this->row($person, $ledgers, $summaries))
                ->values()->all(),
            'last_sequence' => (int) ($people->last()->id ?? $after),
            'has_more' => $hasMore,
        ];
    }

    /**
     * @param  Collection<string, list<AttendanceDailyLedger>>  $ledgers
     * @param  Collection<string, AttendanceMonthlySummary>  $summaries
     * @return array<string, mixed>
     */
    private function row(Personnel $person, Collection $ledgers, Collection $summaries): array
    {
        $tabelNo = trim((string) $person->tabel_no);
        $days = collect($ledgers->get($tabelNo, []));
        $summary = $summaries->get($tabelNo);

        return [
            'external_id' => (string) $person->id,
            'person_uid' => (string) $person->person_uid,
            'external_no' => $tabelNo !== '' ? $tabelNo : null,
            'days' => $days->map(fn (AttendanceDailyLedger $day): array => [
                'day' => (int) Carbon::parse($day->date)->day,
                'status' => (string) $day->attendance_status,
                'absence_code' => $this->text($day->absence_code),
                'scheduled_minutes' => (int) $day->scheduled_minutes,
                'worked_minutes' => (int) $day->worked_minutes,
                'overtime_minutes' => (int) $day->overtime_minutes,
            ])->values()->all(),
            // Sent so the reader can verify its own translation rather than
            // trust it.
            'summary' => $summary === null ? null : [
                'scheduled_minutes' => (int) $summary->total_scheduled_minutes,
                'worked_minutes' => (int) $summary->total_worked_minutes,
                'overtime_minutes' => (int) $summary->total_overtime_minutes,
                'workdays' => (int) $summary->total_workdays,
                'present_days' => (int) $summary->total_present_days,
                'absence_days' => (int) $summary->total_absence_days,
            ],
            'is_locked' => $summary !== null && (bool) $summary->is_locked,
        ];
    }

    /**
     * @param  list<string>  $tabelNos
     * @return Collection<string, list<AttendanceDailyLedger>>
     */
    private function ledgers(array $tabelNos, int $year, int $month): Collection
    {
        if ($tabelNos === []) {
            return collect();
        }

        $start = Carbon::create($year, $month, 1);

        $rows = AttendanceDailyLedger::query()
            ->whereIn('tabel_no', $tabelNos)
            ->whereBetween('date', [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()])
            ->orderBy('date')
            ->get();

        // Grouped by hand rather than with `groupBy()`: the staff number is a
        // string key and the rows are a plain list, and saying so keeps the
        // declared type honest instead of widening it.
        $grouped = collect();

        foreach ($rows as $row) {
            $key = trim((string) $row->tabel_no);
            $bucket = $grouped->get($key, []);
            $bucket[] = $row;
            $grouped->put($key, $bucket);
        }

        return $grouped;
    }

    /**
     * @param  list<string>  $tabelNos
     * @return Collection<string, AttendanceMonthlySummary>
     */
    private function summaries(array $tabelNos, int $year, int $month): Collection
    {
        if ($tabelNos === []) {
            return collect();
        }

        return AttendanceMonthlySummary::query()
            ->whereIn('tabel_no', $tabelNos)
            ->where('year', $year)
            ->where('month', $month)
            ->get()
            ->keyBy('tabel_no');
    }

    /**
     * A fingerprint of the month's calendar.
     *
     * Sorted so the value depends on the content and not on row order, and
     * limited to what actually changes a norm: the date and its day type.
     */
    public function calendarHash(int $year, int $month): string
    {
        $start = Carbon::create($year, $month, 1);

        $days = AttendanceCalendar::query()
            ->whereBetween('date', [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()])
            ->where('scope_type', 'global')
            ->orderBy('date')
            ->get(['date', 'day_type'])
            ->map(fn (AttendanceCalendar $day): string => Carbon::parse($day->date)->toDateString().'|'.$day->day_type)
            ->sort()
            ->values()
            ->all();

        return hash('sha256', implode("\n", $days));
    }

    private function text(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}
