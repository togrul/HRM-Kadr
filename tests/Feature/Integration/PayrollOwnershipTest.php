<?php

namespace Tests\Feature\Integration;

use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Modules\Integration\Domain\Contracts\PayrollOwnership;
use App\Modules\Integration\Support\ConfiguredPayrollOwnership;
use App\Modules\Payroll\Application\Services\PayrollRunService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Who computes payroll.
 *
 * ## HR cannot do this job, and the reason is not organisational
 *
 * A payroll calculation needs the progressive tax brackets, the social-insurance
 * rates by sector, the average-earnings rules, the garnishment ceilings and the
 * accounting periods the result posts into. None of that lives here. What lives
 * here is the *conditions*: who is employed, on what base pay, with which
 * allowances, for how many days.
 *
 * So when the finance system is connected this side stops computing. Running
 * both engines would produce two answers to the same question, and the only way
 * anyone would discover they disagreed is an employee noticing their payslip.
 *
 * The conditions keep flowing — that is the whole point of the connection.
 */
class PayrollOwnershipTest extends TestCase
{
    use RefreshDatabase;

    /** A standalone installation keeps computing, exactly as before. */
    public function test_a_standalone_installation_still_computes(): void
    {
        config(['integration.payroll_owner' => ConfiguredPayrollOwnership::SELF]);

        $this->assertTrue(app(PayrollOwnership::class)->isOurs());

        // The run reaches the real calculation rather than the guard.
        $run = $this->draftRun();

        app(PayrollRunService::class)->calculate($run);

        $this->assertSame('calculated', $run->fresh()->status);
    }

    /** With finance connected, computing here is refused — loudly. */
    public function test_calculation_is_refused_when_finance_owns_payroll(): void
    {
        config(['integration.payroll_owner' => ConfiguredPayrollOwnership::FINANCE]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/computed by the finance system/i');

        app(PayrollRunService::class)->calculate($this->draftRun());
    }

    /** Approving and locking are refused for the same reason. */
    public function test_approval_and_locking_are_refused_too(): void
    {
        config(['integration.payroll_owner' => ConfiguredPayrollOwnership::FINANCE]);

        $service = app(PayrollRunService::class);
        $run = $this->draftRun();

        foreach (['approve', 'lock'] as $method) {
            try {
                $service->{$method}($run);
                $this->fail("{$method}() should have been refused.");
            } catch (RuntimeException $e) {
                $this->assertStringContainsStringIgnoringCase('finance system', $e->getMessage());
            }
        }
    }

    /**
     * Reading is never blocked.
     *
     * Existing runs and payslips stay visible: an employee must still be able to
     * see what they were paid, and that is a result, not a mechanism.
     */
    public function test_existing_runs_remain_readable(): void
    {
        config(['integration.payroll_owner' => ConfiguredPayrollOwnership::FINANCE]);

        $run = $this->draftRun();

        $this->assertSame('draft', PayrollRun::query()->findOrFail($run->id)->status);
        $this->assertSame(1, PayrollRun::query()->count());
    }

    /** The default is standalone — an existing installation is untouched. */
    public function test_the_default_is_standalone(): void
    {
        config(['integration.payroll_owner' => null]);

        $this->assertTrue(app(PayrollOwnership::class)->isOurs());
    }

    private function draftRun(): PayrollRun
    {
        $period = PayrollPeriod::query()->firstOrCreate(['code' => '2026-07'], [
            'year' => 2026,
            'month' => 7,
            'starts_on' => '2026-07-01',
            'ends_on' => '2026-07-31',
            'currency' => 'AZN',
            'status' => 'open',
        ]);

        return app(PayrollRunService::class)->createRun($period);
    }
}
