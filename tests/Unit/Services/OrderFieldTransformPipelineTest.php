<?php

namespace Tests\Unit\Services;

use App\Services\Orders\OrderFieldTransformPipeline;
use Tests\TestCase;

class OrderFieldTransformPipelineTest extends TestCase
{
    public function test_it_applies_transform_chain_in_order(): void
    {
        $pipeline = app(OrderFieldTransformPipeline::class);

        $result = $pipeline->apply('  ali  ', [
            'transforms' => [
                'trim',
                ['type' => 'upper'],
                ['type' => 'append', 'value' => '!'],
            ],
        ]);

        $this->assertSame('ALI!', $result);
    }

    public function test_it_formats_dates_and_supports_replace_options(): void
    {
        $pipeline = app(OrderFieldTransformPipeline::class);

        $result = $pipeline->apply('2026-02-25', [
            ['type' => 'date.format', 'format' => 'd.m.Y'],
            ['type' => 'replace', 'search' => '.', 'replace' => '/'],
        ]);

        $this->assertSame('25/02/2026', $result);
    }
}

