<?php

namespace Tests\Feature\Orders;

use App\Models\Personnel;
use App\Services\Orders\Document\Effects\BlockOrderApprovalService;
use App\Services\Orders\Document\OrderIssueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 6, step 2: approving a block leave order produces the personnel vacation
 * record (the HR side-effect), via the pluggable per-type effect registry.
 */
class BlockOrderApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_approving_a_leave_order_creates_a_vacation_record(): void
    {
        $this->actingAs(\App\Models\User::factory()->create());
        $personnel = $this->makePersonnel();

        $order = app(OrderIssueService::class)->issue([
            'template_code' => 'leave',
            'personnel_id' => $personnel->id,
            'fields' => [
                'start_date' => '19.05.2026-cı il',
                'end_date' => '03.06.2026-cı il',
                'return_date' => '04.06.2026-cı il',
                'days' => '14',
            ],
            'order_number' => '700-M',
            'snapshot_html' => '<div>x</div>',
        ]);

        app(BlockOrderApprovalService::class)->approve($order);

        $this->assertSame(BlockOrderApprovalService::STATUS_APPROVED, $order->fresh()->status_id);

        $vacation = $personnel->vacations()->first();
        $this->assertNotNull($vacation);
        $this->assertSame('2026-05-19', $vacation->start_date->format('Y-m-d'));
        $this->assertSame('2026-06-03', $vacation->end_date->format('Y-m-d'));
        $this->assertSame('2026-06-04', $vacation->return_work_date->format('Y-m-d'));
        $this->assertSame(14, (int) $vacation->duration);
        $this->assertSame('700-M', $vacation->order_no);
    }

    public function test_approving_a_termination_ends_employment(): void
    {
        $this->actingAs(\App\Models\User::factory()->create());
        $personnel = $this->makePersonnel();
        $this->assertNull($personnel->leave_work_date);

        $order = app(OrderIssueService::class)->issue([
            'template_code' => 'termination_request',
            'personnel_id' => $personnel->id,
            'fields' => ['date' => '09.06.2026-cı il'],
            'order_number' => '710-K',
            'snapshot_html' => '<div>x</div>',
        ]);

        app(BlockOrderApprovalService::class)->approve($order);

        $this->assertSame('2026-06-09', $personnel->fresh()->leave_work_date->format('Y-m-d'));
    }

    public function test_approving_a_surname_change_updates_the_surname(): void
    {
        $this->actingAs(\App\Models\User::factory()->create());
        $personnel = $this->makePersonnel();

        $order = app(OrderIssueService::class)->issue([
            'template_code' => 'surname_change',
            'personnel_id' => $personnel->id,
            'fields' => ['new_surname' => 'Bababəyli'],
            'order_number' => '711-İ',
            'snapshot_html' => '<div>x</div>',
        ]);

        app(BlockOrderApprovalService::class)->approve($order);

        $fresh = $personnel->fresh();
        $this->assertSame('Bababəyli', $fresh->surname);
        $this->assertSame('Bayramov', $fresh->previous_surname);
    }

    public function test_approving_a_transfer_moves_the_personnel(): void
    {
        $this->actingAs(\App\Models\User::factory()->create());
        $personnel = $this->makePersonnel();
        $structure = \App\Models\Structure::query()->create(['name' => 'Yeni şöbə', 'shortname' => 'YŞ']);
        $position = \App\Models\Position::query()->create(['name' => 'rəis']);

        $order = app(OrderIssueService::class)->issue([
            'template_code' => 'transfer',
            'personnel_id' => $personnel->id,
            'fields' => ['new_structure' => (string) $structure->id, 'new_position' => (string) $position->id],
            'order_number' => '712-K',
            'snapshot_html' => '<div>x</div>',
        ]);

        app(BlockOrderApprovalService::class)->approve($order);

        $fresh = $personnel->fresh();
        $this->assertSame($structure->id, $fresh->structure_id);
        $this->assertSame($position->id, $fresh->position_id);
    }

    public function test_unmapped_type_has_no_side_effect(): void
    {
        $personnel = $this->makePersonnel();
        $order = app(OrderIssueService::class)->issue([
            'template_code' => 'hire', // employment effect not wired yet (tabel_no/rank)
            'personnel_id' => $personnel->id,
            'order_number' => '701-İ',
            'snapshot_html' => '<div>x</div>',
        ]);

        app(BlockOrderApprovalService::class)->approve($order);

        $this->assertSame(BlockOrderApprovalService::STATUS_APPROVED, $order->fresh()->status_id);
        $this->assertSame(0, $personnel->vacations()->count());
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
