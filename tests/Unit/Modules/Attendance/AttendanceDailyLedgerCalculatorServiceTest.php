<?php

namespace Tests\Unit\Modules\Attendance;

use App\Models\AttendanceManualEntry;
use App\Models\AttendanceSetting;
use App\Models\AttendanceShift;
use App\Modules\Attendance\Application\Services\AttendanceDailyLedgerCalculatorService;
use Carbon\Carbon;
use Tests\TestCase;

class AttendanceDailyLedgerCalculatorServiceTest extends TestCase
{
    public function test_calculates_workday_ledger_with_late_and_early_minutes(): void
    {
        $service = new AttendanceDailyLedgerCalculatorService();

        $shift = new AttendanceShift([
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'break_minutes' => 60,
            'is_night_shift' => false,
        ]);
        $shift->id = 1;

        $setting = new AttendanceSetting([
            'late_grace_minutes' => 5,
            'early_leave_grace_minutes' => 5,
        ]);

        $pairing = [
            'worked_minutes' => 460,
            'break_minutes' => 20,
            'unmatched' => 0,
            'first_in_at' => '2026-03-05 09:10:00',
            'last_out_at' => '2026-03-05 17:50:00',
            'pairs' => [],
        ];

        $result = $service->calculate(
            date: Carbon::parse('2026-03-05'),
            pairing: $pairing,
            shift: $shift,
            manualEntry: null,
            setting: $setting,
            calendarDayType: 'workday'
        );

        $this->assertSame(480, $result['scheduled_minutes']);
        $this->assertSame(460, $result['worked_minutes']);
        $this->assertSame(20, $result['break_minutes']);
        $this->assertSame(5, $result['late_minutes']);
        $this->assertSame(5, $result['early_leave_minutes']);
        $this->assertSame('present', $result['attendance_status']);
    }

    public function test_manual_entry_overrides_system_calculation(): void
    {
        $service = new AttendanceDailyLedgerCalculatorService();

        $manual = new AttendanceManualEntry([
            'worked_minutes' => 300,
            'overtime_minutes' => 15,
            'absence_code' => null,
            'approval_status' => 'approved',
        ]);
        $manual->id = 11;

        $result = $service->calculate(
            date: Carbon::parse('2026-03-06'),
            pairing: [
                'worked_minutes' => 0,
                'break_minutes' => 0,
                'unmatched' => 0,
                'first_in_at' => null,
                'last_out_at' => null,
                'pairs' => [],
            ],
            shift: null,
            manualEntry: $manual,
            setting: null,
            calendarDayType: 'workday'
        );

        $this->assertSame(300, $result['worked_minutes']);
        $this->assertSame(15, $result['overtime_minutes']);
        $this->assertSame('manual_present', $result['attendance_status']);
        $this->assertSame('manual_override', $result['source_summary']);
    }

    public function test_uses_approved_overtime_when_policy_is_by_approval(): void
    {
        $service = new AttendanceDailyLedgerCalculatorService();

        $shift = new AttendanceShift([
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'break_minutes' => 60,
            'is_night_shift' => false,
        ]);
        $shift->id = 2;

        $setting = new AttendanceSetting([
            'overtime_policy' => 'by_approval',
            'rounding_policy' => 'none',
        ]);

        $result = $service->calculate(
            date: Carbon::parse('2026-03-07'),
            pairing: [
                'worked_minutes' => 600,
                'break_minutes' => 30,
                'unmatched' => 0,
                'first_in_at' => '2026-03-07 09:00:00',
                'last_out_at' => '2026-03-07 19:00:00',
                'pairs' => [],
            ],
            shift: $shift,
            manualEntry: null,
            setting: $setting,
            calendarDayType: 'workday',
            override: null,
            approvedOvertimeMinutes: 120
        );

        $this->assertSame(120, $result['overtime_minutes']);
    }

    public function test_handles_night_shift_with_cross_day_late_and_early(): void
    {
        $service = new AttendanceDailyLedgerCalculatorService();

        $shift = new AttendanceShift([
            'start_time' => '22:00:00',
            'end_time' => '06:00:00',
            'break_minutes' => 30,
            'is_night_shift' => true,
        ]);
        $shift->id = 3;

        $setting = new AttendanceSetting([
            'late_grace_minutes' => 0,
            'early_leave_grace_minutes' => 0,
            'overtime_policy' => 'after_shift',
        ]);

        $result = $service->calculate(
            date: Carbon::parse('2026-03-08'),
            pairing: [
                'worked_minutes' => 450,
                'break_minutes' => 20,
                'unmatched' => 0,
                'first_in_at' => '2026-03-08 22:10:00',
                'last_out_at' => '2026-03-09 05:50:00',
                'pairs' => [],
            ],
            shift: $shift,
            manualEntry: null,
            setting: $setting,
            calendarDayType: 'workday'
        );

        $this->assertSame(450, $result['scheduled_minutes']);
        $this->assertSame(10, $result['late_minutes']);
        $this->assertSame(10, $result['early_leave_minutes']);
    }

    public function test_applies_leave_override(): void
    {
        $service = new AttendanceDailyLedgerCalculatorService();

        $shift = new AttendanceShift([
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'break_minutes' => 60,
            'is_night_shift' => false,
        ]);
        $shift->id = 4;

        $result = $service->calculate(
            date: Carbon::parse('2026-03-10'),
            pairing: [
                'worked_minutes' => 0,
                'break_minutes' => 0,
                'unmatched' => 0,
                'first_in_at' => null,
                'last_out_at' => null,
                'pairs' => [],
            ],
            shift: $shift,
            manualEntry: null,
            setting: new AttendanceSetting([
                'overtime_policy' => 'after_shift',
            ]),
            calendarDayType: 'workday',
            override: [
                'type' => 'leave',
                'source' => 'leave',
                'absence_code' => 'SICK',
                'leave_type_id' => 7,
                'leave_type_name' => 'Xəstəlik',
            ]
        );

        $this->assertSame('leave', $result['attendance_status']);
        $this->assertSame('SICK', $result['absence_code']);
        $this->assertSame(0, $result['worked_minutes']);
        $this->assertSame('policy_override', $result['source_summary']);
        $this->assertSame(7, $result['meta']['leave_type_id']);
        $this->assertSame('Xəstəlik', $result['meta']['leave_type_name']);
    }
}
