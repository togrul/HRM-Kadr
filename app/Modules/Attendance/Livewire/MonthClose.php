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

    /**
     * @var array<string,mixed>
     */
    public array $exportStatus = [];

    public function mount(
        int $year,
        int $month,
        AttendanceMonthLockService $lockService,
        AttendanceAuthorizationService $authorization
    ): void
    {
        if (! $authorization->can('attendance.month.view')) {
            abort(403);
        }

        $this->canManage = $authorization->can('attendance.month.manage');
        $this->canExport = $authorization->can('attendance.export');

        $this->year = $year;
        $this->month = $month;
        $this->refreshState($lockService);
        $this->csvProfile = (array) config('attendance.exports.payroll.csv', []);
    }

    public function closePeriod(AttendanceMonthLockService $lockService): void
    {
        if (! $this->canManage) {
            abort(403);
        }

        $stats = $lockService->closeMonth($this->year, $this->month);
        $this->refreshState($lockService);

        $this->dispatch(
            'notify',
            type: 'success',
            message: __('attendance::month_close.messages.closed', [
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
        $this->refreshState($lockService);

        $this->dispatch(
            'notify',
            type: 'success',
            message: __('attendance::month_close.messages.unlocked', [
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
        $this->refreshState($lockService);

        $this->dispatch(
            'notify',
            type: 'success',
            message: __('attendance::month_close.messages.snapshot_done', [
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
            message: __('attendance::month_close.messages.snapshot_queued')
        );
    }

    public function exportPayroll(
        AttendancePayrollExportService $service,
        AttendancePayrollExportContract $contract
    ) {
        if (! $this->canExport) {
            abort(403);
        }

        if (! $this->ensureExportReady()) {
            return null;
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

        if (! $this->ensureExportReady()) {
            return null;
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

    private function refreshState(AttendanceMonthLockService $lockService): void
    {
        $this->status = $lockService->periodStatus($this->year, $this->month);
        $this->exportStatus = $lockService->exportStatus($this->year, $this->month);
    }

    private function ensureExportReady(): bool
    {
        $lockService = app(AttendanceMonthLockService::class);
        $this->refreshState($lockService);

        if ($this->exportStatus['ready'] ?? false) {
            return true;
        }

        $message = ($this->exportStatus['has_snapshot'] ?? false)
            ? __('attendance::month_close.messages.export_requires_fresh_snapshot')
            : __('attendance::month_close.messages.export_requires_snapshot');

        $this->dispatch('notify', type: 'error', message: $message);

        return false;
    }
}
