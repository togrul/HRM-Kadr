<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\AttendanceOvertimeRequest;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class AttendanceOvertimeApprovalService
{
    /**
     * @throws ValidationException
     */
    public function approve(AttendanceOvertimeRequest $request, int $approvedBy, ?int $approvedMinutes = null): AttendanceOvertimeRequest
    {
        if (app(AttendanceMonthLockService::class)->isPeriodLocked($request->date)) {
            throw ValidationException::withMessages([
                'date' => 'Selected month is locked. Overtime approval is blocked.',
            ]);
        }

        $minutes = $approvedMinutes ?? (int) $request->requested_minutes;
        $minutes = max(0, $minutes);

        if ($minutes > (int) $request->requested_minutes) {
            throw ValidationException::withMessages([
                'approved_minutes' => 'Approved minutes cannot be greater than requested minutes.',
            ]);
        }

        $before = $request->only(['status', 'approved_minutes', 'approved_by', 'approved_at']);

        $request->update([
            'status' => 'approved',
            'approved_minutes' => $minutes,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        app(AttendanceAuditLogger::class)->log(
            event: 'overtime_request.approved',
            description: 'Attendance overtime request approved.',
            subject: $request,
            properties: [
                'tabel_no' => $request->tabel_no,
                'date' => $request->date?->toDateString(),
                'before' => $before,
                'after' => $request->only(['status', 'approved_minutes', 'approved_by', 'approved_at']),
            ],
            causerId: $approvedBy
        );

        $this->recalculate($request->tabel_no, Carbon::parse($request->date));

        return $request->refresh();
    }

    public function reject(AttendanceOvertimeRequest $request, int $approvedBy): AttendanceOvertimeRequest
    {
        if (app(AttendanceMonthLockService::class)->isPeriodLocked($request->date)) {
            throw ValidationException::withMessages([
                'date' => 'Selected month is locked. Overtime approval is blocked.',
            ]);
        }

        $before = $request->only(['status', 'approved_minutes', 'approved_by', 'approved_at']);

        $request->update([
            'status' => 'rejected',
            'approved_minutes' => 0,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        app(AttendanceAuditLogger::class)->log(
            event: 'overtime_request.rejected',
            description: 'Attendance overtime request rejected.',
            subject: $request,
            properties: [
                'tabel_no' => $request->tabel_no,
                'date' => $request->date?->toDateString(),
                'before' => $before,
                'after' => $request->only(['status', 'approved_minutes', 'approved_by', 'approved_at']),
            ],
            causerId: $approvedBy
        );

        $this->recalculate($request->tabel_no, Carbon::parse($request->date));

        return $request->refresh();
    }

    private function recalculate(string $tabelNo, Carbon $date): void
    {
        app(AttendancePunchProcessingPipelineService::class)->process(
            from: $date->copy()->startOfDay(),
            to: $date->copy()->endOfDay(),
            source: null,
            options: [
                'include_processed' => true,
                'mark_processed' => false,
                'tabel_nos' => [$tabelNo],
            ]
        );
    }
}
