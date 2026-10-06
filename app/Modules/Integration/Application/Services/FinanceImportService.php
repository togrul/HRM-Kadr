<?php

namespace App\Modules\Integration\Application\Services;

use App\Models\AttendanceCalendar;
use App\Models\FinancePayslip;
use App\Models\FinancePeriodState;
use App\Models\Personnel;
use App\Models\PersonnelBusinessTrip;
use App\Modules\Integration\Infrastructure\FinanceClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Brings back what the finance system owns.
 *
 * Four things travel this way and each closes a specific hole:
 *
 * - **Payslips** — otherwise an employee has no way to see what they were paid,
 *   because their self-service lives here and the calculation lives there.
 * - **Period state** — otherwise a month the finance side has already paid from
 *   could be reopened here, and the two would part company with nothing to
 *   signal it.
 * - **Calendar** — otherwise the two systems disagree about which dates are
 *   holidays, and every norm day and salary drifts silently.
 * - **Business trips** — the finance side owns them (the per-diem rules live
 *   there), but the days have to appear in attendance, and attendance is ours.
 */
class FinanceImportService
{
    /**
     * Calendar day types the finance system is authoritative for.
     *
     * Weekends are deliberately absent: which days an organisation rests on is
     * a local matter, and the public-holiday calendar says nothing about it.
     */
    private const FINANCE_OWNED_DAY_TYPES = ['holiday', 'workday'];

    public function __construct(private readonly FinanceClient $client) {}

    /**
     * Payslips for one month.
     *
     * @return array{imported: int, skipped: int}
     */
    public function payslips(int $year, int $month): array
    {
        $rows = $this->client->all('/api/v1/hr/payslips', ['year' => $year, 'month' => $month]);

        $stats = ['imported' => 0, 'skipped' => 0];

        foreach ($rows as $row) {
            $tabelNo = $this->tabelNo($row['employee_external_id'] ?? null);

            if ($tabelNo === null) {
                // The person is not ours (a contractor on their side), or the
                // link has not been made yet. Neither is an error here.
                $stats['skipped']++;

                continue;
            }

            FinancePayslip::query()->updateOrCreate(
                ['tabel_no' => $tabelNo, 'year' => $year, 'month' => $month],
                [
                    'employee_name' => (string) ($row['employee_name'] ?? ''),
                    'gross' => (float) ($row['gross'] ?? 0),
                    'total_deductions' => (float) ($row['total_deductions'] ?? 0),
                    'net' => (float) ($row['net'] ?? 0),
                    'currency' => (string) ($row['currency'] ?? 'AZN'),
                    'synced_at' => now(),
                ],
            );

            $stats['imported']++;
        }

        return $stats;
    }

    /**
     * Accounting period state.
     *
     * @return array{imported: int}
     */
    public function periodState(?int $fromYear = null): array
    {
        $body = $this->client->get('/api/v1/hr/period-state', array_filter([
            'from_year' => $fromYear,
        ]));

        $items = is_array($body['items'] ?? null) ? $body['items'] : [];
        $imported = 0;

        foreach ($items as $row) {
            if (! is_array($row)) {
                continue;
            }

            FinancePeriodState::query()->updateOrCreate(
                ['year' => (int) ($row['year'] ?? 0), 'month' => (int) ($row['month'] ?? 0)],
                [
                    'closed' => (bool) ($row['closed'] ?? false),
                    'closed_at' => ($row['closed_at'] ?? null) ? Carbon::parse((string) $row['closed_at']) : null,
                    'synced_at' => now(),
                ],
            );

            $imported++;
        }

        return ['imported' => $imported];
    }

