<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\AttendanceManualEntry;
use App\Models\AttendanceOvertimeRequest;
use App\Models\AttendanceSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceOvertimeRequestSyncService
{
    public function sync(
        string $tabelNo,
        Carbon $date,
        array $ledgerPayload,
        ?AttendanceManualEntry $manualEntry = null,
        ?AttendanceSetting $setting = null
    ): void {
        $policy = (string) ($setting?->overtime_policy ?? config('attendance.processing.policy_defaults.overtime_policy', 'by_approval'));
        $requestedMinutes = max(0, (int) data_get($ledgerPayload, 'meta.requestable_overtime_minutes', 0));

        if ($requestedMinutes <= 0 && $manualEntry !== null) {
            $requestedMinutes = $this->resolveManualRequestableMinutes(
                tabelNo: $tabelNo,
                date: $date,
                manualEntry: $manualEntry,
                ledgerPayload: $ledgerPayload,
                setting: $setting
            );
        }

        /** @var Collection<int,AttendanceOvertimeRequest> $requests */
        $requests = AttendanceOvertimeRequest::query()
            ->where('tabel_no', $tabelNo)
            ->whereDate('date', $date->toDateString())
            ->orderByDesc('id')
            ->get();

        $this->cleanupDuplicatePendings($requests);

        $approvedRequest = AttendanceOvertimeRequest::query()
            ->where('tabel_no', $tabelNo)
            ->whereDate('date', $date->toDateString())
            ->where('status', 'approved')
            ->latest('id')
            ->first();

        if ($approvedRequest instanceof AttendanceOvertimeRequest) {
            return;
        }

        $latestPending = AttendanceOvertimeRequest::query()
            ->where('tabel_no', $tabelNo)
            ->whereDate('date', $date->toDateString())
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        $latestRejected = AttendanceOvertimeRequest::query()
            ->where('tabel_no', $tabelNo)
            ->whereDate('date', $date->toDateString())
            ->where('status', 'rejected')
            ->latest('id')
            ->first();

        if ($policy !== 'by_approval' || $requestedMinutes <= 0) {
            if ($latestPending instanceof AttendanceOvertimeRequest) {
                $latestPending->delete();

                app(AttendanceAuditLogger::class)->log(
                    event: 'overtime_request.deleted',
                    description: 'Attendance overtime request removed after recalculation.',
                    subject: $latestPending,
                    properties: [
                        'tabel_no' => $tabelNo,
                        'date' => $date->toDateString(),
                        'reason' => $policy !== 'by_approval' ? 'policy_not_by_approval' : 'no_requestable_overtime',
                    ],
                    causerId: $this->resolveCauserId($latestPending, $manualEntry)
                );
            }

            return;
        }

        if ($latestRejected instanceof AttendanceOvertimeRequest) {
            return;
        }

        if ($latestPending instanceof AttendanceOvertimeRequest) {
            if ((int) $latestPending->requested_minutes === $requestedMinutes) {
                return;
            }

            $before = $latestPending->only(['requested_minutes', 'source']);
            $source = $manualEntry !== null ? 'auto_manual_entry' : 'auto_ledger';

            $latestPending->update([
                'requested_minutes' => $requestedMinutes,
                'approved_minutes' => 0,
                'source' => $source,
            ]);

            app(AttendanceAuditLogger::class)->log(
                event: 'overtime_request.updated',
                description: 'Attendance overtime request recalculated.',
                subject: $latestPending,
                properties: [
                    'tabel_no' => $tabelNo,
                    'date' => $date->toDateString(),
                    'before' => $before,
                    'after' => $latestPending->only(['requested_minutes', 'source']),
                ],
                causerId: $this->resolveCauserId($latestPending, $manualEntry)
            );

            return;
        }

        $causerId = $this->resolveCauserId(null, $manualEntry);
        if ($causerId === null) {
            app(AttendanceAuditLogger::class)->log(
                event: 'overtime_request.skipped',
                description: 'Attendance overtime request generation skipped due to missing actor.',
                properties: [
                    'tabel_no' => $tabelNo,
                    'date' => $date->toDateString(),
                    'requested_minutes' => $requestedMinutes,
                ]
            );

            return;
        }

        $request = AttendanceOvertimeRequest::query()->create([
            'tabel_no' => $tabelNo,
            'date' => $date->toDateString(),
            'requested_minutes' => $requestedMinutes,
            'approved_minutes' => 0,
            'status' => 'pending',
            'source' => $manualEntry !== null ? 'auto_manual_entry' : 'auto_ledger',
            'reason' => $manualEntry?->reason,
            'requested_by' => $causerId,
        ]);

        app(AttendanceAuditLogger::class)->log(
            event: 'overtime_request.created',
            description: 'Attendance overtime request created automatically.',
            subject: $request,
            properties: [
                'tabel_no' => $tabelNo,
                'date' => $date->toDateString(),
                'requested_minutes' => $requestedMinutes,
                'source' => $manualEntry !== null ? 'auto_manual_entry' : 'auto_ledger',
            ],
            causerId: $causerId
        );
    }

    /**
     * @param  Collection<int,AttendanceOvertimeRequest>  $requests
     */
    private function cleanupDuplicatePendings(Collection $requests): void
    {
        $pendingRequests = $requests->where('status', 'pending')->values();

        if ($pendingRequests->count() <= 1) {
            return;
        }

        $keep = $pendingRequests->first();

        foreach ($pendingRequests->slice(1) as $duplicate) {
            $duplicate->delete();

            app(AttendanceAuditLogger::class)->log(
                event: 'overtime_request.duplicate_deleted',
                description: 'Duplicate attendance overtime request deleted.',
                subject: $duplicate,
                properties: [
                    'kept_request_id' => $keep?->id,
                    'deleted_request_id' => $duplicate->id,
                    'tabel_no' => $duplicate->tabel_no,
                    'date' => $duplicate->date?->toDateString(),
                ],
                causerId: $duplicate->requested_by
            );
        }
    }

    private function resolveCauserId(
        ?AttendanceOvertimeRequest $request,
        ?AttendanceManualEntry $manualEntry
    ): ?int {
        return $request?->requested_by
            ?? $manualEntry?->approved_by
            ?? $manualEntry?->entered_by
            ?? User::query()->orderBy('id')->value('id');
    }

    private function resolveManualRequestableMinutes(
        string $tabelNo,
        Carbon $date,
        AttendanceManualEntry $manualEntry,
        array $ledgerPayload,
        ?AttendanceSetting $setting
    ): int {
        $context = app(AttendanceManualMetricsResolverService::class)->resolveBaselineContext(
            tabelNo: $tabelNo,
            date: $date->toDateString(),
            payload: [
                'shift_source_mode' => $manualEntry->calculation_shift_source ?? 'auto',
                'explicit_shift_id' => $manualEntry->calculation_shift_id,
            ]
        );

        $scheduledMinutes = (int) ($context['planned_minutes'] ?? 0);
        $workedMinutes = max(0, (int) $manualEntry->worked_minutes);
        $calendarDayType = (string) data_get($ledgerPayload, 'meta.calendar_day_type', 'workday');

        return app(AttendanceRulePolicyService::class)->resolveRequestableOvertimeMinutes(
            workedMinutes: $workedMinutes,
            scheduledMinutes: $scheduledMinutes,
            calendarDayType: $calendarDayType,
            setting: $setting
        );
    }
}
