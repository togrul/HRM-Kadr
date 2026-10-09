<?php

namespace App\Modules\Attendance\Application\Services;

use App\Enums\OrderStatusEnum;
use App\Models\Leave;
use App\Models\Personnel;
use App\Models\PersonnelBusinessTrip;
use App\Models\PersonnelVacation;
use App\Modules\Attendance\Contracts\PayrollWorkedDays;
use App\Support\Database\InstalledTables;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

/**
 * Working days and absences for payroll. Absences are read through the shared
 * `App\Models` tables with the same coverage rules as the presence resolver: approved or
 * HR-entered vacations and business trips, approved whole-day leaves. When two absences
 * cover the same day the stronger one is kept (vacation, then leave, then business trip).
 */
class AttendancePayrollWorkedDaysService implements PayrollWorkedDays
{
    public function __construct(private readonly AttendanceWorkNormService $norms) {}

    public function workdays(string $tabelNo, string $from, string $to): array
    {
        $start = CarbonImmutable::parse($from)->startOfDay();
        $end = CarbonImmutable::parse($to)->startOfDay();

        if ($end->lessThan($start)) {
            return ['workdays' => [], 'absent' => []];
        }

        $structureId = Personnel::query()->where('tabel_no', $tabelNo)->value('structure_id');
        $structureId = $structureId !== null ? (int) $structureId : null;
        $maps = $this->norms->calendarMaps(Carbon::parse($start), Carbon::parse($end), $structureId !== null ? [$structureId] : []);

        $workdays = [];

        for ($day = $start; $day->lessThanOrEqualTo($end); $day = $day->addDay()) {
            if ($this->norms->resolveDayType($day, $structureId, $maps['global'], $maps['structure']) === 'workday') {
                $workdays[] = $day->toDateString();
            }
        }

        $absent = [];
        $isWorkday = array_flip($workdays);

        foreach ([self::BUSINESS_TRIP => $this->trips($tabelNo, $from, $to), self::LEAVE => $this->leaves($tabelNo, $from, $to), self::VACATION => $this->vacations($tabelNo, $from, $to)] as $kind => $ranges) {
            foreach ($ranges as [$rangeStart, $rangeEnd]) {
                for ($day = CarbonImmutable::parse($rangeStart)->max($start); $day->lessThanOrEqualTo(CarbonImmutable::parse($rangeEnd)->min($end)); $day = $day->addDay()) {
                    if (isset($isWorkday[$day->toDateString()])) {
                        $absent[$day->toDateString()] = $kind;
                    }
                }
            }
        }

        ksort($absent);

        return ['workdays' => $workdays, 'absent' => $absent];
    }

    /**
     * @return list<array{0:string,1:string}>
     */
    private function vacations(string $tabelNo, string $from, string $to): array
    {
        if (! InstalledTables::has('personnel_vacations')) {
            return [];
        }

        return PersonnelVacation::query()
            ->where('tabel_no', $tabelNo)
            ->where(fn ($query) => $query->whereNull('approval_status')->orWhere('approval_status', 'approved'))
            ->whereDate('start_date', '<=', $to)
            ->where(fn ($query) => $query
                ->whereDate('end_date', '>=', $from)
                ->orWhere(fn ($fallback) => $fallback->whereNull('end_date')->whereDate('return_work_date', '>', $from)))
            ->toBase()
            ->get(['start_date', 'end_date', 'return_work_date'])
            ->map(fn ($row): array => [
                substr((string) $row->start_date, 0, 10),
                filled($row->end_date) ? substr((string) $row->end_date, 0, 10) : CarbonImmutable::parse(substr((string) $row->return_work_date, 0, 10))->subDay()->toDateString(),
            ])
            ->all();
    }

    /**
     * @return list<array{0:string,1:string}>
     */
    private function trips(string $tabelNo, string $from, string $to): array
    {
        if (! InstalledTables::has('personnel_business_trips')) {
            return [];
        }

        return PersonnelBusinessTrip::query()
            ->where('tabel_no', $tabelNo)
            ->where(fn ($query) => $query->whereNull('approval_status')->orWhere('approval_status', 'approved'))
            ->whereDate('start_date', '<=', $to)
            ->whereDate('end_date', '>=', $from)
            ->toBase()
            ->get(['start_date', 'end_date'])
            ->map(fn ($row): array => [substr((string) $row->start_date, 0, 10), substr((string) $row->end_date, 0, 10)])
            ->all();
    }

    /**
     * @return list<array{0:string,1:string}>
     */
    private function leaves(string $tabelNo, string $from, string $to): array
    {
        if (! InstalledTables::has('leaves')) {
            return [];
        }

        return Leave::query()
            ->where('tabel_no', $tabelNo)
            ->where('status_id', OrderStatusEnum::APPROVED->value)
            ->where(fn ($query) => $query->whereNull('duration_unit')->orWhere('duration_unit', 'day'))
            ->whereDate('starts_at', '<=', $to)
            // An open sick certificate (no end yet) counts up to today.
            ->where(fn ($query) => $query->whereDate('ends_at', '>=', $from)->orWhereNull('ends_at'))
            ->toBase()
            ->get(['starts_at', 'ends_at'])
            ->map(fn ($row): array => [
                substr((string) $row->starts_at, 0, 10),
                filled($row->ends_at) ? substr((string) $row->ends_at, 0, 10) : max(substr((string) $row->starts_at, 0, 10), now()->toDateString()),
            ])
            ->all();
    }
}