    /**
     * The production calendar for a year.
     *
     * Only global entries are touched. A calendar scoped to one structure is a
     * local decision and is none of the finance system's business.
     *
     * @return array{imported: int, removed: int}
     */
    public function calendar(int $year): array
    {
        $body = $this->client->get('/api/v1/hr/calendar', ['year' => $year]);
        $items = is_array($body['items'] ?? null) ? $body['items'] : [];

        return DB::transaction(function () use ($year, $items): array {
            $keep = [];
            $imported = 0;

            foreach ($items as $row) {
                if (! is_array($row) || ! isset($row['date'])) {
                    continue;
                }

                $date = Carbon::parse((string) $row['date'])->toDateString();

                $day = AttendanceCalendar::query()->updateOrCreate(
                    ['date' => $date, 'scope_type' => 'global', 'scope_id' => null],
                    [
                        'day_type' => (string) ($row['day_type'] ?? 'holiday'),
                        'name' => (string) ($row['name'] ?? ''),
                        'is_paid' => true,
                    ],
                );

                // The kept rows are remembered by **id**, not by date. The `date`
                // cast writes `Y-m-d H:i:s`, so a `whereNotIn` over date strings
                // matches nothing — and the sweep below would have deleted the
                // very rows just imported.
                $keep[] = $day->getKey();
                $imported++;
            }

            // A holiday removed on their side must disappear here too, otherwise
            // the fingerprints would never match again and every attendance
            // package would be refused from then on.
            //
            // Only holiday-shaped days are theirs to manage. **Weekends are
            // ours**: the finance system publishes the public-holiday calendar
            // and knows nothing about which days this organisation treats as
            // rest days. Deleting by date alone would wipe the weekend calendar
            // and every norm would collapse.
            $removed = AttendanceCalendar::query()
                ->where('scope_type', 'global')
                ->whereIn('day_type', self::FINANCE_OWNED_DAY_TYPES)
                ->whereYear('date', $year)
                ->when($keep !== [], fn ($q) => $q->whereNotIn('id', $keep))
                ->delete();

            return ['imported' => $imported, 'removed' => (int) $removed];
        });
    }

    /**
     * Business trips.
     *
     * Recorded as a trip record, which is what makes the days show as
     * `business_trip` in the daily ledger — and therefore what sends them back
     * on the next attendance package. The finance side never writes our
     * attendance directly; it asks, and we record.
     *
     * @return array{applied: int, cancelled: int, skipped: int, cursor: int}
     */
    public function businessTrips(int $after = 0): array
    {
        $rows = $this->client->all('/api/v1/hr/events/business_trip', []);

        $stats = ['applied' => 0, 'cancelled' => 0, 'skipped' => 0, 'cursor' => $after];

        foreach ($rows as $row) {
            $sequence = (int) ($row['sequence'] ?? 0);

            if ($sequence <= $after) {
                continue;
            }

            $tabelNo = $this->tabelNo($row['employee_external_id'] ?? null);
            $orderNo = (string) ($row['no'] ?? '');

            if ($tabelNo === null || $orderNo === '') {
                $stats['skipped']++;
                $stats['cursor'] = $sequence;

                continue;
            }

            if ((string) ($row['status'] ?? '') === 'cancelled') {
                PersonnelBusinessTrip::query()
                    ->where('tabel_no', $tabelNo)
                    ->where('order_no', $orderNo)
                    ->delete();

                $stats['cancelled']++;
                $stats['cursor'] = $sequence;

                continue;
            }

            PersonnelBusinessTrip::query()->updateOrCreate(
                ['tabel_no' => $tabelNo, 'order_no' => $orderNo],
                [
                    'location' => (string) ($row['destination'] ?? ''),
                    'description' => (string) ($row['purpose'] ?? ''),
                    'start_date' => (string) ($row['date_from'] ?? ''),
                    'end_date' => (string) ($row['date_to'] ?? ''),
                    'order_given_by' => 'Maliyyə sistemi',
                    'added_by' => 1,
                ],
            );

            $stats['applied']++;
            $stats['cursor'] = $sequence;
        }

        return $stats;
    }

    /**
     * Their employee key → our staff number.
     *
     * The finance side identifies people by the key we gave it, which is our
     * `personnels.id`.
     */
    private function tabelNo(mixed $externalId): ?string
    {
        $id = (int) ($externalId ?? 0);

        if ($id <= 0) {
            return null;
        }

        $tabelNo = trim((string) Personnel::query()->whereKey($id)->value('tabel_no'));

        return $tabelNo === '' ? null : $tabelNo;
    }
}
