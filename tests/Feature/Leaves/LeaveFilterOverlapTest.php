<?php

namespace Tests\Feature\Leaves;

use App\Enums\OrderStatusEnum;
use App\Models\Leave;
use App\Models\OrderStatus;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveFilterOverlapTest extends TestCase
{
    use RefreshDatabase;

    public function test_filter_includes_leaves_that_overlap_the_selected_period(): void
    {
        OrderStatus::query()->create([
            'id' => OrderStatusEnum::APPROVED->value,
            'locale' => 'az',
            'name' => 'Təsdiqlənib',
        ]);

        $overlappingLeave = Leave::withoutEvents(fn () => Leave::query()->create([
            'tabel_no' => 'TB-OVERLAP',
            'leave_type_id' => null,
            'starts_at' => '2026-03-01',
            'ends_at' => '2026-03-10',
            'status_id' => OrderStatusEnum::APPROVED->value,
            'approved_at' => '2026-02-28 09:00:00',
        ]));

        Leave::withoutEvents(fn () => Leave::query()->create([
            'tabel_no' => 'TB-BEFORE',
            'leave_type_id' => null,
            'starts_at' => '2026-02-20',
            'ends_at' => '2026-02-25',
            'status_id' => OrderStatusEnum::APPROVED->value,
            'approved_at' => '2026-02-19 09:00:00',
        ]));

        Leave::withoutEvents(fn () => Leave::query()->create([
            'tabel_no' => 'TB-AFTER',
            'leave_type_id' => null,
            'starts_at' => '2026-03-15',
            'ends_at' => '2026-03-18',
            'status_id' => OrderStatusEnum::APPROVED->value,
            'approved_at' => '2026-03-14 09:00:00',
        ]));

        $results = Leave::query()
            ->filter([
                'starts_at' => CarbonImmutable::parse('2026-03-05')->toDateString(),
                'ends_at' => CarbonImmutable::parse('2026-03-06')->toDateString(),
            ])
            ->pluck('id')
            ->all();

        $this->assertContains($overlappingLeave->id, $results);
        $this->assertCount(1, $results);
    }
}
