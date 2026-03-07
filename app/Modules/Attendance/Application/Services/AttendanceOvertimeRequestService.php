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
                'manualRequest.requested_minutes' => __('Requested minutes must be greater than zero.'),
            ]);
        }

        if (app(AttendanceMonthLockService::class)->isPeriodLocked($date)) {
            throw ValidationException::withMessages([
                'manualRequest.date' => __('Selected month is locked. Overtime request creation is blocked.'),
            ]);
        }

        $personnelExists = Personnel::query()
            ->where('tabel_no', $tabelNo)
            ->exists();

        if (! $personnelExists) {
            throw ValidationException::withMessages([
                'manualRequest.tabel_no' => __('Selected personnel was not found.'),
            ]);
        }

        $approvedExists = AttendanceOvertimeRequest::query()
            ->where('tabel_no', $tabelNo)
            ->whereDate('date', $date->toDateString())
            ->where('status', 'approved')
            ->exists();

        if ($approvedExists) {
            throw ValidationException::withMessages([
                'manualRequest.date' => __('An approved overtime request already exists for this day.'),
            ]);
        }

        $pendingExists = AttendanceOvertimeRequest::query()
            ->where('tabel_no', $tabelNo)
            ->whereDate('date', $date->toDateString())
            ->where('status', 'pending')
            ->exists();

        if ($pendingExists) {
            throw ValidationException::withMessages([
                'manualRequest.date' => __('A pending overtime request already exists for this day.'),
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
