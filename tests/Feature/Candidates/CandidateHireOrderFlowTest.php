<?php

namespace Tests\Feature\Candidates;

use App\Enums\OrderStatusEnum;
use App\Models\Candidate;
use App\Models\CandidateApplication;
use App\Models\JobOpening;
use App\Models\JobRequisition;
use App\Models\OrderLog;
use App\Models\OrderWordTemplate;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\StaffSchedule;
use App\Models\Structure;
use App\Models\User;
use App\Modules\Candidates\Application\Services\CandidateHireOrderService;
use App\Modules\Candidates\Livewire\CandidateList;
use App\Modules\Candidates\Livewire\EditCandidate;
use App\Modules\Orders\Contracts\HireOrderTemplates;
use App\Modules\Orders\Infrastructure\Document\OrderApprovalService;
use App\Modules\Orders\Infrastructure\Document\OrderStatusTransitionService;
use App\Modules\Orders\Livewire\OrderComposer;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Candidate → "İşə qəbul" order → employee: the candidate list prepares the hire order in
 * the Orders composer, and approving it links the candidate to the new employee and order.
 */
class CandidateHireOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    private Structure $structure;

    private Position $position;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        DB::table('countries')->insert(['code' => 'AZ']);
        DB::table('education_degrees')->insert(['title_az' => 'Ali', 'title_en' => 'Higher', 'title_ru' => '-']);
        DB::table('work_norms')->insert(['name_az' => 'Tam', 'name_en' => 'Full', 'name_ru' => '-']);
        foreach ([10 => 'Baxılır', 30 => 'Əmrə hazır', 70 => 'Qəbul olundu', 90 => 'Dayandırıldı'] as $id => $name) {
            DB::table('appeal_statuses')->insert(['id' => $id, 'name' => $name, 'locale' => app()->getLocale()]);
        }

        // Company root (id=1) so the hire structure is a sub-structure.
        Structure::query()->firstOrCreate(['shortname' => 'ROOT'], ['name' => 'Şirkət']);
        $this->structure = Structure::query()->create(['name' => 'Mərkəzi anbar', 'shortname' => 'MA']);
        $this->position = Position::query()->create(['name' => 'sürücü']);

        StaffSchedule::query()->create([
            'structure_id' => $this->structure->id, 'position_id' => $this->position->id,
            'total' => 1, 'filled' => 0, 'vacant' => 1,
        ]);
    }

    public function test_the_list_offers_the_action_and_opens_a_prefilled_hire_composer(): void
    {
        $this->seedHireTemplate('ise_qebul');
        $this->actingAs($this->hrUser());
        $candidate = $this->makeCandidate(statusId: 30, withOpening: true);

        $component = Livewire::test(CandidateList::class, ['status' => 'all'])
            ->assertSee(__('candidates::common.actions.prepare_hire_order'))
            ->call('openHireOrder', $candidate->id)
            ->assertSet('showSideMenu', 'order-composer')
            ->assertSeeLivewire(OrderComposer::class);

        $this->assertSame([
            'presetCode' => 'ise_qebul',
            'candidateId' => $candidate->id,
            'candidateLabel' => 'Hüseynov Elçin Vüqar',
            'hireStructureId' => $this->structure->id,
            'hirePositionId' => $this->position->id,
        ], $component->instance()->hireOrderComposerParameters);
    }

    public function test_approving_the_prepared_order_hires_and_links_the_candidate(): void
    {
        $this->seedHireTemplate('ise_qebul');
        $this->actingAs($this->hrUser());
        $candidate = $this->makeCandidate(statusId: 30, withOpening: true);

        $parameters = app(CandidateHireOrderService::class)->composerParameters($candidate);
        $this->assertNotNull($parameters);

        Livewire::test(OrderComposer::class, $parameters)
            ->assertSet('presetCode', 'ise_qebul')
            ->assertSet('candidateId', $candidate->id)
            ->assertSet('candidateLabel', 'Hüseynov Elçin Vüqar')
            ->assertSet('hireStructureId', $this->structure->id)
            ->assertSet('hirePositionId', $this->position->id)
            ->set('orderNumber', '77-K')
            ->set('fields', ['var_2' => '01.10.2026-cı il'])
            ->call('issue')
            ->assertHasNoErrors();

        $order = OrderLog::query()->where('order_no', '77-K')->firstOrFail();

        // Issued but pending: the candidate is untouched until the order is approved.
        $this->assertSame(30, (int) $candidate->fresh()->status_id);
        $this->assertNull($candidate->fresh()->hired_personnel_id);

        app(OrderApprovalService::class)->approve($order);

        $personnel = Personnel::query()->where('surname', 'Hüseynov')->firstOrFail();
        $candidate->refresh();

        $this->assertSame(70, (int) $candidate->status_id);
        $this->assertSame($personnel->id, $candidate->hired_personnel_id);
        $this->assertSame($order->id, $candidate->hire_order_id);
        $this->assertSame('77-K', $candidate->hire_order_no);
        $this->assertNotNull($candidate->hired_at);
        $this->assertSame($this->position->id, (int) $personnel->position_id);
        $this->assertSame('1995-01-01', $personnel->birthdate->format('Y-m-d'));
        $this->assertSame('+994501234567', $personnel->phone);

        // A hired candidate no longer offers the action, and links to the employee + order.
        $this->assertFalse(app(CandidateHireOrderService::class)->canPrepare($candidate));

        Livewire::test(CandidateList::class, ['status' => 'all'])
            ->assertDontSee(__('candidates::common.actions.prepare_hire_order'))
            ->assertSee(route('personnel.show', $personnel->id), false)
            ->assertSee(__('candidates::common.actions.open_hire_order'));

        Livewire::test(EditCandidate::class, ['candidateModel' => $candidate->id])
            ->assertSee(__('candidates::common.hire.hired_note'))
            ->assertSee(__('candidates::common.labels.hire_order_number', ['number' => '77-K']))
            ->assertDontSee(__('candidates::common.actions.prepare_hire_order'));
    }

    public function test_an_approved_hire_without_records_can_be_revoked_and_the_candidate_is_ready_again(): void
    {
        $this->seedHireTemplate('ise_qebul');
        $this->actingAs($this->hrUser());
        $candidate = $this->makeCandidate(statusId: 30, withOpening: true);
        DB::table('compensation_regimes')->insert(['code' => 'civil', 'name' => 'Mülki', 'is_active' => true, 'sort' => 1]);
        $order = $this->issueAndApproveHire($candidate, '78-K');

        $personnel = Personnel::query()->where('surname', 'Hüseynov')->firstOrFail();
        // The hire seeded a draft compensation; it is the hire's own record, not a blocker.
        $this->assertTrue(DB::table('employee_compensations')->where('tabel_no', $personnel->tabel_no)->where('status', 'draft')->exists());
        $this->assertSame(1, (int) StaffSchedule::query()->where('position_id', $this->position->id)->value('filled'));

        app(OrderStatusTransitionService::class)->revert($order->fresh(), 'Səhv tərtib edilib');

        $this->assertSame(OrderStatusEnum::PENDING->value, (int) $order->fresh()->status_id);

        // The employee is soft-deleted (kept for the audit trail), the slot is free again.
        $this->assertSoftDeleted('personnels', ['id' => $personnel->id]);
        $this->assertFalse(DB::table('employee_compensations')->where('tabel_no', $personnel->tabel_no)->exists());
        $this->assertSame(0, (int) StaffSchedule::query()->where('position_id', $this->position->id)->value('filled'));

        // The candidate is back on "Əmrə hazır" with every hire link cleared.
        $candidate->refresh();
        $this->assertSame(30, (int) $candidate->status_id);
        $this->assertNull($candidate->hired_personnel_id);
        $this->assertNull($candidate->hire_order_id);
        $this->assertNull($candidate->hire_order_no);
        $this->assertNull($candidate->hired_at);
        $this->assertNull(CandidateApplication::query()->where('candidate_id', $candidate->id)->value('personnel_id'));
        $this->assertFalse(DB::table('employee_lifecycle_events')
            ->where('source_type', 'candidate_order_conversion')->where('source_id', $candidate->id)->exists());
        $this->assertTrue(app(CandidateHireOrderService::class)->canPrepare($candidate));

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'candidates',
            'event' => 'hire_revoked',
            'subject_id' => $personnel->id,
        ]);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'orders', 'event' => 'reverted', 'subject_id' => $order->id]);

        // Approving again hires the candidate afresh under a new staff number.
        app(OrderApprovalService::class)->approve($order->fresh());

        $rehired = Personnel::query()->where('surname', 'Hüseynov')->firstOrFail();
        $this->assertNotSame($personnel->id, $rehired->id);
        $this->assertNotSame($personnel->tabel_no, $rehired->tabel_no);
        $this->assertSame($rehired->id, $candidate->fresh()->hired_personnel_id);
        $this->assertSame(70, (int) $candidate->fresh()->status_id);
    }

    public function test_an_approved_hire_whose_employee_already_has_records_cannot_be_revoked(): void
    {
        $this->seedHireTemplate('ise_qebul');
        $this->actingAs($this->hrUser());
        $candidate = $this->makeCandidate(statusId: 30, withOpening: true);
        $order = $this->issueAndApproveHire($candidate, '80-K');
        $personnel = Personnel::query()->where('surname', 'Hüseynov')->firstOrFail();

        DB::table('personnel_vacations')->insert([
            'tabel_no' => $personnel->tabel_no, 'vacation_places' => 'Bakı', 'duration' => 5,
            'start_date' => '2026-11-02', 'end_date' => '2026-11-06', 'return_work_date' => '2026-11-07',
            'order_given_by' => 'Direktor', 'added_by' => auth()->id(),
        ]);

        foreach (['revert', 'cancel'] as $action) {
            try {
                app(OrderStatusTransitionService::class)->{$action}($order->fresh(), 'Səhv tərtib edilib');
                $this->fail('A hire whose employee has records must not be revocable.');
            } catch (DomainException $exception) {
                $this->assertSame(__('orders::order_composer.errors.hire_revoke_blocked', [
                    'records' => __('orders::order_composer.hire_revoke.records.vacations').': 1',
                ]), $exception->getMessage());
            }
        }

        $this->assertSame(OrderStatusEnum::APPROVED->value, (int) $order->fresh()->status_id);
        $this->assertNotSoftDeleted('personnels', ['id' => $personnel->id]);
        $this->assertSame(70, (int) $candidate->fresh()->status_id);
        $this->assertSame($personnel->id, $candidate->fresh()->hired_personnel_id);
    }

    public function test_cancelling_an_approved_hire_without_records_also_reverts_the_candidate(): void
    {
        $this->seedHireTemplate('ise_qebul');
        $this->actingAs($this->hrUser());
        $candidate = $this->makeCandidate(statusId: 30, withOpening: true);
        $order = $this->issueAndApproveHire($candidate, '81-K');

        app(OrderStatusTransitionService::class)->cancel($order->fresh(), 'Səhv tərtib edilib');

        $this->assertSame(OrderStatusEnum::CANCELLED->value, (int) $order->fresh()->status_id);
        $this->assertSame(30, (int) $candidate->fresh()->status_id);
        $this->assertSame(0, Personnel::query()->where('surname', 'Hüseynov')->count());
    }

    public function test_cancelling_a_pending_hire_order_leaves_the_candidate_ready(): void
    {
        $this->seedHireTemplate('ise_qebul');
        $this->actingAs($this->hrUser());
        $candidate = $this->makeCandidate(statusId: 30, withOpening: true);

        Livewire::test(OrderComposer::class, app(CandidateHireOrderService::class)->composerParameters($candidate))
            ->set('orderNumber', '79-K')
            ->set('fields', ['var_2' => '01.10.2026-cı il'])
            ->call('issue');

        app(OrderStatusTransitionService::class)->cancel(OrderLog::query()->where('order_no', '79-K')->firstOrFail(), 'Səhv tərtib edilib');

        $this->assertSame(30, (int) $candidate->fresh()->status_id);
        $this->assertNull($candidate->fresh()->hired_personnel_id);
        $this->assertTrue(app(CandidateHireOrderService::class)->canPrepare($candidate->fresh()));
    }

    public function test_any_non_final_status_is_eligible_and_final_ones_are_not(): void
    {
        $this->seedHireTemplate('ise_qebul');
        $this->actingAs($this->hrUser());
        $service = app(CandidateHireOrderService::class);

        $this->assertTrue($service->canPrepare($this->makeCandidate(statusId: 10)));
        $this->assertTrue($service->canPrepare($this->makeCandidate(statusId: 30)));
        $stopped = $this->makeCandidate(statusId: 90);
        $this->assertFalse($service->canPrepare($stopped));
        $this->assertFalse($service->canPrepare($this->makeCandidate(statusId: 70)));

        // Without an opening the target post falls back to the candidate's structure.
        $parameters = $service->composerParameters($this->makeCandidate(statusId: 10));
        $this->assertSame($this->structure->id, $parameters['hireStructureId']);
        $this->assertNull($parameters['hirePositionId']);

        Livewire::test(CandidateList::class, ['status' => 'all'])
            ->call('openHireOrder', $stopped->id)
            ->assertDispatched('addError', __('candidates::common.hire.unavailable'))
            ->assertSet('showSideMenu', '');
    }

    public function test_the_action_needs_the_add_orders_permission_and_a_hire_template(): void
    {
        $candidate = $this->makeCandidate(statusId: 30);

        // No hire template yet: nothing to open.
        $this->actingAs($this->hrUser());
        $this->assertNull(app(HireOrderTemplates::class)->hireTemplateCode());
        Livewire::test(CandidateList::class, ['status' => 'all'])
            ->assertDontSee(__('candidates::common.actions.prepare_hire_order'));

        $this->seedHireTemplate('ise_qebul');

        $user = User::factory()->create();
        grantAllStructures($user);
        $user->givePermissionTo([
            Permission::findOrCreate('show-candidates', 'web'),
            Permission::findOrCreate('edit-candidates', 'web'),
        ]);
        $this->actingAs($user);

        Livewire::test(CandidateList::class, ['status' => 'all'])
            ->assertDontSee(__('candidates::common.actions.prepare_hire_order'))
            ->call('openHireOrder', $candidate->id)
            ->assertForbidden();

        Livewire::test(EditCandidate::class, ['candidateModel' => $candidate->id])
            ->assertDontSee(__('candidates::common.actions.prepare_hire_order'));
    }

    public function test_the_candidate_form_hands_over_to_the_list(): void
    {
        $this->seedHireTemplate('ise_qebul');
        $this->actingAs($this->hrUser());
        $candidate = $this->makeCandidate(statusId: 30);

        Livewire::test(EditCandidate::class, ['candidateModel' => $candidate->id])
            ->assertSee(__('candidates::common.actions.prepare_hire_order'))
            ->call('requestHireOrder')
            ->assertDispatched('candidateHireOrderRequested', candidateId: $candidate->id);
    }

    public function test_the_seeded_ise_qebul_template_is_preferred_over_other_hire_templates(): void
    {
        $this->seedHireTemplate('a_hire_custom');
        $this->assertSame('a_hire_custom', app(HireOrderTemplates::class)->hireTemplateCode());

        $this->seedHireTemplate('ise_qebul');
        $this->assertSame('ise_qebul', app(HireOrderTemplates::class)->hireTemplateCode());

        OrderWordTemplate::query()->where('code', 'ise_qebul')->update(['is_active' => false]);
        $this->assertSame('a_hire_custom', app(HireOrderTemplates::class)->hireTemplateCode());
    }

    private function issueAndApproveHire(Candidate $candidate, string $orderNo): OrderLog
    {
        Livewire::test(OrderComposer::class, app(CandidateHireOrderService::class)->composerParameters($candidate))
            ->set('orderNumber', $orderNo)
            ->set('fields', ['var_2' => '01.10.2026-cı il'])
            ->call('issue')
            ->assertHasNoErrors();

        $order = OrderLog::query()->where('order_no', $orderNo)->firstOrFail();
        app(OrderApprovalService::class)->approve($order);

        return $order;
    }

    private function hrUser(): User
    {
        $user = User::factory()->create();
        grantAllStructures($user);
        $user->givePermissionTo([
            Permission::findOrCreate('show-candidates', 'web'),
            Permission::findOrCreate('edit-candidates', 'web'),
            Permission::findOrCreate('add-orders', 'web'),
        ]);

        return $user;
    }

    private function makeCandidate(int $statusId, bool $withOpening = false): Candidate
    {
        $candidate = Candidate::query()->create([
            'surname' => 'Hüseynov', 'name' => 'Elçin', 'patronymic' => 'Vüqar', 'height' => 0,
            'structure_id' => $this->structure->id, 'status_id' => $statusId, 'gender' => 1,
            'birthdate' => '1995-01-01', 'phone' => '+994501234567', 'appeal_date' => '2026-09-01',
        ]);

        if ($withOpening) {
            $requisition = JobRequisition::query()->create([
                'title' => 'Sürücü tələbnaməsi', 'structure_id' => $this->structure->id,
                'position_id' => $this->position->id, 'profile_pack' => 'private',
                'requested_by' => auth()->id(), 'owner_id' => auth()->id(), 'headcount' => 1, 'status' => 'open',
            ]);
            $opening = JobOpening::query()->create([
                'job_requisition_id' => $requisition->id, 'title' => 'Sürücü',
                'structure_id' => $this->structure->id, 'position_id' => $this->position->id,
                'profile_pack' => 'private', 'headcount' => 1, 'status' => 'open',
                'owner_id' => auth()->id(), 'created_by' => auth()->id(),
            ]);
            CandidateApplication::query()->create([
                'candidate_id' => $candidate->id, 'job_opening_id' => $opening->id,
                'current_stage' => 'offer', 'status' => 'active', 'applied_at' => now(), 'moved_at' => now(),
            ]);
        }

        return $candidate;
    }

    private function seedHireTemplate(string $code): void
    {
        $relative = 'order-templates/'.$code.'.docx';
        $phpWord = new PhpWord;
        $phpWord->addSection()->addText('${var_1} ${var_2} tarixindən işə qəbul edilsin.');
        $tmp = tempnam(sys_get_temp_dir(), 'mst_').'.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($tmp);
        Storage::disk('local')->put($relative, (string) file_get_contents($tmp));
        @unlink($tmp);

        OrderWordTemplate::query()->create([
            'code' => $code,
            'label' => 'İşə qəbul '.$code,
            'effect' => 'hire',
            'docx_path' => $relative,
            'variables' => [
                ['token' => 'var_1', 'label' => 'Tam ad', 'source' => 'auto', 'auto_key' => 'employee.full_name', 'field' => null, 'effect_role' => null],
                ['token' => 'var_2', 'label' => 'Tarix', 'source' => 'manual', 'auto_key' => null, 'field' => ['key' => 'var_2', 'type' => 'date'], 'effect_role' => 'start_date'],
            ],
            'is_active' => true,
        ]);
    }
}
