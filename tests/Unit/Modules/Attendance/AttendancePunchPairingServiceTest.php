<?php

namespace Tests\Unit\Modules\Attendance;

use App\Models\AttendanceRawPunch;
use App\Modules\Attendance\Application\Services\AttendancePunchPairingService;
use Carbon\Carbon;
use Tests\TestCase;

class AttendancePunchPairingServiceTest extends TestCase
{
    public function test_pairs_in_and_out_events(): void
    {
        $service = new AttendancePunchPairingService();

        $punches = collect([
            new AttendanceRawPunch([
                'tabel_no' => 'TMP-01',
                'punched_at' => Carbon::parse('2026-03-05 09:00:00'),
                'direction' => 'in',
            ]),
            new AttendanceRawPunch([
                'tabel_no' => 'TMP-01',
                'punched_at' => Carbon::parse('2026-03-05 18:00:00'),
                'direction' => 'out',
            ]),
        ]);

        $result = $service->pair($punches);

        $this->assertSame(540, $result['worked_minutes']);
        $this->assertSame(0, $result['break_minutes']);
        $this->assertSame(0, $result['unmatched']);
        $this->assertFalse($result['missing_in']);
        $this->assertFalse($result['missing_out']);
        $this->assertCount(1, $result['pairs']);
        $this->assertIsArray($result['consumed_punch_ids']);
    }

    public function test_counts_break_minutes_when_break_events_exist(): void
    {
        $service = new AttendancePunchPairingService();

        $punches = collect([
            new AttendanceRawPunch([
                'tabel_no' => 'TMP-02',
                'punched_at' => Carbon::parse('2026-03-05 09:00:00'),
                'direction' => 'in',
            ]),
            new AttendanceRawPunch([
                'tabel_no' => 'TMP-02',
                'punched_at' => Carbon::parse('2026-03-05 13:00:00'),
                'direction' => 'break_out',
            ]),
            new AttendanceRawPunch([
                'tabel_no' => 'TMP-02',
                'punched_at' => Carbon::parse('2026-03-05 14:00:00'),
                'direction' => 'break_in',
            ]),
            new AttendanceRawPunch([
                'tabel_no' => 'TMP-02',
                'punched_at' => Carbon::parse('2026-03-05 18:00:00'),
                'direction' => 'out',
            ]),
        ]);

        $result = $service->pair($punches);

        $this->assertSame(480, $result['worked_minutes']);
        $this->assertSame(60, $result['break_minutes']);
        $this->assertSame(0, $result['unmatched']);
        $this->assertFalse($result['missing_in']);
        $this->assertFalse($result['missing_out']);
    }

    public function test_marks_missing_out_when_day_ends_with_open_in(): void
    {
        $service = new AttendancePunchPairingService();

        $punches = collect([
            new AttendanceRawPunch([
                'tabel_no' => 'TMP-03',
                'punched_at' => Carbon::parse('2026-03-05 09:00:00'),
                'direction' => 'in',
            ]),
        ]);

        $result = $service->pair($punches);

        $this->assertSame(1, $result['unmatched']);
        $this->assertFalse($result['missing_in']);
        $this->assertTrue($result['missing_out']);
    }
}
