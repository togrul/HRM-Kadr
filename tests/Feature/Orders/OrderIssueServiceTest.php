<?php

namespace Tests\Feature\Orders;

use App\Models\Personnel;
use App\Services\Orders\Document\OrderIssueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 6 bridge: a block-engine order persists into the shared order_logs list
 * (no legacy order/order_type row) and prints from its frozen snapshot.
 */
class OrderIssueServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_a_block_order_with_snapshot_and_personnel(): void
    {
        $personnel = $this->makePersonnel();

        $order = app(OrderIssueService::class)->issue([
            'template_code' => 'leave',
            'label' => 'Əmək məzuniyyəti',
            'personnel_id' => $personnel->id,
            'fields' => ['days' => '14'],
            'order_number' => '900-M',
            'order_date' => '14 may 2026-cı il',
            'snapshot_html' => '<div class="order-document"><p>Test əmri</p></div>',
        ]);

        $this->assertNull($order->order_id);          // no legacy linkage
        $this->assertNull($order->order_type_id);
        $this->assertSame('block_v2', $order->template_render_mode);
        $this->assertSame(OrderIssueService::STATUS_PENDING, $order->status_id);
        $this->assertSame('900-M', $order->order_no);
        $this->assertSame('Əmək məzuniyyəti', data_get($order->template_snapshot, 'label'));
        $this->assertTrue($order->personnels()->where('personnels.id', $personnel->id)->exists());
    }

    private function makePersonnel(): Personnel
    {
        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'TB'.Str::upper(Str::random(6)),
            'surname' => 'Bayramov',
            'name' => 'Ruslan',
            'patronymic' => 'Bəxtiyar',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'email' => Str::lower(Str::random(8)).'@example.com',
            'mobile' => '994501112233',
            'nationality_id' => 1,
            'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
            'residental_address' => 'Main st',
            'education_degree_id' => 1,
            'work_norm_id' => 1,
            'structure_id' => 1,
            'position_id' => 1,
            'join_work_date' => '2026-03-01',
            'added_by' => 1,
            'is_pending' => false,
        ]));
    }
}
