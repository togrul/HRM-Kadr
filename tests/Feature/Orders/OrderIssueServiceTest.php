<?php

namespace Tests\Feature\Orders;

use App\Models\Personnel;
use App\Services\Orders\Document\OrderIssueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
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

    public function test_it_updates_a_pending_block_order_in_place(): void
    {
        $first = $this->makePersonnel();
        $second = $this->makePersonnel();
        $service = app(OrderIssueService::class);

        $order = $service->issue([
            'template_code' => 'leave',
            'label' => 'Əmək məzuniyyəti',
            'personnel_id' => $first->id,
            'fields' => ['days' => '14'],
            'order_number' => '900-M',
            'order_date' => '14 may 2026-cı il',
            'snapshot_html' => '<div class="order-document"><p>Köhnə mətn</p></div>',
        ]);

        $updated = $service->update($order, [
            'template_code' => 'leave',
            'label' => 'Əmək məzuniyyəti',
            'personnel_id' => $second->id,
            'fields' => ['days' => '21'],
            'order_number' => '901-M',
            'order_date' => '20 may 2026-cı il',
            'snapshot_html' => '<div class="order-document"><p>Düzəliş edilmiş mətn</p></div>',
        ]);

        $this->assertSame($order->id, $updated->id);   // same row, no new order
        $this->assertSame('901-M', $updated->fresh()->order_no);
        $this->assertSame('21', data_get($updated->fresh()->template_snapshot, 'fields.days'));
        $this->assertStringContainsString('Düzəliş edilmiş mətn', data_get($updated->fresh()->template_snapshot, 'html'));
        // personnel re-synced to the new employee only
        $this->assertTrue($updated->personnels()->where('personnels.id', $second->id)->exists());
        $this->assertFalse($updated->personnels()->where('personnels.id', $first->id)->exists());
    }

    public function test_it_refuses_to_edit_an_approved_order(): void
    {
        $personnel = $this->makePersonnel();
        $service = app(OrderIssueService::class);

        $order = $service->issue([
            'template_code' => 'leave',
            'personnel_id' => $personnel->id,
            'order_number' => '902-M',
            'snapshot_html' => '<div class="order-document"><p>x</p></div>',
        ]);
        $order->update(['status_id' => 20]); // approved

        $this->expectException(RuntimeException::class);

        $service->update($order, [
            'order_number' => '902-M',
            'snapshot_html' => '<div class="order-document"><p>y</p></div>',
        ]);
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
