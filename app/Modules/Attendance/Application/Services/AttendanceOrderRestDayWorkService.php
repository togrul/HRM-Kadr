<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\AttendanceOvertimeRequest;
use App\Modules\Attendance\Contracts\OrderRestDayWork;
use App\Services\Modules\ModuleState;
use App\Support\Database\InstalledTables;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Puts an order's rest-day / holiday work on record as an approved overtime request
 * (source "order") for the full standard working day. On a weekend or holiday every
 * worked minute is overtime, so the ledger marks the day "weekend/holiday worked" and the
 * approved minutes carry the order's compensation choice (double_pay | day_off) in their own
 * column, read by payroll through PayrollRestDayWork. The day's ledger
 * is recalculated once the order's transaction commits.
 */
class AttendanceOrderRestDayWorkService implements OrderRestDayWork
{
    public const SOURCE = 'order';

    /** A full standard working day (ƏM: 40-hour week, 8-hour day). */
    private const FULL_DAY_MINUTES = 480;

    public function __construct(private readonly ModuleState $modules) {}

    public function record(string $tabelNo, CarbonImmutable $date, string $compensation, string $reason): ?int
    {
        $requestedBy = auth()->id();

        if (! InstalledTables::has('attendance_overtime_requests') || $requestedBy === null) {
            return null;
        }

        $attributes = [
            'tabel_no' => $tabelNo,
            'date' => $date->toDateString(),
            'requested_minutes' => self::FULL_DAY_MINUTES,
            'approved_minutes' => self::FULL_DAY_MINUTES,
            'status' => 'approved',
            'source' => self::SOURCE,
            'reason' => $reason,
            'requested_by' => $requestedBy,
            'approved_by' => $requestedBy,
            'approved_at' => now(),
        ];

        if (InstalledTables::hasColumn('attendance_overtime_requests', 'compensation')) {
            $attributes['compensation'] = $compensation === self::COMPENSATION_DAY_OFF ? self::COMPENSATION_DAY_OFF : self::COMPENSATION_DOUBLE_PAY;
        }

        $request = AttendanceOvertimeRequest::query()->create($attributes);

        $this->recalculate($tabelNo, $date);

        return (int) $request->getKey();
    }

    public function remove(int $recordId): void
    {
        if (! InstalledTables::has('attendance_overtime_requests')) {
            return;
        }

        $request = AttendanceOvertimeRequest::withTrashed()->whereKey($recordId)->where('source', self::SOURCE)->first();

        if ($request === null) {
            return;
        }

        $tabelNo = (string) $request->getAttribute('tabel_no');
        $date = $request->date ? CarbonImmutable::parse($request->date->toDateString()) : null;
        $request->forceDelete();

        if ($date !== null) {
            $this->recalculate($tabelNo, $date);
        }
    }

    private function recalculate(string $tabelNo, CarbonImmutable $date): void
    {
        if (! $this->modules->enabled('attendance')) {
            return;
        }

        DB::afterCommit(function () use ($tabelNo, $date): void {
            app(AttendancePunchProcessingPipelineService::class)->process(
                from: Carbon::parse($date->toDateString())->startOfDay(),
                to: Carbon::parse($date->toDateString())->endOfDay(),
                source: null,
                options: [
                    'include_processed' => true,
                    'mark_processed' => false,
                    'tabel_nos' => [$tabelNo],
                ]
            );
        });
    }
}
