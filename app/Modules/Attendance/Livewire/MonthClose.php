<?php

namespace App\Modules\Attendance\Livewire;

use App\Modules\Attendance\Application\Contracts\AttendancePayrollExportContract;
use App\Modules\Attendance\Application\Services\AttendanceAuthorizationService;
use App\Modules\Attendance\Application\Services\AttendanceMonthLockService;
use App\Modules\Attendance\Application\Services\AttendancePayrollExportService;
use App\Modules\Attendance\Exports\AttendancePayrollCsvExport;
use App\Modules\Attendance\Exports\AttendancePayrollExport;
use App\Modules\Attendance\Jobs\GenerateAttendanceMonthlySnapshotJob;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Livewire\Component;

class MonthClose extends Component
{
    public int $year;

    public int $month;

    public array $status = [];

    public bool $canManage = false;

    public bool $canExport = false;

    /**
     * @var array<string,mixed>
     */
    public array $csvProfile = [];

    public function mount(
        int $year,
        int $month,
        AttendanceMonthLockService $lockService,
        AttendanceAuthorizationService $authorization
    ): void
    {
        if (! $authorization->can('attendance.view')) {
            abort(403);
        }

        $this->canManage = $authorization->can('attendance.month.manage');
        $this->canExport = $authorization->can('attendance.export');

        $this->year = $year;
        $this->month = $month;
        $this->status = $lockService->periodStatus($year, $month);
        $this->csvProfile = (array) config('attendance.exports.payroll.csv', []);
    }

    public function closePeriod(AttendanceMonthLockService $lockService): void
    {
        if (! $this->canManage) {
            abort(403);
        }

        $stats = $lockService->closeMonth($this->year, $this->month);
        $this->status = $lockService->periodStatus($this->year, $this->month);

        $this->dispatch(
            'notify',
            type: 'success',
            message: __('Month closed. Summaries: :summaries, Locked ledgers: :ledgers', [
                'summaries' => $stats['summary_upserts'],
                'ledgers' => $stats['locked_ledgers'],
            ])
        );
    }

    public function unlockPeriod(AttendanceMonthLockService $lockService): void
    {
        if (! $this->canManage) {
            abort(403);
        }

        $stats = $lockService->unlockMonth($this->year, $this->month);
        $this->status = $lockService->periodStatus($this->year, $this->month);

        $this->dispatch(
            'notify',
            type: 'success',
            message: __('Month unlocked. Summaries: :summaries, Ledgers: :ledgers', [
                'summaries' => $stats['unlocked_summaries'],
                'ledgers' => $stats['unlocked_ledgers'],
            ])
        );
    }

    public function snapshotNow(AttendanceMonthLockService $lockService): void
    {
        if (! $this->canManage) {
            abort(403);
        }

        $stats = $lockService->snapshotMonth($this->year, $this->month, false);
        $this->status = $lockService->periodStatus($this->year, $this->month);

        $this->dispatch(
            'notify',
            type: 'success',
            message: __('Snapshot done. Summary rows upserted: :count', [
                'count' => $stats['summary_upserts'],
            ])
        );
    }

    public function snapshotQueue(): void
    {
        if (! $this->canManage) {
            abort(403);
        }

        GenerateAttendanceMonthlySnapshotJob::dispatch($this->year, $this->month, false);

        $this->dispatch(
            'notify',
            type: 'success',
            message: __('Monthly snapshot queued.')
        );
    }

    public function exportPayroll(
        AttendancePayrollExportService $service,
        AttendancePayrollExportContract $contract
    ) {
        if (! $this->canExport) {
            abort(403);
        }

        $rows = $service->rows($this->year, $this->month);

        $filename = sprintf('attendance-payroll-%04d-%02d.xlsx', $this->year, $this->month);

        return Excel::download(
            new AttendancePayrollExport($rows, $this->year, $this->month, $contract),
            $filename
        );
    }

    public function exportPayrollCsv(
        AttendancePayrollExportService $service,
        AttendancePayrollExportContract $contract
    ) {
        if (! $this->canExport) {
            abort(403);
        }

        $rows = $service->rows($this->year, $this->month);
        $csvSettings = (array) config('attendance.exports.payroll.csv', []);

        $filename = sprintf('attendance-payroll-%04d-%02d.csv', $this->year, $this->month);

        return Excel::download(
            new AttendancePayrollCsvExport($rows, $contract, $csvSettings),
            $filename,
            ExcelWriter::CSV
        );
    }

    public function render()
    {
        return view('attendance::livewire.attendance.month-close', [
            'csvProfile' => $this->csvProfile,
        ]);
    }
}
