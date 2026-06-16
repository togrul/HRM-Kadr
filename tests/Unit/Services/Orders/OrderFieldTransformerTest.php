<?php

namespace Tests\Unit\Services\Orders;

use App\Services\Orders\Document\OrderFieldTransformer;
use App\Support\Language\AzerbaijaniDateFormatter;
use PHPUnit\Framework\TestCase;

class OrderFieldTransformerTest extends TestCase
{
    private function transformer(): OrderFieldTransformer
    {
        return new OrderFieldTransformer(new AzerbaijaniDateFormatter);
    }

    public function test_work_year_start_date_expands_to_a_correct_span(): void
    {
        $out = $this->transformer()->transform(['work_year' => '2025-11-26', 'days' => '14']);

        $this->assertSame('26.11.2025-25.11.2026-cı il', $out['work_year']);
        $this->assertSame('14', $out['days']); // untouched
    }

    public function test_already_formatted_work_year_is_left_untouched(): void
    {
        $out = $this->transformer()->transform(['work_year' => '26.11.2025-25.11.2026-cı il']);

        $this->assertSame('26.11.2025-25.11.2026-cı il', $out['work_year']);
    }

    public function test_missing_work_year_is_a_no_op(): void
    {
        $this->assertSame(['days' => '14'], $this->transformer()->transform(['days' => '14']));
    }
}
