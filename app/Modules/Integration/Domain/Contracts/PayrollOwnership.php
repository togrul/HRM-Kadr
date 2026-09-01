<?php

namespace App\Modules\Integration\Domain\Contracts;

/**
 * Answers one question: does this system still compute payroll?
 *
 * ## Why HR cannot do that job
 *
 * A payroll calculation needs progressive tax brackets, social-insurance rates
 * by sector, average-earnings rules, garnishment ceilings and the accounting
 * periods the result posts into. None of that lives here. What lives here is the
 * *conditions*: who is employed, on what base pay, with which allowances, for
 * how many days.
 *
 * So when the finance system is connected, this side stops computing and keeps
 * supplying conditions. Running both engines would produce two answers to the
 * same question, and the only way anyone would notice they disagreed is an
 * employee reading their payslip.
 *
 * Published as a contract because the Payroll module consumes it, and a module
 * may only reach another module through its `Contracts\` surface.
 */
interface PayrollOwnership
{
    /** True when payroll is still ours to compute. */
    public function isOurs(): bool;

    public function isFinance(): bool;
}
