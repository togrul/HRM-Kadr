<?php

namespace Tests\Feature\Orders;

use App\Models\OrderLog;
use App\Models\OrderWordTemplate;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Modules\Orders\Livewire\OrderComposer;
use App\Services\Orders\Document\OrderIssueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\TemplateProcessor;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use ZipArchive;

/**
 * The issue-time composer on the Word engine: it fills an uploaded .docx template's
 * ${tokens} with resolved employee/system data + the author's manual fields and stores
 * the final document.
 */
class OrderComposerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_users_without_add_orders_permission_are_forbidden(): void
    {
        $this->seedTemplate();
        $this->actingAs(User::factory()->create());

        Livewire::test(OrderComposer::class, ['presetCode' => 'leave'])
            ->assertForbidden();
    }

    public function test_it_issues_a_filled_docx_order_and_downloads_it(): void
    {
        $this->seedTemplate();
        $personnel = $this->makePersonnel();
        $this->actingAs($this->userWith('add-orders'));

        Livewire::test(OrderComposer::class, [
            'presetCode' => 'leave',
            'personnelId' => $personnel->id,
        ])
            ->set('orderNumber', '214-M')
            ->set('orderDate', '14 may 2026-cı il')
            ->set('fields', ['var_2' => '19.05.2026-cı il'])
            ->call('issue')
            ->assertFileDownloaded('leave_214-M.docx');

        $order = OrderLog::where('order_no', '214-M')->first();
        $this->assertNotNull($order);
        $this->assertSame(OrderIssueService::RENDER_MODE_DOCX, $order->template_render_mode);
        $this->assertSame($personnel->id, data_get($order->template_snapshot, 'personnel_id'));

        // The stored .docx carries the resolved (declension-aware) employee name + manual date.
        $stored = data_get($order->template_snapshot, 'docx_path');
        Storage::disk('local')->assertExists($stored);
        $text = $this->documentText(Storage::disk('local')->path($stored));
        $this->assertStringContainsString('Bayramov Ruslan Bəxtiyar oğluna', $text);
        $this->assertStringContainsString('19.05.2026-cı il', $text);
        $this->assertStringNotContainsString('${', $text);
    }

    public function test_issuing_an_order_whose_number_contains_a_slash_downloads_a_safe_filename(): void
    {
        $this->seedTemplate();
        $personnel = $this->makePersonnel();
        $this->actingAs($this->userWith('add-orders'));

        Livewire::test(OrderComposer::class, [
            'presetCode' => 'leave',
            'personnelId' => $personnel->id,
        ])
            ->set('orderNumber', '2026/ƏM-145')
            ->set('fields', ['var_2' => '19.05.2026'])
            ->call('issue')
            ->assertFileDownloaded('leave_2026-ƏM-145.docx');
    }

    public function test_it_opens_a_pending_order_for_editing_and_saves_in_place(): void
    {
        $this->seedTemplate();
        $personnel = $this->makePersonnel();
        $this->actingAs($this->userWith('add-orders'));

        Livewire::test(OrderComposer::class, [
            'presetCode' => 'leave',
            'personnelId' => $personnel->id,
        ])
            ->set('orderNumber', '500-M')
            ->set('fields', ['var_2' => '19.05.2026'])
            ->call('issue');

        $order = OrderLog::where('order_no', '500-M')->firstOrFail();

        Livewire::test(OrderComposer::class, ['orderId' => $order->id])
            ->assertSet('presetCode', 'leave')
            ->assertSet('orderNumber', '500-M')
            ->assertSet('personnelId', $personnel->id)
            ->set('orderNumber', '500-M-DÜZ')
            ->call('issue')
            ->assertDispatched('orderAdded');

        $order->refresh();
        $this->assertSame('500-M-DÜZ', $order->order_no);
        $this->assertSame(OrderIssueService::STATUS_PENDING, $order->status_id);
        $this->assertSame(1, OrderLog::where('template_render_mode', OrderIssueService::RENDER_MODE_DOCX)->count());
    }

    public function test_it_replaces_the_generated_document_with_an_uploaded_word_file(): void
    {
        $this->seedTemplate();
        $personnel = $this->makePersonnel();
        $this->actingAs($this->userWith('add-orders'));

        Livewire::test(OrderComposer::class, [
            'presetCode' => 'leave',
            'personnelId' => $personnel->id,
        ])
            ->set('orderNumber', '600-M')
            ->set('fields', ['var_2' => '19.05.2026'])
            ->call('issue');

        $orderLog = OrderLog::where('order_no', '600-M')->firstOrFail();

        $file = \Illuminate\Http\UploadedFile::fake()->create('corrected.docx', 50, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        Livewire::test(OrderComposer::class, ['orderId' => $orderLog->id])
            ->set('uploadedDocx', $file)
            ->call('uploadDocx')
            ->assertDispatched('orderAdded')
            ->assertSet('hasUploadedDocx', true);

        Storage::disk('local')->assertExists(data_get($orderLog->fresh()->template_snapshot, 'docx_path'));
    }

    public function test_selecting_a_type_renders_only_its_manual_fields(): void
    {
        $this->seedTemplate();
        $this->actingAs($this->userWith('add-orders'));

        Livewire::test(OrderComposer::class)
            ->set('presetCode', 'leave')
            ->assertSee('Başlama tarixi');   // the manual variable's label
    }

    public function test_issue_requires_an_employee_when_the_template_uses_employee_data(): void
    {
        $this->seedTemplate();
        $this->actingAs($this->userWith('add-orders'));

        Livewire::test(OrderComposer::class)
            ->set('presetCode', 'leave')
            ->set('orderNumber', '900-M')
            ->call('issue')
            ->assertHasErrors('personnelId');
    }

    public function test_a_list_bound_manual_field_writes_the_chosen_records_name(): void
    {
        $this->makeMaster('order-templates/transfer.docx', 'İşçi ${var_1} struktura keçirilsin.');
        $structure = Structure::query()->create(['name' => 'Mərkəzi Anbar', 'shortname' => 'MA']);

        OrderWordTemplate::create([
            'code' => 'transfer',
            'label' => 'Köçürmə',
            'docx_path' => 'order-templates/transfer.docx',
            'variables' => [
                ['token' => 'var_1', 'label' => 'Struktur', 'source' => 'manual', 'auto_key' => null, 'field' => ['key' => 'var_1', 'type' => 'structure']],
            ],
            'is_active' => true,
        ]);

        $this->actingAs($this->userWith('add-orders'));

        $component = Livewire::test(OrderComposer::class, ['presetCode' => 'transfer'])
            ->set('orderNumber', '800-K')
            // The list-bound field submits the record id, not free text.
            ->set('fields', ['var_1' => (string) $structure->id]);

        // The structure is offered as a picker option (id + name).
        $this->assertContains(
            ['id' => $structure->id, 'label' => 'Mərkəzi Anbar', 'depth' => 0],
            $component->get('lookupOptions')['structure']
        );

        $component->call('issue');

        $order = OrderLog::where('order_no', '800-K')->firstOrFail();
        $text = $this->documentText(Storage::disk('local')->path(data_get($order->template_snapshot, 'docx_path')));
        // The chosen record's NAME (not its id) lands in the document.
        $this->assertStringContainsString('Mərkəzi Anbar', $text);
        $this->assertStringNotContainsString('${var_1}', $text);
    }

    public function test_a_docx_order_can_be_approved(): void
    {
        $this->seedTemplate();
        $personnel = $this->makePersonnel();
        $this->actingAs($this->userWith('add-orders'));

        Livewire::test(OrderComposer::class, ['presetCode' => 'leave', 'personnelId' => $personnel->id])
            ->set('orderNumber', '700-IQ')
            ->set('fields', ['var_2' => '19.05.2026'])
            ->call('issue');

        $order = OrderLog::where('order_no', '700-IQ')->firstOrFail();
        $this->assertSame(OrderIssueService::STATUS_PENDING, $order->status_id);

        app(\App\Services\Orders\Document\OrderApprovalService::class)->approve($order);

        $this->assertSame(
            \App\Services\Orders\Document\OrderApprovalService::STATUS_APPROVED,
            $order->fresh()->status_id
        );
    }

    public function test_approving_a_vacation_order_puts_the_employee_on_leave(): void
    {
        $this->makeMaster('order-templates/vac.docx', '${var_1} üçün ${var_2}–${var_3} məzuniyyət.');
        OrderWordTemplate::create([
            'code' => 'vac',
            'label' => 'Məzuniyyət',
            'effect' => 'vacation',
            'docx_path' => 'order-templates/vac.docx',
            'variables' => [
                ['token' => 'var_1', 'label' => 'Tam ad', 'source' => 'auto', 'auto_key' => 'employee.full_name', 'field' => null, 'effect_role' => null],
                ['token' => 'var_2', 'label' => 'Başlama', 'source' => 'manual', 'auto_key' => null, 'field' => ['key' => 'var_2', 'type' => 'date'], 'effect_role' => 'start_date'],
                ['token' => 'var_3', 'label' => 'Bitmə', 'source' => 'manual', 'auto_key' => null, 'field' => ['key' => 'var_3', 'type' => 'date'], 'effect_role' => 'end_date'],
                ['token' => 'var_4', 'label' => 'Gün', 'source' => 'manual', 'auto_key' => null, 'field' => ['key' => 'var_4', 'type' => 'number'], 'effect_role' => 'days'],
            ],
            'is_active' => true,
        ]);

        $personnel = $this->makePersonnel();
        $this->actingAs($this->userWith('add-orders'));

        Livewire::test(OrderComposer::class, ['presetCode' => 'vac', 'personnelId' => $personnel->id])
            ->set('orderNumber', '901-M')
            ->set('fields', ['var_2' => '19.05.2026-cı il', 'var_3' => '03.06.2026-cı il', 'var_4' => '14'])
            ->call('issue');

        $order = OrderLog::where('order_no', '901-M')->firstOrFail();

        // No leave yet while pending; approval creates the record.
        $this->assertCount(0, $personnel->vacations()->get());
        app(\App\Services\Orders\Document\OrderApprovalService::class)->approve($order);

        $vacation = $personnel->vacations()->first();
        $this->assertNotNull($vacation);
        $this->assertSame('2026-05-19', \Illuminate\Support\Carbon::parse($vacation->start_date)->format('Y-m-d'));
        $this->assertSame('2026-06-03', \Illuminate\Support\Carbon::parse($vacation->end_date)->format('Y-m-d'));
        $this->assertSame(14, (int) $vacation->duration);
        $this->assertSame('901-M', $vacation->order_no);
    }

    public function test_approving_a_hire_order_converts_the_candidate_to_an_employee(): void
    {
        // The PersonnelObserver notifies admins on creation — the role/permission it
        // queries must exist (no admin users, so nothing is actually sent).
        \Spatie\Permission\Models\Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        // Reference data the candidate→personnel conversion requires.
        \Illuminate\Support\Facades\DB::table('countries')->insert(['code' => 'AZ']);
        \Illuminate\Support\Facades\DB::table('education_degrees')->insert(['title_az' => 'Ali', 'title_en' => 'Higher', 'title_ru' => '-']);
        \Illuminate\Support\Facades\DB::table('work_norms')->insert(['name_az' => 'Tam', 'name_en' => 'Full', 'name_ru' => '-']);

        // Company root (id=1) so the hire structure is a sub-structure, untouched by the
        // staff-schedule "main structure" auto-recompute.
        Structure::query()->firstOrCreate(['shortname' => 'ROOT'], ['name' => 'Şirkət']);
        $structure = Structure::query()->create(['name' => 'Mərkəzi anbar', 'shortname' => 'MA']);
        $position = Position::query()->create(['name' => 'sürücü']);
        $candidate = \App\Models\Candidate::query()->create([
            'surname' => 'Hüseynov', 'name' => 'Elçin', 'patronymic' => 'Vüqar', 'height' => 178,
            'structure_id' => $structure->id, 'status_id' => 30, 'gender' => 1, 'birthdate' => '1995-01-01',
        ]);

        $this->makeMaster('order-templates/hire.docx', '${var_1} ${var_2} tarixindən ${var_3} işə qəbul edilsin.');
        OrderWordTemplate::create([
            'code' => 'hire',
            'label' => 'İşə qəbul',
            'effect' => 'hire',
            'docx_path' => 'order-templates/hire.docx',
            'variables' => [
                ['token' => 'var_1', 'label' => 'Tam ad', 'source' => 'auto', 'auto_key' => 'employee.full_name', 'field' => null, 'effect_role' => null],
                ['token' => 'var_2', 'label' => 'Tarix', 'source' => 'manual', 'auto_key' => null, 'field' => ['key' => 'var_2', 'type' => 'date'], 'effect_role' => 'start_date'],
                ['token' => 'var_3', 'label' => 'Struktur', 'source' => 'auto', 'auto_key' => 'employee.structure_dative', 'field' => null, 'effect_role' => null],
            ],
            'is_active' => true,
        ]);

        // A free staff-schedule slot for the target structure+position lets the hire pass.
        \App\Models\StaffSchedule::query()->create([
            'structure_id' => $structure->id, 'position_id' => $position->id,
            'total' => 1, 'filled' => 0, 'vacant' => 1,
        ]);

        $this->actingAs($this->userWith('add-orders'));

        Livewire::test(OrderComposer::class, ['presetCode' => 'hire'])
            ->set('orderNumber', '135-K')
            ->call('selectCandidate', $candidate->id)
            ->set('hirePositionId', $position->id)
            ->set('fields', ['var_2' => '09.06.2026-cı il'])
            ->call('issue');

        $order = OrderLog::where('order_no', '135-K')->firstOrFail();
        $this->assertSame($candidate->id, data_get($order->template_snapshot, 'candidate_id'));
        $this->assertSame(0, Personnel::query()->where('surname', 'Hüseynov')->count());

        app(\App\Services\Orders\Document\OrderApprovalService::class)->approve($order);

        // The candidate is now an active employee in the chosen structure + position.
        $personnel = Personnel::query()->where('surname', 'Hüseynov')->first();
        $this->assertNotNull($personnel);
        $this->assertFalse((bool) $personnel->is_pending);
        $this->assertSame($structure->id, (int) $personnel->structure_id);
        $this->assertSame($position->id, (int) $personnel->position_id);
        $this->assertNotEmpty($personnel->tabel_no);

        // Approving the hire consumed the slot: filled +1, vacant back to 0.
        $schedule = \App\Models\StaffSchedule::query()
            ->where('structure_id', $structure->id)->where('position_id', $position->id)->first();
        $this->assertSame(1, (int) $schedule->filled);
        $this->assertSame(0, (int) $schedule->vacant);

        // The candidate moves off the "Əmrə hazır" (30) list to "Qəbul olundu" (70).
        $this->assertSame(70, (int) $candidate->fresh()->status_id);
    }

    public function test_hire_without_vacancy_prompts_then_auto_creates_a_slot(): void
    {
        [$candidate, $structure, $position] = $this->seedHireScaffold('hire2');
        $this->actingAs($this->userWith('add-orders'));

        // No staff schedule exists → issuing prompts instead of creating the order.
        $component = Livewire::test(OrderComposer::class, ['presetCode' => 'hire2'])
            ->set('orderNumber', '200-K')
            ->call('selectCandidate', $candidate->id)
            ->set('hirePositionId', $position->id)
            ->set('fields', ['var_2' => '09.06.2026-cı il'])
            ->call('issue');

        $component->assertDispatched('order-vacancy-missing');
        $this->assertNull(OrderLog::where('order_no', '200-K')->first(), 'No order should be created while gated.');

        // Confirming the prompt creates the slot and issues the order in one shot.
        $component->call('createVacancyAndIssue');

        $this->assertNotNull(OrderLog::where('order_no', '200-K')->first());
        $schedule = \App\Models\StaffSchedule::query()
            ->where('structure_id', $structure->id)->where('position_id', $position->id)->first();
        $this->assertNotNull($schedule);
        $this->assertSame(1, (int) $schedule->vacant);
    }

    public function test_a_fully_filled_schedule_expands_by_one_slot(): void
    {
        [$candidate, $structure, $position] = $this->seedHireScaffold('hire3');

        // total 4 / filled 4 / vacant 0 — no room.
        \App\Models\StaffSchedule::query()->create([
            'structure_id' => $structure->id, 'position_id' => $position->id,
            'total' => 4, 'filled' => 4, 'vacant' => 0,
        ]);

        $this->actingAs($this->userWith('add-orders'));

        Livewire::test(OrderComposer::class, ['presetCode' => 'hire3'])
            ->set('orderNumber', '201-K')
            ->call('selectCandidate', $candidate->id)
            ->set('hirePositionId', $position->id)
            ->set('fields', ['var_2' => '09.06.2026-cı il'])
            ->call('createVacancyAndIssue');

        $schedule = \App\Models\StaffSchedule::query()
            ->where('structure_id', $structure->id)->where('position_id', $position->id)->first();
        // Expanded to total 5 / filled 4 / vacant 1 so the order can add the employee.
        $this->assertSame(5, (int) $schedule->total);
        $this->assertSame(4, (int) $schedule->filled);
        $this->assertSame(1, (int) $schedule->vacant);
        $this->assertNotNull(OrderLog::where('order_no', '201-K')->first());
    }

    public function test_vacation_balance_is_enforced_consumed_and_restored(): void
    {
        $year = (int) now()->year;
        $this->makeMaster('order-templates/vacbal.docx', '${var_1} ${var_2}–${var_3} ${var_4}.');
        OrderWordTemplate::updateOrCreate(['code' => 'vacbal'], [
            'label' => 'Məzuniyyət', 'effect' => 'vacation', 'docx_path' => 'order-templates/vacbal.docx',
            'variables' => [
                ['token' => 'var_1', 'label' => 'Ad', 'source' => 'auto', 'auto_key' => 'employee.full_name', 'field' => null, 'effect_role' => null],
                ['token' => 'var_2', 'label' => 'Başlama', 'source' => 'manual', 'auto_key' => null, 'field' => ['key' => 'var_2', 'type' => 'date'], 'effect_role' => 'start_date'],
                ['token' => 'var_3', 'label' => 'Bitmə', 'source' => 'manual', 'auto_key' => null, 'field' => ['key' => 'var_3', 'type' => 'date'], 'effect_role' => 'end_date'],
                ['token' => 'var_4', 'label' => 'Gün', 'source' => 'manual', 'auto_key' => null, 'field' => ['key' => 'var_4', 'type' => 'number'], 'effect_role' => 'days'],
            ],
            'is_active' => true,
        ]);

        $personnel = $this->makePersonnel();
        // Mostly-used balance: 30 total, only 5 left for the year.
        \App\Models\Vacation::query()->create([
            'tabel_no' => $personnel->tabel_no, 'year' => $year,
            'vacation_days_total' => 30, 'remaining_days' => 5, 'reserved_date_month' => null,
        ]);

        $this->actingAs($this->userWith('add-orders'));
        $start = '19.05.'.$year.'-cı il';
        $end = '23.05.'.$year.'-cı il';

        // 0 days → blocked (a vacation must be at least 1 day).
        Livewire::test(OrderComposer::class, ['presetCode' => 'vacbal', 'personnelId' => $personnel->id])
            ->set('orderNumber', '929-M')
            ->set('fields', ['var_2' => $start, 'var_3' => $end, 'var_4' => '0'])
            ->call('issue')
            ->assertDispatched('orderError');
        $this->assertNull(OrderLog::where('order_no', '929-M')->first());

        // 14 days requested but only 5 remain → blocked with a notification, no order.
        $component = Livewire::test(OrderComposer::class, ['presetCode' => 'vacbal', 'personnelId' => $personnel->id])
            ->set('orderNumber', '930-M')
            ->set('fields', ['var_2' => $start, 'var_3' => $end, 'var_4' => '14'])
            ->call('issue')
            ->assertDispatched('orderError');
        $this->assertNull(OrderLog::where('order_no', '930-M')->first());

        // Exactly the remaining 5 days → allowed.
        $component->set('fields', ['var_2' => $start, 'var_3' => $end, 'var_4' => '5'])->call('issue');
        $order = OrderLog::where('order_no', '930-M')->firstOrFail();

        $transitions = app(\App\Services\Orders\Document\OrderStatusTransitionService::class);

        // Approval deducts the days from the balance…
        $transitions->approve($order);
        $this->assertSame(0, (int) \App\Models\Vacation::query()
            ->where('tabel_no', $personnel->tabel_no)->where('year', $year)->value('remaining_days'));

        // …and cancelling restores them.
        $transitions->cancel($order);
        $this->assertSame(5, (int) \App\Models\Vacation::query()
            ->where('tabel_no', $personnel->tabel_no)->where('year', $year)->value('remaining_days'));
    }

    public function test_an_order_with_an_empty_required_field_is_blocked(): void
    {
        // Template with start/end dates + a day count; filling only the day count
        // (dates left empty) must be blocked — an order may not have blank slots.
        $this->makeMaster('order-templates/vacreq.docx', '${var_1} ${var_2}–${var_3} ${var_4}.');
        OrderWordTemplate::updateOrCreate(['code' => 'vacreq'], [
            'label' => 'Məzuniyyət', 'effect' => 'vacation', 'docx_path' => 'order-templates/vacreq.docx',
            'variables' => [
                ['token' => 'var_1', 'label' => 'Ad', 'source' => 'auto', 'auto_key' => 'employee.full_name', 'field' => null, 'effect_role' => null],
                ['token' => 'var_2', 'label' => 'Başlama', 'source' => 'manual', 'auto_key' => null, 'field' => ['key' => 'var_2', 'type' => 'date'], 'effect_role' => 'start_date'],
                ['token' => 'var_3', 'label' => 'Bitmə', 'source' => 'manual', 'auto_key' => null, 'field' => ['key' => 'var_3', 'type' => 'date'], 'effect_role' => 'end_date'],
                ['token' => 'var_4', 'label' => 'Gün', 'source' => 'manual', 'auto_key' => null, 'field' => ['key' => 'var_4', 'type' => 'number'], 'effect_role' => 'days'],
            ],
            'is_active' => true,
        ]);
        $personnel = $this->makePersonnel();
        $this->actingAs($this->userWith('add-orders'));

        Livewire::test(OrderComposer::class, ['presetCode' => 'vacreq', 'personnelId' => $personnel->id])
            ->set('orderNumber', '970-M')
            ->set('fields', ['var_4' => '1']) // only days; start/end dates empty
            ->call('issue')
            ->assertDispatched('orderError')
            ->assertHasErrors(['fields.var_2', 'fields.var_3']); // per-input validation errors
        $this->assertNull(OrderLog::where('order_no', '970-M')->first());
    }

    public function test_a_vacation_with_no_days_selected_is_blocked(): void
    {
        $this->makeMaster('order-templates/vacnone.docx', '${var_1} ${var_4}.');
        OrderWordTemplate::updateOrCreate(['code' => 'vacnone'], [
            'label' => 'Məzuniyyət', 'effect' => 'vacation', 'docx_path' => 'order-templates/vacnone.docx',
            'variables' => [
                ['token' => 'var_1', 'label' => 'Ad', 'source' => 'auto', 'auto_key' => 'employee.full_name', 'field' => null, 'effect_role' => null],
                ['token' => 'var_4', 'label' => 'Gün', 'source' => 'manual', 'auto_key' => null, 'field' => ['key' => 'var_4', 'type' => 'number'], 'effect_role' => 'days'],
            ],
            'is_active' => true,
        ]);
        $personnel = $this->makePersonnel();
        $this->actingAs($this->userWith('add-orders'));

        Livewire::test(OrderComposer::class, ['presetCode' => 'vacnone', 'personnelId' => $personnel->id])
            ->set('orderNumber', '950-M')
            ->call('issue')
            ->assertDispatched('orderError');
        $this->assertNull(OrderLog::where('order_no', '950-M')->first());
    }

    public function test_editing_a_vacation_to_clear_days_is_blocked(): void
    {
        [$order, $personnel] = $this->issueVacationOrder('960-M'); // valid: 14 days
        $this->actingAs($this->userWith('add-orders'));

        // Re-open in edit mode, clear the day count, save → must be blocked.
        Livewire::test(OrderComposer::class, ['orderId' => $order->id])
            ->set('fields.var_4', '')
            ->call('issue')
            ->assertDispatched('orderError');

        // The order keeps its original (valid) snapshot, not the cleared one.
        $this->assertSame('14', (string) data_get($order->fresh()->template_snapshot, 'fields.var_4'));
    }

    public function test_a_vacation_without_a_day_count_is_not_gated(): void
    {
        // Unpaid-style leave: effect=vacation but no 'days' role (defined by a date range).
        $this->makeMaster('order-templates/unpaid.docx', '${var_1} ${var_2}.');
        OrderWordTemplate::updateOrCreate(['code' => 'unpaid'], [
            'label' => 'Ödənişsiz', 'effect' => 'vacation', 'docx_path' => 'order-templates/unpaid.docx',
            'variables' => [
                ['token' => 'var_1', 'label' => 'Ad', 'source' => 'auto', 'auto_key' => 'employee.full_name', 'field' => null, 'effect_role' => null],
                ['token' => 'var_2', 'label' => 'Başlama', 'source' => 'manual', 'auto_key' => null, 'field' => ['key' => 'var_2', 'type' => 'date'], 'effect_role' => 'start_date'],
            ],
            'is_active' => true,
        ]);

        $personnel = $this->makePersonnel();
        $this->actingAs($this->userWith('add-orders'));

        Livewire::test(OrderComposer::class, ['presetCode' => 'unpaid', 'personnelId' => $personnel->id])
            ->set('orderNumber', '940-M')
            ->set('fields', ['var_2' => '01.05.'.now()->year.'-cı il'])
            ->call('issue');

        // Not blocked by the day-count gate — the order is created.
        $this->assertNotNull(OrderLog::where('order_no', '940-M')->first());
    }

    /**
     * Minimal hire scaffold: a "ready" candidate, structure, position and a hire
     * template under $code. Returns [candidate, structure, position].
     *
     * @return array{0:\App\Models\Candidate,1:Structure,2:Position}
     */
    private function seedHireScaffold(string $code): array
    {
        \Spatie\Permission\Models\Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        // Reserve id=1 for a company root so the hire structure is a sub-structure (the
        // staff-schedule "main structure" magic only manages structure_id=1).
        Structure::query()->firstOrCreate(['shortname' => 'ROOT'], ['name' => 'Şirkət']);

        $structure = Structure::query()->create(['name' => 'Anbar '.$code, 'shortname' => 'A'.$code]);
        $position = Position::query()->create(['name' => 'operator '.$code]);
        $candidate = \App\Models\Candidate::query()->create([
            'surname' => 'Test'.$code, 'name' => 'Nümunə', 'patronymic' => 'X', 'height' => 175,
            'structure_id' => $structure->id, 'status_id' => 30, 'gender' => 1, 'birthdate' => '1995-01-01',
        ]);

        $this->makeMaster('order-templates/'.$code.'.docx', '${var_1} ${var_2} işə qəbul edilsin.');
        OrderWordTemplate::create([
            'code' => $code,
            'label' => 'İşə qəbul '.$code,
            'effect' => 'hire',
            'docx_path' => 'order-templates/'.$code.'.docx',
            'variables' => [
                ['token' => 'var_1', 'label' => 'Tam ad', 'source' => 'auto', 'auto_key' => 'employee.full_name', 'field' => null, 'effect_role' => null],
                ['token' => 'var_2', 'label' => 'Tarix', 'source' => 'manual', 'auto_key' => null, 'field' => ['key' => 'var_2', 'type' => 'date'], 'effect_role' => 'start_date'],
            ],
            'is_active' => true,
        ]);

        return [$candidate, $structure, $position];
    }

    public function test_unknown_type_surfaces_an_error(): void
    {
        $this->actingAs($this->userWith('add-orders'));

        Livewire::test(OrderComposer::class, ['presetCode' => 'does-not-exist'])
            ->call('issue')
            ->assertHasErrors('presetCode');
    }

    public function test_cancelling_a_pending_order_marks_it_cancelled_without_effects(): void
    {
        [$order, $personnel] = $this->issueVacationOrder('801-M');

        app(\App\Services\Orders\Document\OrderStatusTransitionService::class)->cancel($order);

        $this->assertSame(\App\Enums\OrderStatusEnum::CANCELLED->value, (int) $order->fresh()->status_id);
        $this->assertCount(0, $personnel->vacations()->get());
    }

    public function test_reverting_an_approved_vacation_order_removes_the_leave(): void
    {
        [$order, $personnel] = $this->issueVacationOrder('802-M');
        $transitions = app(\App\Services\Orders\Document\OrderStatusTransitionService::class);

        $transitions->approve($order);
        $this->assertCount(1, $personnel->vacations()->get());

        $transitions->revert($order);

        $this->assertSame(\App\Enums\OrderStatusEnum::PENDING->value, (int) $order->fresh()->status_id);
        $this->assertCount(0, $personnel->vacations()->get());
    }

    public function test_cancelling_an_approved_order_reverses_its_effect(): void
    {
        [$order, $personnel] = $this->issueVacationOrder('803-M');
        $transitions = app(\App\Services\Orders\Document\OrderStatusTransitionService::class);

        $transitions->approve($order);
        $transitions->cancel($order);

        $this->assertSame(\App\Enums\OrderStatusEnum::CANCELLED->value, (int) $order->fresh()->status_id);
        $this->assertCount(0, $personnel->vacations()->get());
    }

    public function test_reopening_a_cancelled_order_returns_it_to_pending(): void
    {
        [$order] = $this->issueVacationOrder('804-M');
        $transitions = app(\App\Services\Orders\Document\OrderStatusTransitionService::class);

        $transitions->cancel($order);
        $transitions->reopen($order);

        $this->assertSame(\App\Enums\OrderStatusEnum::PENDING->value, (int) $order->fresh()->status_id);
    }

    public function test_an_illegal_status_jump_is_rejected(): void
    {
        [$order] = $this->issueVacationOrder('805-M');
        $transitions = app(\App\Services\Orders\Document\OrderStatusTransitionService::class);
        $transitions->cancel($order);

        // cancelled → approved is not a permitted move.
        $this->expectException(\DomainException::class);
        $transitions->transition($order->fresh(), \App\Enums\OrderStatusEnum::APPROVED);
    }

    /**
     * Issue a pending vacation order for a fresh employee and return [order, personnel].
     *
     * @return array{0:OrderLog,1:Personnel}
     */
    private function issueVacationOrder(string $number): array
    {
        $this->makeMaster('order-templates/vac.docx', '${var_1} üçün ${var_2}–${var_3} məzuniyyət.');
        OrderWordTemplate::updateOrCreate(['code' => 'vac'], [
            'label' => 'Məzuniyyət',
            'effect' => 'vacation',
            'docx_path' => 'order-templates/vac.docx',
            'variables' => [
                ['token' => 'var_1', 'label' => 'Tam ad', 'source' => 'auto', 'auto_key' => 'employee.full_name', 'field' => null, 'effect_role' => null],
                ['token' => 'var_2', 'label' => 'Başlama', 'source' => 'manual', 'auto_key' => null, 'field' => ['key' => 'var_2', 'type' => 'date'], 'effect_role' => 'start_date'],
                ['token' => 'var_3', 'label' => 'Bitmə', 'source' => 'manual', 'auto_key' => null, 'field' => ['key' => 'var_3', 'type' => 'date'], 'effect_role' => 'end_date'],
                ['token' => 'var_4', 'label' => 'Gün', 'source' => 'manual', 'auto_key' => null, 'field' => ['key' => 'var_4', 'type' => 'number'], 'effect_role' => 'days'],
            ],
            'is_active' => true,
        ]);

        $personnel = $this->makePersonnel();
        $this->actingAs($this->userWith('add-orders'));

        Livewire::test(OrderComposer::class, ['presetCode' => 'vac', 'personnelId' => $personnel->id])
            ->set('orderNumber', $number)
            ->set('fields', ['var_2' => '19.05.2026-cı il', 'var_3' => '03.06.2026-cı il', 'var_4' => '14'])
            ->call('issue');

        return [OrderLog::where('order_no', $number)->firstOrFail(), $personnel];
    }

    /**
     * Seed a "leave" Word template: one auto variable (employee full name, dative) and
     * one manual variable (start date), backed by a real ${token} master on the disk.
     */
    private function seedTemplate(): void
    {
        $relative = 'order-templates/leave.docx';
        $this->makeMaster($relative, 'Əmr: ${var_1} işçisinə ${var_2} tarixindən məzuniyyət verilsin.');

        OrderWordTemplate::create([
            'code' => 'leave',
            'label' => 'Məzuniyyət',
            'docx_path' => $relative,
            'variables' => [
                ['token' => 'var_1', 'label' => 'Tam ad', 'source' => 'auto', 'auto_key' => 'employee.full_name_dative', 'field' => null],
                ['token' => 'var_2', 'label' => 'Başlama tarixi', 'source' => 'manual', 'auto_key' => null, 'field' => ['key' => 'var_2', 'type' => 'text']],
            ],
            'is_active' => true,
        ]);
    }

    private function makeMaster(string $relative, string $text): void
    {
        $phpWord = new PhpWord;
        $phpWord->addSection()->addText($text);
        $tmp = tempnam(sys_get_temp_dir(), 'mst_').'.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($tmp);
        Storage::disk('local')->put($relative, (string) file_get_contents($tmp));
        @unlink($tmp);
    }

    private function documentText(string $docxPath): string
    {
        $zip = new ZipArchive;
        $zip->open($docxPath);
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();

        return html_entity_decode(strip_tags(str_replace('<', ' <', $xml)), ENT_QUOTES | ENT_XML1, 'UTF-8');
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
            'join_work_date' => '2020-01-01',
            'added_by' => 1,
            'is_pending' => false,
        ]));
    }
}
