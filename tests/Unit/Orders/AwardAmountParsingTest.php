<?php

namespace Tests\Unit\Orders;

use App\Modules\Orders\Infrastructure\Document\Effects\AwardEffect;
use DomainException;
use Tests\TestCase;

class AwardAmountParsingTest extends TestCase
{
    public function test_it_reads_both_decimal_marks_and_thousand_separators(): void
    {
        foreach (['1 234,50', '1,234.50', '1.234,50', '1234.5', ' 1234,5 '] as $typed) {
            $this->assertSame(1234.5, AwardEffect::parseAmount($typed), $typed);
        }

        $this->assertNull(AwardEffect::parseAmount(''));
    }

    public function test_an_amount_that_is_not_a_number_stops_the_approval(): void
    {
        $this->expectException(DomainException::class);

        AwardEffect::parseAmount('yüz manat');
    }
}
