<?php

namespace Tests\Unit\Services;

use App\Modules\Orders\Support\Traits\Templates\HandlesSetTypeUiConfigSupport;
use ReflectionMethod;
use Tests\TestCase;

class SetTypeUiConfigRulesNormalizationTest extends TestCase
{
    public function test_it_generates_default_rules_when_draft_is_empty(): void
    {
        $subject = $this->makeSubject();

        $this->assertSame('nullable|int', $subject->normalize('', 'select', false));
        $this->assertSame('required|date', $subject->normalize('', 'date-input', true));
        $this->assertSame('nullable|string', $subject->normalize('', 'text-input', false));
    }

    public function test_it_keeps_rule_body_and_syncs_required_prefix_with_checkbox_state(): void
    {
        $subject = $this->makeSubject();

        $this->assertSame(
            'nullable|int|min:1',
            $subject->normalize('required|int|min:1', 'numeric-input', false)
        );

        $this->assertSame(
            'required|string|min:2',
            $subject->normalize('nullable|string|min:2', 'text-input', true)
        );
    }

    private function makeSubject(): object
    {
        return new class
        {
            use HandlesSetTypeUiConfigSupport;

            public function normalize(string $rules, string $input, bool $isRequired): string
            {
                $method = new ReflectionMethod($this, 'normalizeRulesDraftValue');
                $method->setAccessible(true);

                return (string) $method->invoke($this, $rules, $input, $isRequired);
            }
        };
    }
}
