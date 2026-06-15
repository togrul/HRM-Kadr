<?php

namespace Tests\Unit\Services\Orders;

use App\Services\Orders\Variables\OrderVariableRegistry;
use App\Services\Orders\Variables\VariableInterpolator;
use PHPUnit\Framework\TestCase;

class VariableInterpolatorTest extends TestCase
{
    private VariableInterpolator $interpolator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->interpolator = new VariableInterpolator;
    }

    public function test_it_substitutes_known_variables(): void
    {
        $text = '{{ employee.full_name_dative }} {{ field.days }} təqvim günü məzuniyyət verilsin.';

        $result = $this->interpolator->interpolate($text, [
            'employee.full_name_dative' => 'Cəfərova Fidan Məsud oğluna',
            'field.days' => '14',
        ]);

        $this->assertSame('Cəfərova Fidan Məsud oğluna 14 təqvim günü məzuniyyət verilsin.', $result);
    }

    public function test_missing_or_empty_values_become_the_marker(): void
    {
        $result = $this->interpolator->interpolate(
            '{{ employee.full_name }} — {{ field.unknown }}',
            ['employee.full_name' => '', 'employee.other' => 'x'],
            missing: '___',
        );

        $this->assertSame('___ — ___', $result);
    }

    public function test_it_extracts_distinct_placeholders(): void
    {
        $keys = $this->interpolator->placeholders('{{ a.b }} {{c.d}} {{ a.b }}');

        $this->assertSame(['a.b', 'c.d'], $keys);
    }

    public function test_it_flags_unresolvable_placeholders(): void
    {
        $registry = new OrderVariableRegistry;
        $text = '{{ employee.full_name_dative }} {{ field.days }} {{ made.up }}';

        $unresolvable = $this->interpolator->unresolvablePlaceholders($text, $registry, ['field.days']);

        // employee.* is in the registry, field.days is declared, made.up is neither.
        $this->assertSame(['made.up'], $unresolvable);
    }
}
