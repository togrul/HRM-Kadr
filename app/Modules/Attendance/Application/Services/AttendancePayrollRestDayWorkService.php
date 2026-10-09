<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\AttendanceOvertimeRequest;
use App\Models\Personnel;
use App\Modules\Attendance\Contracts\OrderRestDayWork;
use App\Modules\Attendance\Contracts\PayrollRestDayWork;
use App\Support\Database\InstalledTables;
use Carbon\CarbonImmutable;

/**
 * Reads the order rest-day work out of the overtime register for payroll and the finance
 * feed. A day named by more than one order is listed once (the latest order's terms);
 * a row without a compensation value predates the column and gets the order default,
 * double pay.
 */
class AttendancePayrollRestDayWorkService implements PayrollRestDayWork
{
    public function __construct(private readonly AttendanceWorkNormService $norms) {}

    public function orderWorkFor(array $tabelNos, int $year, int $month): array
    {
        if ($tabelNos === [] || ! InstalledTables::has('attendance_overtime_requests')) {
            return [];
        }

        $start = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $hasCompensation = InstalledTables::hasColumn('attendance_overtime_requests', 'compensation');
        $columns = ['id', 'tabel_no', 'date', 'approved_minutes'];

        if ($hasCompensation) {
            $columns[] = 'compensation';
        }

        $rows = AttendanceOvertimeRequest::query()
            ->whereIn('tabel_no', $tabelNos)
            ->where('source', AttendanceOrderRestDayWorkService::SOURCE)
            ->where('status', 'approved')
            ->where('approved_minutes', '>', 0)
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $start->endOfMonth()->toDateString())
            ->orderBy('id')
            ->get($columns);

        $days = [];

        foreach ($rows as $row) {
            $date = $row->date?->toDateString();

            if ($date === null) {
                continue;
            }

            $compensation = $hasCompensation ? (string) $row->getAttribute('compensation') : '';

            $days[(string) $row->getAttribute('tabel_no')][$date] = [
                'id' => (int) $row->getKey(),
                'date' => $date,
                'minutes' => (int) $row->getAttribute('approved_minutes'),
                'compensation' => $compensation === OrderRestDayWork::COMPENSATION_DAY_OFF
                    ? OrderRestDayWork::COMPENSATION_DAY_OFF
                    : OrderRestDayWork::COMPENSATION_DOUBLE_PAY,
            ];
        }

        $result = [];

        foreach ($days as $tabelNo => $byDate) {
            ksort($byDate);
            $result[$tabelNo] = array_values($byDate);
        }

        return $result;
    }

    public function monthNormMinutes(string $tabelNo, int $year, int $month): int
    {
        $structureId = Personnel::query()->where('tabel_no', $tabelNo)->value('structure_id');

        return (int) $this->norms->employeeMonthNorm($year, $month, $tabelNo, $structureId !== null ? (int) $structureId : null)['minutes'];
    }
}
