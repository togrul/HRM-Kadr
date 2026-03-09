<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\AttendanceSetting;
use App\Models\AttendanceShift;
use App\Models\AttendanceShiftAssignment;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class AttendanceShiftManagementService
{
    /**
     * @param  array<string,mixed>  $payload
     */
    public function upsertShift(array $payload, int $userId, ?AttendanceShift $shift = null): AttendanceShift
    {
        $shift ??= new AttendanceShift();

        $before = $shift->exists ? $shift->only([
            'name',
            'start_time',
            'end_time',
            'break_minutes',
            'is_night_shift',
            'in_flex_before_minutes',
            'in_flex_after_minutes',
            'out_flex_before_minutes',
            'out_flex_after_minutes',
            'is_active',
        ]) : [];

        $shift->name = trim((string) ($payload['name'] ?? ''));
        $shift->start_time = (string) ($payload['start_time'] ?? '09:00');
        $shift->end_time = (string) ($payload['end_time'] ?? '18:00');
        $shift->break_minutes = max(0, (int) ($payload['break_minutes'] ?? 0));
        $shift->is_night_shift = (bool) ($payload['is_night_shift'] ?? false);
        $shift->in_flex_before_minutes = max(0, (int) ($payload['in_flex_before_minutes'] ?? 0));
        $shift->in_flex_after_minutes = max(0, (int) ($payload['in_flex_after_minutes'] ?? 0));
        $shift->out_flex_before_minutes = max(0, (int) ($payload['out_flex_before_minutes'] ?? 0));
        $shift->out_flex_after_minutes = max(0, (int) ($payload['out_flex_after_minutes'] ?? 0));
        $shift->is_active = (bool) ($payload['is_active'] ?? true);
        $shift->updated_by = $userId;
        if (! $shift->exists) {
            $shift->created_by = $userId;
        }

        $shift->save();

        app(AttendanceAuditLogger::class)->log(
            event: $shift->wasRecentlyCreated ? 'shift.created' : 'shift.updated',
            description: $shift->wasRecentlyCreated ? 'Attendance shift created.' : 'Attendance shift updated.',
            subject: $shift,
            properties: [
                'before' => $before,
                'after' => $shift->only([
                    'name',
                    'start_time',
                    'end_time',
                    'break_minutes',
                    'is_night_shift',
                    'in_flex_before_minutes',
                    'in_flex_after_minutes',
                    'out_flex_before_minutes',
                    'out_flex_after_minutes',
                    'is_active',
                ]),
            ],
            causerId: $userId
        );

        return $shift->refresh();
    }

    /**
     * @throws ValidationException
     */
    public function deactivateShift(AttendanceShift $shift, int $userId): AttendanceShift
    {
        $usedAsDefault = AttendanceSetting::query()
            ->where('default_shift_id', $shift->id)
            ->where('is_active', true)
            ->exists();

        if ($usedAsDefault) {
            throw ValidationException::withMessages([
                'shift' => __('attendance::shift_management.messages.default_shift_selected'),
            ]);
        }

        $activeAssignments = AttendanceShiftAssignment::query()
            ->where('shift_id', $shift->id)
            ->where('is_active', true)
            ->exists();

        if ($activeAssignments) {
            throw ValidationException::withMessages([
                'shift' => __('attendance::shift_management.messages.active_assignments_exist'),
            ]);
        }

        $before = $shift->only(['is_active']);
        $shift->is_active = false;
        $shift->updated_by = $userId;
        $shift->save();

        app(AttendanceAuditLogger::class)->log(
            event: 'shift.deactivated',
            description: 'Attendance shift deactivated.',
            subject: $shift,
            properties: [
                'before' => $before,
                'after' => $shift->only(['is_active']),
            ],
            causerId: $userId
        );

        return $shift->refresh();
    }

    /**
     * @param  array<string,mixed>  $payload
     * @throws ValidationException
     */
    public function upsertAssignment(array $payload, int $userId, ?AttendanceShiftAssignment $assignment = null): AttendanceShiftAssignment
    {
        $assignment ??= new AttendanceShiftAssignment();

        $tabelNo = trim((string) ($payload['tabel_no'] ?? ''));
        $effectiveFrom = Carbon::parse((string) ($payload['effective_from'] ?? now()->toDateString()))->toDateString();
        $effectiveTo = filled($payload['effective_to'] ?? null)
            ? Carbon::parse((string) $payload['effective_to'])->toDateString()
            : null;

        $this->guardAssignmentOverlap(
            tabelNo: $tabelNo,
            effectiveFrom: $effectiveFrom,
            effectiveTo: $effectiveTo,
            ignoreId: $assignment->exists ? (int) $assignment->id : null
        );

        $before = $assignment->exists ? $assignment->only([
            'tabel_no',
            'shift_id',
            'effective_from',
            'effective_to',
            'assignment_source',
            'is_active',
        ]) : [];

        $assignment->tabel_no = $tabelNo;
        $assignment->shift_id = (int) ($payload['shift_id'] ?? 0);
        $assignment->effective_from = $effectiveFrom;
        $assignment->effective_to = $effectiveTo;
        $assignment->assignment_source = (string) ($payload['assignment_source'] ?? 'manual_ui');
        $assignment->is_active = (bool) ($payload['is_active'] ?? true);
        $assignment->updated_by = $userId;
        if (! $assignment->exists) {
            $assignment->created_by = $userId;
        }

        $assignment->save();

        app(AttendanceAuditLogger::class)->log(
            event: $assignment->wasRecentlyCreated ? 'shift_assignment.created' : 'shift_assignment.updated',
            description: $assignment->wasRecentlyCreated
                ? 'Attendance shift assignment created.'
                : 'Attendance shift assignment updated.',
            subject: $assignment,
            properties: [
                'before' => $before,
                'after' => $assignment->only([
                    'tabel_no',
                    'shift_id',
                    'effective_from',
                    'effective_to',
                    'assignment_source',
                    'is_active',
                ]),
            ],
            causerId: $userId
        );

        return $assignment->refresh();
    }

    public function deactivateAssignment(AttendanceShiftAssignment $assignment, int $userId): AttendanceShiftAssignment
    {
        $before = $assignment->only(['is_active', 'effective_to']);

        $assignment->is_active = false;
        if ($assignment->effective_to === null || $assignment->effective_to->isFuture()) {
            $assignment->effective_to = now()->toDateString();
        }
        $assignment->updated_by = $userId;
        $assignment->save();

        app(AttendanceAuditLogger::class)->log(
            event: 'shift_assignment.deactivated',
            description: 'Attendance shift assignment deactivated.',
            subject: $assignment,
            properties: [
                'before' => $before,
                'after' => $assignment->only(['is_active', 'effective_to']),
            ],
            causerId: $userId
        );

        return $assignment->refresh();
    }

    /**
     * @throws ValidationException
     */
    private function guardAssignmentOverlap(
        string $tabelNo,
        string $effectiveFrom,
        ?string $effectiveTo,
        ?int $ignoreId = null
    ): void {
        $query = AttendanceShiftAssignment::query()
            ->where('tabel_no', $tabelNo)
            ->where('is_active', true)
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->whereDate('effective_from', '<=', $effectiveTo ?? '2999-12-31')
            ->where(function ($q) use ($effectiveFrom): void {
                $q->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $effectiveFrom);
            });

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'assignment.tabel_no' => __('attendance::shift_management.messages.assignment_overlap'),
            ]);
        }
    }
}
