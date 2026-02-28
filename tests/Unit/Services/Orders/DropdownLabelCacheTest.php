<?php

namespace Tests\Unit\Services\Orders;

use App\Modules\Orders\Support\Traits\Orders\DropdownLabelCache;
use App\Services\WordSuffixService;
use Tests\TestCase;

class DropdownLabelCacheTest extends TestCase
{
    public function test_it_keeps_structure_suffix_without_hyphen(): void
    {
        $subject = new class
        {
            use DropdownLabelCache;

            public function normalize(string $base, string $value): string
            {
                return $this->normalizeStructureSuffixLabel($base, $value, new WordSuffixService);
            }
        };

        $this->assertSame('idarenin', $subject->normalize('idare', 'idare'));
        $this->assertSame('idarənin', $subject->normalize('idarə', 'idarə-nin'));
    }
}

