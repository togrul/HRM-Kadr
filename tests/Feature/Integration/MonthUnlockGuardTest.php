<?php

namespace Tests\Feature\Integration;

use App\Models\AttendanceExportMark;
use App\Models\AttendanceMonthlySummary;
use App\Modules\Attendance\Application\Services\AttendanceMonthLockService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A month the finance system has already paid from must not be reopened by
 * accident.
 *
 * Over there the month has produced payroll, journal entries and very likely a
 * closed accounting period. Editing it here afterwards changes nothing on that
 * side — the two simply stop agreeing, and nobody is told. That is the worst
 * shape a failure can take: invisible and permanent.
 *
 * The check is deliberately local. Asking the finance system would make the
 * answer depend on the network, and behind a firewall the safe answer would have
 * to be "assume closed" anyway — so it may as well work offline by construction.
 */
class MonthUnlockGuardTest extends TestCase
{
    use RefreshDatabase;

    /** An untouched month unlocks as before. */
    public function test_a_month_never_exported_unlocks_normally(): void
    {
        $this->lockedSummary();

        $stats = app(AttendanceMonthLockService::class)->unlockMonth(2026, 7);

        $this->assertSame(1, $stats['unlocked_summaries']);
    }

    /** Once handed over, it refuses. */
    public function test_an_exported_month_refuses_to_unlock(): void
    {
        $this->lockedSummary();
        AttendanceExportMark::mark(2026, 7);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/handed to the finance system/i');

        app(AttendanceMonthLockService::class)->unlockMonth(2026, 7);
    }

    /** The lock actually holds — nothing is quietly reopened before the throw. */
    public function test_the_refusal_leaves_the_month_locked(): void
    {
        $summary = $this->lockedSummary();
        AttendanceExportMark::mark(2026, 7);

        try {
            app(AttendanceMonthLockService::class)->unlockMonth(2026, 7);
        } catch (DomainException) {
            // expected
        }

        $this->assertTrue((bool) $summary->fresh()->is_locked);
    }

    /**
     * A deliberate correction is still possible.
     *
     * The point is not to forbid reopening — corrections are real — but to make
     * it an explicit act instead of a side effect of a button.
     */
    public function test_a_forced_unlock_is_allowed(): void
    {
        $this->lockedSummary();
        AttendanceExportMark::mark(2026, 7);

        $stats = app(AttendanceMonthLockService::class)->unlockMonth(2026, 7, force: true);

        $this->assertSame(1, $stats['unlocked_summaries']);
    }

    /** A mark on one month does not affect another. */
    public function test_the_mark_is_per_month(): void
    {
        $this->lockedSummary(month: 8);
        AttendanceExportMark::mark(2026, 7);

        $stats = app(AttendanceMonthLockService::class)->unlockMonth(2026, 8);

        $this->assertSame(1, $stats['unlocked_summaries']);
    }

    private function lockedSummary(int $month = 7): AttendanceMonthlySummary
    {
        return AttendanceMonthlySummary::query()->create([
            'tabel_no' => 'TB-1',
            'year' => 2026,
            'month' => $month,
            'total_scheduled_minutes' => 0,
            'total_worked_minutes' => 0,
            'total_overtime_minutes' => 0,
            'total_absence_minutes' => 0,
            'total_workdays' => 0,
            'total_present_days' => 0,
            'total_absence_days' => 0,
            'is_locked' => true,
        ]);
    }
}
