<?php

namespace App\Modules\Attendance\Livewire;

use App\Modules\Attendance\Application\Services\AttendanceAuthorizationService;
use App\Modules\Attendance\Application\Services\AttendancePuantajReadService;
use App\Models\Structure;
use App\Traits\NestedStructureTrait;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class PuantajGrid extends Component
{
    use WithPagination;
    use NestedStructureTrait;

    public int $year;

    public int $month;

    public string $search = '';

    public int $perPage = 20;

    public ?int $selectedStructureId = null;

    public function mount(int $year, int $month, AttendanceAuthorizationService $authorization): void
    {
        if (! $authorization->can('attendance.view')) {
            abort(403);
        }

        $this->year = $year;
        $this->month = $month;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedStructureId(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $from = Carbon::createFromDate($this->year, $this->month, 1)->startOfMonth();
        $to = $from->copy()->endOfMonth();
        $days = range(1, (int) $from->daysInMonth);
        $structureIds = $this->selectedStructureId
            ? $this->getNestedStructure($this->selectedStructureId)
            : [];
        /** @var AttendancePuantajReadService $readService */
        $readService = app(AttendancePuantajReadService::class);

        $personnels = $readService->paginatePersonnels(trim($this->search), $this->perPage, $structureIds);
        $tabelNos = $personnels->getCollection()->pluck('tabel_no')->filter()->values()->all();

        $ledgerByTabelAndDate = $readService->loadLedgerMap($tabelNos, $from, $to);
        $calendarDayTypeByDate = $readService->globalCalendarDayTypeByDate($from, $to);

        $rows = $personnels->getCollection()->map(function ($personnel) use (
            $days,
            $from,
            $ledgerByTabelAndDate
        ): array {
            $rowCells = [];
            $totalWorkedMinutes = 0;
            $totalPresentDays = 0;

            foreach ($days as $day) {
                $date = $from->copy()->day($day)->toDateString();
                $ledger = $ledgerByTabelAndDate[$personnel->tabel_no][$date] ?? null;

                $workedMinutes = (int) ($ledger['worked_minutes'] ?? 0);
                $status = (string) ($ledger['attendance_status'] ?? 'none');
                $absenceCode = (string) ($ledger['absence_code'] ?? '');

                $rowCells[$day] = [
                    'value' => $this->formatCellValue($workedMinutes, $status, $absenceCode),
                    'status' => $status,
                    'worked_minutes' => $workedMinutes,
                    'title' => $this->buildCellTitle($workedMinutes, $status, $absenceCode),
                ];

                $totalWorkedMinutes += $workedMinutes;
                if ($workedMinutes > 0 || in_array($status, ['present', 'manual_present', 'holiday_worked', 'weekend_worked'], true)) {
                    $totalPresentDays++;
                }
            }

            return [
                'personnel' => $personnel,
                'cells' => $rowCells,
                'total_hours' => round($totalWorkedMinutes / 60, 1),
                'total_days' => $totalPresentDays,
            ];
        })->values();

        return view('attendance::livewire.attendance.puantaj-grid', [
            'days' => $days,
            'rows' => $rows,
            'personnels' => $personnels,
            'calendarDayTypeByDate' => $calendarDayTypeByDate,
            'monthStart' => $from,
            'selectedStructureLabel' => $this->selectedStructureId
                ? Structure::query()->whereKey($this->selectedStructureId)->value('name')
                : null,
        ]);
    }

    private function formatCellValue(int $workedMinutes, string $status, string $absenceCode): string
    {
        if ($status === 'none') {
            return '';
        }

        if ($workedMinutes > 0) {
            return (string) round($workedMinutes / 60, 1);
        }

        if ($absenceCode !== '') {
            return strtoupper($absenceCode);
        }

        return match ($status) {
            'absent', 'manual_absence' => '0',
            'weekend', 'holiday' => '-',
            default => '',
        };
    }

    private function buildCellTitle(int $workedMinutes, string $status, string $absenceCode): string
    {
        $parts = [];

        if ($workedMinutes > 0) {
            $parts[] = __('Worked: :hours h', ['hours' => round($workedMinutes / 60, 1)]);
        }

        if ($status !== 'none') {
            $parts[] = __('Status: :status', ['status' => __($status)]);
        }

        if ($absenceCode !== '') {
            $parts[] = __('Absence: :code', ['code' => strtoupper($absenceCode)]);
        }

        return implode(' | ', $parts);
    }
}
