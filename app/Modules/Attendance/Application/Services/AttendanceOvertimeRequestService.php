<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\AttendanceOvertimeRequest;
use App\Models\Personnel;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class AttendanceOvertimeRequestService
{
    /**
     * @param  array<string,mixed>  $payload
     * @throws ValidationException
     */
    public function create(array $payload, int $requestedBy): AttendanceOvertimeRequest
    {
        $tabelNo = trim((string) ($payload['tabel_no'] ?? ''));
        $date = Carbon::parse((string) ($payload['date'] ?? now()->toDateString()))->startOfDay();
        $requestedMinutes = max(0, (int) ($payload['requested_minutes'] ?? 0));
        $reason = trim((string) ($payload['reason'] ?? ''));

        if ($tabelNo === '' || $requestedMinutes <= 0) {
            throw ValidationException::withMessages([
                'manualRequest.requested_minutes' => __('attendance::overtime.errors.requested_minutes_positive'),
            ]);
        }

        if (app(AttendanceMonthLockService::class)->isPeriodLocked($date)) {
            throw ValidationException::withMessages([
                'manualRequest.date' => __('attendance::overtime.errors.month_locked'),
            ]);
        }

        $personnelExists = Personnel::query()
            ->where('tabel_no', $tabelNo)
            ->exists();

        if (! $personnelExists) {
            throw ValidationException::withMessages([
                'manualRequest.tabel_no' => __('attendance::overtime.errors.personnel_not_found'),
            ]);
        }

        $approvedExists = AttendanceOvertimeRequest::query()
            ->where('tabel_no', $tabelNo)
            ->whereDate('date', $date->toDateString())
            ->where('status', 'approved')
            ->exists();

        if ($approvedExists) {
            throw ValidationException::withMessages([
                'manualRequest.date' => __('attendance::overtime.errors.approved_exists'),
            ]);
        }

        $pendingExists = AttendanceOvertimeRequest::query()
            ->where('tabel_no', $tabelNo)
            ->whereDate('date', $date->toDateString())
            ->where('status', 'pending')
            ->exists();

        if ($pendingExists) {
            throw ValidationException::withMessages([
                'manualRequest.date' => __('attendance::overtime.errors.pending_exists'),
            ]);
        }

        $request = AttendanceOvertimeRequest::query()->create([
            'tabel_no' => $tabelNo,
            'date' => $date->toDateString(),
            'requested_minutes' => $requestedMinutes,
            'approved_minutes' => 0,
            'status' => 'pending',
            'source' => 'manual',
            'reason' => $reason !== '' ? $reason : null,
            'requested_by' => $requestedBy,
        ]);

        app(AttendanceAuditLogger::class)->log(
            event: 'overtime_request.created_manual',
            description: 'Attendance overtime request created manually.',
            subject: $request,
            properties: [
                'tabel_no' => $tabelNo,
                'date' => $date->toDateString(),
                'requested_minutes' => $requestedMinutes,
                'source' => 'manual',
            ],
            causerId: $requestedBy
        );

        return $request;
    }
}
