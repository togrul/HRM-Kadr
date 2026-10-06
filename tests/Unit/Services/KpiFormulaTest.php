<?php

namespace Tests\Unit\Services;

use App\Modules\PerformanceEvaluation\Application\Services\Kpi\KpiFormula;
use InvalidArgumentException;
use Tests\TestCase;

class KpiFormulaTest extends TestCase
{
    public function test_arithmetic_functions_and_references(): void
    {
        $formula = new KpiFormula;
        $values = ['FACT' => 110000.0, 'PLAN' => 100000.0, 'ZERO' => 0.0];

        $this->assertSame(110.0, $formula->evaluate('{FACT} / {PLAN} * 100', $values));
        $this->assertSame(14.0, $formula->evaluate('2 + 3 * 4', []));
        $this->assertSame(20.0, $formula->evaluate('(2 + 3) * 4', []));
        $this->assertSame(-5.0, $formula->evaluate('-(2 + 3)', []));
        $this->assertSame(33.33, $formula->evaluate('ROUND(100 / 3, 2)', []));
        $this->assertSame(120.0, $formula->evaluate('MIN({FACT} / {PLAN} * 100 + 50, 120)', $values));
        $this->assertSame(1.5, $formula->evaluate('MAX(1, 1.5, 0.5)', []));
        $this->assertSame(1.0, $formula->evaluate('{FACT} >= {PLAN}', $values));
        $this->assertSame(100.0, $formula->evaluate('IF({FACT} > {PLAN}, 100, 0)', $values));
        $this->assertSame(0.0, $formula->evaluate('IF({FACT} < {PLAN}, {MISSING}, 0)', $values), 'The branch not taken may be empty.');
        $this->assertSame(['FACT', 'PLAN'], $formula->references('{FACT} / {PLAN} + {FACT}'));
    }

    public function test_missing_inputs_and_division_by_zero_leave_it_unscored(): void
    {
        $formula = new KpiFormula;

        $this->assertNull($formula->evaluate('{FACT} / {PLAN}', ['FACT' => 10.0]));
        $this->assertNull($formula->evaluate('10 / {ZERO}', ['ZERO' => 0.0]));
    }

    public function test_bad_formulas_are_rejected_without_eval(): void
    {
        $formula = new KpiFormula;

        foreach (['', '2 +', '(1 + 2', 'SYSTEM(1)', '1; phpinfo()', 'ABS(1, 2)', 'IF(1, 2)', '{lower}'] as $bad) {
            try {
                $formula->references($bad);
                $this->fail("Expected a syntax error for [{$bad}]");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
