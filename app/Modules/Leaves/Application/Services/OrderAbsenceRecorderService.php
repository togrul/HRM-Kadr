<?php

namespace App\Modules\Leaves\Application\Services;

use App\Enums\OrderStatusEnum;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Modules\Leaves\Contracts\OrderAbsenceRecorder;
use Carbon\CarbonImmutable;

/**
 * Files an order's paid absence as an approved, full-day leave of its own type. The
 * order is the approval, so the leave skips the leave approval route; it is stamped with
 * the "order" submission source so the register shows where it came from.
 */
class OrderAbsenceRecorderService implements OrderAbsenceRecorder
{
    public const SUBMISSION_SOURCE = 'order';

    public function record(
        string $tabelNo,
        CarbonImmutable $from,
        CarbonImmutable $to,
        string $typeName,
        string $attendanceCode,
        string $reason,
    ): int {
        $type = LeaveType::query()->firstOrCreate(
            ['name' => $typeName],
            ['attendance_code' => $attendanceCode, 'max_days' => 0, 'requires_document' => false],
        );

        $leave = Leave::query()->create([
            'tabel_no' => $tabelNo,
            'leave_type_id' => $type->id,
            'starts_at' => $from->toDateString(),
            'ends_at' => $to->toDateString(),
            'duration_unit' => 'day',
            'total_days' => (int) $from->diffInDays($to) + 1,
            'reason' => $reason,
            'status_id' => OrderStatusEnum::APPROVED->value,
            'approved_at' => now(),
            'submission_source' => self::SUBMISSION_SOURCE,
            'submitted_by_user_id' => auth()->id(),
        ]);

        return (int) $leave->getKey();
    }

    public function remove(int $leaveId): void
    {
        Leave::withTrashed()->whereKey($leaveId)->get()->each(fn (Leave $leave) => $leave->forceDelete());
    }
}
