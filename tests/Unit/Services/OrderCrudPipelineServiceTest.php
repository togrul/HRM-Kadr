<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Services\Orders\OrderCrudPipelineService;
use Tests\TestCase;

class OrderCrudPipelineServiceTest extends TestCase
{
    public function test_it_merges_rules_and_prepares_default_blade_payload(): void
    {
        $service = new OrderCrudPipelineService;

        $validatedRules = [];
        $defaultBladeData = [
            'attributes' => ['foo' => 'bar'],
            'personnel_ids' => [11],
            'component_ids' => [22],
        ];

        $result = $service->validateAndPrepare(
            selectedBlade: Order::BLADE_DEFAULT,
            validationRules: [
                'main' => ['orderForm.order_no' => 'required', 'skip' => ''],
                'dynamic' => ['componentForms.0.rank_id' => 'nullable|integer', 'empty' => null],
            ],
            validate: function (array $rules) use (&$validatedRules): void {
                $validatedRules = $rules;
            },
            prepareDefaultBladeData: fn () => $defaultBladeData,
            prepareBusinessTripBladeData: fn () => $this->fail('Business trip payload should not be prepared for default blade.'),
            prepareVacationBladeData: fn () => $this->fail('Vacation payload should not be prepared for default blade.'),
            resolveVacancyData: fn (array $bladeData) => [['vacancy' => ['row' => 1]], $bladeData === $defaultBladeData ? 'ok' : 'invalid'],
        );

        $this->assertSame([
            'orderForm.order_no' => 'required',
            'componentForms.0.rank_id' => 'nullable|integer',
        ], $validatedRules);

        $this->assertSame('ok', $result['message']);
        $this->assertSame($defaultBladeData['attributes'], $result['attributes']);
        $this->assertSame($defaultBladeData['personnel_ids'], $result['personnel_ids']);
        $this->assertSame($defaultBladeData['component_ids'], $result['component_ids']);
        $this->assertSame(['row' => 1], $result['vacancy_list']['vacancy']);
    }

    public function test_it_prepares_business_trip_payload_when_blade_matches(): void
    {
        $service = new OrderCrudPipelineService;

        $businessTripBladeData = [
            'attributes' => ['trip' => true],
            'personnel_ids' => [99],
            'component_ids' => [77],
        ];

        $result = $service->validateAndPrepare(
            selectedBlade: Order::BLADE_BUSINESS_TRIP,
            validationRules: ['main' => [], 'dynamic' => []],
            validate: fn (array $rules) => $this->assertSame([], $rules),
            prepareDefaultBladeData: fn () => $this->fail('Default payload should not be prepared for business trip blade.'),
            prepareBusinessTripBladeData: fn () => $businessTripBladeData,
            prepareVacationBladeData: fn () => $this->fail('Vacation payload should not be prepared for business trip blade.'),
            resolveVacancyData: fn () => [[], ''],
        );

        $this->assertSame($businessTripBladeData['attributes'], $result['attributes']);
        $this->assertSame($businessTripBladeData['personnel_ids'], $result['personnel_ids']);
        $this->assertSame($businessTripBladeData['component_ids'], $result['component_ids']);
    }
}
