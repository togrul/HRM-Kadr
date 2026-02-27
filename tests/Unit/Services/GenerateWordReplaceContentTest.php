<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Services\GenerateWordReplaceContent;
use Tests\TestCase;

class GenerateWordReplaceContentTest extends TestCase
{
    public function test_default_blade_replaces_title_and_content_without_forced_newline(): void
    {
        $service = new GenerateWordReplaceContent(
            Order::BLADE_DEFAULT,
            [
                0 => ['$fullname' => 'Ali', '$rank' => 'Leytenant'],
                1 => ['$fullname' => 'Veli', '$rank' => 'Kapitan'],
            ]
        );

        $result = $service->handle(
            key: 0,
            text: [
                'title' => 'Əmr: $rank',
                'content' => ['$fullname - $rank', '$fullname'],
            ],
            index: 0
        );

        $this->assertSame('Əmr: Leytenant', $result['title']);
        $this->assertSame('Ali - LeytenantVeli', $result['content']);
    }

    public function test_vacation_blade_appends_newline_per_content_row(): void
    {
        $service = new GenerateWordReplaceContent(
            Order::BLADE_VACATION,
            [
                0 => ['$fullname' => 'Aysel'],
                1 => ['$fullname' => 'Kamil'],
            ]
        );

        $result = $service->handle(
            key: 0,
            text: [
                'title' => 'Məzuniyyət',
                'content' => ['$fullname', '$fullname'],
            ],
            index: 0
        );

        $this->assertSame('Məzuniyyət', $result['title']);
        $this->assertSame('Aysel' . PHP_EOL . 'Kamil' . PHP_EOL, $result['content']);
    }

    public function test_business_trip_blade_formats_car_transport_row_and_keeps_row_newlines(): void
    {
        $service = new GenerateWordReplaceContent(
            Order::BLADE_BUSINESS_TRIP,
            [
                '0_CAR' => [
                    [
                        '$fullname' => 'Ali',
                        '$transportation' => 'CAR',
                        '$car' => '90-AA-001',
                        '$trip_location' => 'Bakı',
                    ],
                    [
                        '$fullname' => 'Veli',
                        '$transportation' => 'BUS',
                        '$car' => '',
                        '$trip_location' => 'Gəncə',
                    ],
                ],
            ]
        );

        $result = $service->handle(
            key: '0_CAR',
            text: [
                'title' => 'Ezamiyyət: $fullname',
                'content' => [
                    '$fullname $transportation $car $trip_location',
                    '$fullname $transportation $car $trip_location',
                ],
            ],
            index: 0
        );

        $this->assertSame('Ezamiyyət: Ali', $result['title']);
        $this->assertStringContainsString('Ali CAR Avtomaşın: 90-AA-001 Bakı', $result['content']);
        $this->assertStringContainsString('Veli BUS', $result['content']);
        $this->assertStringEndsWith(PHP_EOL, $result['content']);
    }
}

