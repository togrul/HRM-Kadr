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
                'work_year' => '2025-11-26', // a start date — the engine derives the span
                'days' => '14',
                'start_date' => '19.05.2026-cı il',
                'end_date' => '03.06.2026-cı il',
                'return_date' => '04.06.2026-cı il',
            ])
            ->call('generatePreview');

        $html = $component->get('previewHtml');
        $this->assertStringContainsString('Bayramov Ruslan Bəxtiyar oğluna', $html);
        $this->assertStringContainsString('Keşlə Qeyri-Qida Satış mərkəzinin', $html);
        // Labour year ends the day BEFORE the anniversary, not the same day.
        $this->assertStringContainsString('26.11.2025-25.11.2026-cı il', $html);
        $this->assertStringNotContainsString('26.11.2025-26.11.2026', $html);
        $this->assertStringNotContainsString('___', $html);

        // Issuing creates the order in the shared list AND downloads the .docx.
        $component->call('issue')->assertFileDownloaded('leave_214-M.docx');

        $order = \App\Models\OrderLog::where('order_no', '214-M')->first();
        $this->assertNotNull($order);
        $this->assertSame('block_v2', $order->template_render_mode);
        $this->assertStringContainsString('Bayramov Ruslan Bəxtiyar oğluna', data_get($order->template_snapshot, 'html'));
        $this->assertSame($personnel->id, data_get($order->template_snapshot, 'personnel_id'));
    }

    public function test_issuing_an_order_whose_number_contains_a_slash_downloads_a_safe_filename(): void
    {
        $personnel = $this->makePersonnel();
        $this->actingAs($this->userWith('add-orders'));

        // "/" is illegal in a download filename — it must be folded to "-".
        Livewire::test(OrderComposer::class, [
            'presetCode' => 'leave',
            'personnelId' => $personnel->id,
        ])
            ->set('orderNumber', '2026/ƏM-145')
            ->set('fields', ['days' => '14', 'work_year' => '2025-11-26'])
            ->call('generatePreview')
            ->call('issue')
            ->assertFileDownloaded('leave_2026-ƏM-145.docx');
    }

    public function test_it_opens_a_pending_block_order_for_editing_and_saves_in_place(): void
    {
        $personnel = $this->makePersonnel();
        $this->actingAs($this->userWith('add-orders'));

        // First issue an order through the composer.
        $created = Livewire::test(OrderComposer::class, [
            'presetCode' => 'leave',
            'personnelId' => $personnel->id,
        ])
            ->set('orderNumber', '500-M')
            ->set('orderDate', '14 may 2026-cı il')
            ->set('fields', ['days' => '14', 'work_year' => '2025-11-26'])
            ->call('generatePreview')
            ->call('issue');

        $order = \App\Models\OrderLog::where('order_no', '500-M')->firstOrFail();

        // Re-open it in edit mode: everything is prefilled from the snapshot.
        $editor = Livewire::test(OrderComposer::class, ['orderId' => $order->id])
            ->assertSet('presetCode', 'leave')
            ->assertSet('orderNumber', '500-M')
            ->assertSet('personnelId', $personnel->id);

        $this->assertTrue($editor->instance()->isEditing());
        $this->assertStringContainsString('Bayramov Ruslan Bəxtiyar oğluna', $editor->get('previewHtml'));

        // Correct the text inline and save — same row is re-frozen, then redirect to list.
        $editedHtml = str_replace('14 təqvim günü', '20 təqvim günü', $editor->get('editedHtml'));

        $editor->set('orderNumber', '500-M-DÜZ')
            ->set('editedHtml', $editedHtml)
            ->call('issue')
            ->assertDispatched('orderAdded');

        $order->refresh();
        $this->assertSame('500-M-DÜZ', $order->order_no);
        $this->assertStringContainsString('20 təqvim günü', data_get($order->template_snapshot, 'html'));
        $this->assertSame(\App\Services\Orders\Document\OrderIssueService::STATUS_PENDING, $order->status_id);
        // No duplicate row — editing updates in place.
        $this->assertSame(1, \App\Models\OrderLog::where('template_render_mode', 'block_v2')->count());
    }

    public function test_it_replaces_the_generated_document_with_an_uploaded_word_file(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $personnel = $this->makePersonnel();
        $this->actingAs($this->userWith('add-orders'));

        $order = Livewire::test(OrderComposer::class, [
            'presetCode' => 'leave',
            'personnelId' => $personnel->id,
        ])
            ->set('orderNumber', '600-M')
            ->set('fields', ['days' => '14', 'work_year' => '2025-11-26'])
            ->call('generatePreview')
            ->call('issue');

        $orderLog = \App\Models\OrderLog::where('order_no', '600-M')->firstOrFail();

        $file = \Illuminate\Http\UploadedFile::fake()->create('corrected.docx', 50, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        Livewire::test(OrderComposer::class, ['orderId' => $orderLog->id])
            ->set('uploadedDocx', $file)
            ->call('uploadDocx')
            ->assertDispatched('orderAdded')
            ->assertSet('hasUploadedDocx', true);

        $storedPath = data_get($orderLog->fresh()->template_snapshot, 'docx_path');
        $this->assertNotEmpty($storedPath);
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($storedPath);
    }

    public function test_inline_editing_clears_a_previously_uploaded_word_file(): void
    {
        $personnel = $this->makePersonnel();
        $issuer = app(\App\Services\Orders\Document\OrderIssueService::class);

        $order = $issuer->issue([
            'template_code' => 'leave',
            'personnel_id' => $personnel->id,
            'order_number' => '601-M',
            'snapshot_html' => '<div class="order-document"><p>x</p></div>',
        ]);
        $issuer->attachUploadedDocx($order, 'order-documents/whatever.docx');
        $this->assertNotEmpty(data_get($order->fresh()->template_snapshot, 'docx_path'));

        $issuer->update($order, [
            'order_number' => '601-M',
            'snapshot_html' => '<div class="order-document"><p>y</p></div>',
        ]);

        $this->assertNull(data_get($order->fresh()->template_snapshot, 'docx_path'));
    }

    public function test_selecting_a_preset_renders_its_auto_derived_field_inputs(): void
    {
        $this->actingAs($this->userWith('add-orders'));

        Livewire::test(OrderComposer::class)
            ->set('presetCode', 'leave')
            ->assertSee('Gün sayı')        // field.days
            ->assertSee('Başlama tarixi')  // field.start_date
            ->assertSee('İşə başlama tarixi');
    }

    public function test_employee_picker_searches_and_selects(): void
    {
        $personnel = $this->makePersonnel();
        $this->actingAs($this->userWith('add-orders'));

        Livewire::test(OrderComposer::class)
            ->set('personnelQuery', 'Bayram')
            ->assertSee('Bayramov')
            ->call('selectPersonnel', $personnel->id)
            ->assertSet('personnelId', $personnel->id)
            ->assertSet('personnelLabel', 'Bayramov Ruslan Bəxtiyar')
            ->call('clearPersonnel')
            ->assertSet('personnelId', null);
    }

    public function test_preview_requires_an_employee(): void
    {
        $this->actingAs($this->userWith('add-orders'));

        Livewire::test(OrderComposer::class)
            ->set('presetCode', 'leave')
            ->call('generatePreview')
            ->assertHasErrors('personnelId');
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
