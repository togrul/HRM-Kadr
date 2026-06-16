<?php

namespace Tests\Feature\Orders;

use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Modules\Orders\Livewire\OrderComposer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Phase 5 — the issue-time composer Livewire component wiring the render engine:
 * authorization, live preview with declension, and the finalized .docx download.
 */
class OrderComposerTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_without_add_orders_permission_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(OrderComposer::class, ['presetCode' => 'leave'])
            ->assertForbidden();
    }

    public function test_it_previews_a_filled_order_and_downloads_the_docx(): void
    {
        $personnel = $this->makePersonnel();
        $this->actingAs($this->userWith('add-orders'));

        $component = Livewire::test(OrderComposer::class, [
            'presetCode' => 'leave',
            'personnelId' => $personnel->id,
        ])
            ->set('orderNumber', '214-M')
            ->set('orderDate', '14 may 2026-cı il')
            ->set('fields', [
                'work_year' => '26.11.2025-26.11.2026-cı il',
                'days' => '14',
                'start_date' => '19.05.2026-cı il',
                'end_date' => '03.06.2026-cı il',
                'return_date' => '04.06.2026-cı il',
            ])
            ->call('generatePreview');

        $html = $component->get('previewHtml');
        $this->assertStringContainsString('Bayramov Ruslan Bəxtiyar oğluna', $html);
        $this->assertStringContainsString('Keşlə Qeyri-Qida Satış mərkəzinin', $html);
        $this->assertStringNotContainsString('___', $html);

        $component->call('download')->assertFileDownloaded('leave_214-M.docx');
    }

    public function test_unknown_preset_surfaces_an_error(): void
    {
        $this->actingAs($this->userWith('add-orders'));

        Livewire::test(OrderComposer::class, ['presetCode' => 'does-not-exist'])
            ->call('generatePreview')
            ->assertHasErrors('presetCode');
    }

    private function userWith(string $permission): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));

        return $user;
    }

    private function makePersonnel(): Personnel
    {
        $structure = Structure::query()->create([
            'name' => 'Keşlə Qeyri-Qida Satış mərkəzi',
            'shortname' => 'Keşlə QQS',
        ]);
        $position = Position::query()->create(['name' => 'satınalma operatoru']);

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
            'structure_id' => $structure->id,
            'position_id' => $position->id,
            'join_work_date' => '2026-03-01',
            'added_by' => 1,
            'is_pending' => false,
        ]));
    }
}
