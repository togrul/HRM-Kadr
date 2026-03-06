<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\AttendanceManualEntry;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class AttendanceManualEntryService
{
    /**
     * @param  array<string,mixed>  $payload
     */
    public function upsert(string $tabelNo, string $date, array $payload, int $enteredBy): AttendanceManualEntry
    {
        $entryDate = Carbon::parse($date)->toDateString();
        if (app(AttendanceMonthLockService::class)->isPeriodLocked($entryDate)) {
            throw ValidationException::withMessages([
                'date' => 'Selected month is locked. Manual entries cannot be changed.',
            ]);
        }

        $entry = AttendanceManualEntry::query()
            ->firstOrNew([
                'tabel_no' => $tabelNo,
                'date' => $entryDate,
            ]);

        $before = $entry->exists ? $entry->only([
            'check_in_at',
            'check_out_at',
            'worked_minutes',
            'overtime_minutes',
            'late_minutes',
            'early_leave_minutes',
            'calculation_shift_source',
            'calculation_shift_id',
            'absence_code',
            'reason',
            'approval_status',
            'approved_by',
            'approved_at',
        ]) : [];

        $computed = app(AttendanceManualMetricsResolverService::class)->resolve($tabelNo, $entryDate, $payload);

        $entry->check_in_at = $computed['check_in_at'];
        $entry->check_out_at = $computed['check_out_at'];
        $entry->worked_minutes = $computed['worked_minutes'];
        $entry->overtime_minutes = $computed['overtime_minutes'];
        $entry->late_minutes = $computed['late_minutes'];
        $entry->early_leave_minutes = $computed['early_leave_minutes'];
        $entry->calculation_shift_source = (string) ($payload['shift_source_mode'] ?? 'auto');
        $entry->calculation_shift_id = (
            ($payload['shift_source_mode'] ?? 'auto') === 'explicit'
            && is_numeric($payload['explicit_shift_id'] ?? null)
        ) ? (int) $payload['explicit_shift_id'] : null;
        $entry->absence_code = $payload['absence_code'] ?: null;
        $entry->reason = $payload['reason'] ?: null;
        $entry->approval_status = 'pending';
        $entry->entered_by = $enteredBy;
        $entry->approved_by = null;
        $entry->approved_at = null;

        $entry->save();

        app(AttendanceAuditLogger::class)->log(
            event: $entry->wasRecentlyCreated ? 'manual_entry.created' : 'manual_entry.updated',
            description: $entry->wasRecentlyCreated ? 'Manual attendance entry created.' : 'Manual attendance entry updated.',
            subject: $entry,
            properties: [
                'tabel_no' => $entry->tabel_no,
                'date' => $entry->date?->toDateString(),
                'before' => $before,
                'after' => $entry->only([
                    'check_in_at',
                    'check_out_at',
                    'worked_minutes',
                    'overtime_minutes',
                    'late_minutes',
                    'early_leave_minutes',
                    'calculation_shift_source',
                    'calculation_shift_id',
                    'absence_code',
                    'reason',
                    'approval_status',
                ]),
            ],
            causerId: $enteredBy
        );

        $this->recalculate($entry->tabel_no, Carbon::parse($entryDate));

        return $entry;
    }

    /**
     * @throws ValidationException
     */
    public function approve(AttendanceManualEntry $entry, int $approvedBy): AttendanceManualEntry
    {
        $entryDate = $entry->date?->toDateString() ?: now()->toDateString();
        if (app(AttendanceMonthLockService::class)->isPeriodLocked($entryDate)) {
            throw ValidationException::withMessages([
                'date' => 'Selected month is locked. Manual entries cannot be approved.',
            ]);
        }

        $before = $entry->only(['approval_status', 'approved_by', 'approved_at']);

        $entry->approval_status = 'approved';
        $entry->approved_by = $approvedBy;
        $entry->approved_at = now();
        $entry->save();

        app(AttendanceAuditLogger::class)->log(
            event: 'manual_entry.approved',
            description: 'Manual attendance entry approved.',
            subject: $entry,
            properties: [
                'tabel_no' => $entry->tabel_no,
                'date' => $entry->date?->toDateString(),
                'before' => $before,
                'after' => $entry->only(['approval_status', 'approved_by', 'approved_at']),
            ],
            causerId: $approvedBy
        );

        $this->recalculate($entry->tabel_no, Carbon::parse($entryDate));

        return $entry->refresh();
    }

    /**
     * @throws ValidationException
     */
    public function reject(AttendanceManualEntry $entry, int $approvedBy, ?string $note = null): AttendanceManualEntry
    {
        $entryDate = $entry->date?->toDateString() ?: now()->toDateString();
        if (app(AttendanceMonthLockService::class)->isPeriodLocked($entryDate)) {
            throw ValidationException::withMessages([
                'date' => 'Selected month is locked. Manual entries cannot be rejected.',
            ]);
        }

        $before = $entry->only(['approval_status', 'approved_by', 'approved_at', 'reason']);

        $entry->approval_status = 'rejected';
        $entry->approved_by = $approvedBy;
        $entry->approved_at = now();
        if ($note !== null && $note !== '') {
            $entry->reason = trim((string) $entry->reason."\n\n[Reject note] ".$note);
        }
        $entry->save();

        app(AttendanceAuditLogger::class)->log(
            event: 'manual_entry.rejected',
            description: 'Manual attendance entry rejected.',
            subject: $entry,
            properties: [
                'tabel_no' => $entry->tabel_no,
                'date' => $entry->date?->toDateString(),
                'before' => $before,
                'after' => $entry->only(['approval_status', 'approved_by', 'approved_at', 'reason']),
                'reject_note' => $note,
            ],
            causerId: $approvedBy
        );

        $this->recalculate($entry->tabel_no, Carbon::parse($entryDate));

        return $entry->refresh();
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
