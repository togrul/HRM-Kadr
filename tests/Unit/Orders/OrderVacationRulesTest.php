<?php

namespace Tests\Unit\Orders;

use App\Models\OrderWordTemplate;
use App\Modules\Orders\Application\Document\OrderVacationRules;
use Tests\TestCase;

class OrderVacationRulesTest extends TestCase
{
    private function rules(): OrderVacationRules
    {
        return app(OrderVacationRules::class);
    }

    /**
     * @param  array<int,array<string,mixed>>  $variables
     */
    private function template(?string $effect, array $variables): OrderWordTemplate
    {
        return new OrderWordTemplate(['effect' => $effect, 'variables' => $variables]);
    }

    private function paidLeave(): OrderWordTemplate
    {
        return $this->template('vacation', [
            ['token' => 'var_1', 'effect_role' => null],
            ['token' => 'var_2', 'effect_role' => 'start_date'],
            ['token' => 'var_4', 'effect_role' => 'days'],
        ]);
    }

    public function test_only_vacations_with_a_day_count_are_day_counted(): void
    {
        $this->assertTrue($this->rules()->isDayCounted($this->paidLeave()));
        $this->assertFalse($this->rules()->isDayCounted($this->template('vacation', [['token' => 'var_2', 'effect_role' => 'start_date']])));
        $this->assertFalse($this->rules()->isDayCounted($this->template('hire', [['token' => 'var_4', 'effect_role' => 'days']])));
        $this->assertFalse($this->rules()->isDayCounted($this->template(null, [])));
    }

    public function test_the_request_counts_against_the_start_dates_year(): void
    {
        $request = $this->rules()->request($this->paidLeave(), ['var_2' => '19.05.2031-ci il', 'var_4' => '14']);

        $this->assertSame(['year' => 2031, 'requested' => 14], $request);
    }

    public function test_a_missing_start_date_and_day_count_fall_back_to_this_year_and_zero(): void
    {
        $this->assertSame(['year' => (int) now()->year, 'requested' => 0], $this->rules()->request($this->paidLeave(), []));
    }

    public function test_effect_field_value_reads_the_token_carrying_the_role(): void
    {
        $rules = $this->rules();

        $this->assertSame('7', $rules->effectFieldValue($this->paidLeave(), ['var_4' => 7], 'days'));
        $this->assertNull($rules->effectFieldValue($this->paidLeave(), [], 'days'));
        $this->assertNull($rules->effectFieldValue($this->paidLeave(), ['var_4' => 7], 'end_date'));
    }

    public function test_violation_blocks_zero_days_and_overdrafts_only(): void
    {
        $balance = ['year' => 2026, 'total' => 30, 'used' => 25, 'remaining' => 5];

        $this->assertSame(__('orders::order_composer.vacation.min_days'), $this->rules()->violation($balance + ['requested' => 0]));
        $this->assertNotNull($this->rules()->violation($balance + ['requested' => 6]));
        $this->assertNull($this->rules()->violation($balance + ['requested' => 5]));
        $this->assertNull($this->rules()->violation($balance + ['requested' => 1]));
    }
}
