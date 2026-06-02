<?php

namespace Tests\Feature\EmployeeLifecycle;

use App\Enums\OrderStatusEnum;
use App\Models\Component;
use App\Models\Order;
use App\Models\OrderCategory;
use App\Models\OrderLog;
use App\Models\OrderLogComponentAttributes;
use App\Models\OrderStatus;
use App\Models\OrderType;
use App\Models\Personnel;
use App\Models\User;
use App\Modules\EmployeeLifecycle\Application\Services\LifecycleDashboardReadService;
use App\Modules\EmployeeLifecycle\Application\Services\LifecyclePlanTemplateService;
use App\Modules\EmployeeLifecycle\Application\Services\OrderLifecycleIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LifecycleDashboardReadServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_summarizes_lifecycle_events_and_overdue_tasks(): void
    {
        app()->setLocale('az');
        Carbon::setTestNow('2026-05-03 10:00:00');

        $this->assertTrue(Schema::hasTable('employee_lifecycle_events'));
        $this->assertTrue(Schema::hasTable('employee_lifecycle_tasks'));
        $this->assertTrue(Schema::hasTable('employee_lifecycle_plan_templates'));
        $this->assertTrue(Schema::hasTable('employee_lifecycle_task_templates'));

        $owner = User::factory()->create(['name' => 'Lifecycle Owner']);
        $personnel = $this->makePersonnel();

        $onboardingEventId = DB::table('employee_lifecycle_events')->insertGetId([
            'personnel_id' => $personnel->id,
            'tabel_no' => $personnel->tabel_no,
            'type' => 'onboarding',
            'status' => 'in_progress',
            'title' => 'Yeni əməkdaş onboarding',
            'description' => 'İlk həftə hazırlıq planı',
            'effective_date' => '2026-05-01',
            'deadline_at' => '2026-05-02',
            'owner_user_id' => $owner->id,
            'created_by' => $owner->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('employee_lifecycle_events')->insert([
            'personnel_id' => $personnel->id,
            'tabel_no' => $personnel->tabel_no,
            'type' => 'probation',
            'status' => 'planned',
            'title' => 'Probation review',
            'effective_date' => '2026-05-20',
            'deadline_at' => '2026-05-20',
            'owner_user_id' => $owner->id,
            'created_by' => $owner->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('employee_lifecycle_tasks')->insert([
            'event_id' => $onboardingEventId,
            'title' => 'IT hesablarını aktiv et',
            'owner_type' => 'it',
            'owner_user_id' => $owner->id,
            'due_at' => '2026-05-01',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = app(LifecycleDashboardReadService::class)->dashboard();

        $this->assertSame(2, $payload['summary']['active_events']);
        $this->assertSame(1, $payload['summary']['overdue_tasks']);
        $this->assertSame(1, $payload['summary']['probation_queue']);
        $this->assertSame(['onboarding'], app(LifecycleDashboardReadService::class)->events(['type' => 'onboarding'])->pluck('type')->unique()->values()->all());
        $this->assertSame('Lifecycle Owner', $payload['events']->firstWhere('type', 'onboarding')['owner_name']);
        $this->assertTrue($payload['events']->firstWhere('type', 'onboarding')['is_overdue']);
        $this->assertSame('Sınaq müddəti baxışı', $payload['events']->firstWhere('type', 'probation')['title']);
        $this->assertSame(__('employee-lifecycle::dashboard.owner_types.it'), $payload['overdueTasks']->first()['owner_label']);
    }

    public function test_it_launches_onboarding_plan_from_task_templates(): void
    {
        Carbon::setTestNow('2026-05-03 10:00:00');

        $owner = User::factory()->create(['name' => 'Lifecycle Owner']);
        $personnel = $this->makePersonnel();

        $service = app(LifecyclePlanTemplateService::class);
        $templateId = $service->createTemplate([
            'name' => 'Yeni əməkdaş ilk həftə planı',
            'type' => 'onboarding',
            'description' => 'HR, rəhbər və IT üçün onboarding planı',
            'default_duration_days' => 7,
            'created_by' => $owner->id,
        ], [
            ['title' => 'Sənədləri yoxla', 'owner_type' => 'hr', 'due_offset_days' => 0],
            ['title' => 'İş yeri və avadanlıq hazırla', 'owner_type' => 'it', 'due_offset_days' => 1],
            ['title' => 'İlk həftə görüşü keçir', 'owner_type' => 'manager', 'due_offset_days' => 5],
        ]);

        $eventId = $service->launchForPersonnel($templateId, $personnel->id, '2026-05-03', $owner->id, $owner->id);

        $this->assertDatabaseHas('employee_lifecycle_events', [
            'id' => $eventId,
            'personnel_id' => $personnel->id,
            'plan_template_id' => $templateId,
            'type' => 'onboarding',
            'status' => 'in_progress',
            'deadline_at' => '2026-05-10',
        ]);
        $this->assertDatabaseHas('employee_lifecycle_tasks', [
            'event_id' => $eventId,
            'title' => 'İş yeri və avadanlıq hazırla',
            'owner_type' => 'it',
            'due_at' => '2026-05-04',
        ]);
        $this->assertSame(3, DB::table('employee_lifecycle_tasks')->where('event_id', $eventId)->count());

        $payload = app(LifecycleDashboardReadService::class)->dashboard();
        $createdTemplate = $payload['planTemplates']->firstWhere('id', $templateId);

        $this->assertGreaterThanOrEqual(1, $payload['summary']['active_templates']);
        $this->assertSame('Yeni əməkdaş ilk həftə planı', $createdTemplate['name']);
        $this->assertSame(3, $createdTemplate['tasks_count']);
        $this->assertSame(1, $createdTemplate['events_count']);
    }

    public function test_it_updates_deletes_unused_and_archives_used_plan_templates(): void
    {
        Carbon::setTestNow('2026-05-03 10:00:00');

        $owner = User::factory()->create(['name' => 'Lifecycle Owner']);
        $personnel = $this->makePersonnel();
        $service = app(LifecyclePlanTemplateService::class);

        $unusedTemplateId = $service->createTemplate([
            'name' => 'Draft onboarding',
            'type' => 'onboarding',
            'description' => 'Initial draft',
            'default_duration_days' => 10,
            'created_by' => $owner->id,
        ], [
            ['title' => 'Old task', 'owner_type' => 'hr', 'due_offset_days' => 0],
        ]);

        $service->updateTemplate($unusedTemplateId, [
            'name' => 'Updated movement plan',
            'type' => 'movement',
            'description' => 'Updated description',
            'default_duration_days' => 6,
            'is_active' => true,
        ], [
            ['title' => 'Manager approval', 'owner_type' => 'manager', 'due_offset_days' => 0, 'is_required' => true],
            ['title' => 'IT access review', 'owner_type' => 'it', 'due_offset_days' => 2, 'is_required' => true],
        ]);

        $this->assertDatabaseHas('employee_lifecycle_plan_templates', [
            'id' => $unusedTemplateId,
            'name' => 'Updated movement plan',
            'type' => 'movement',
            'default_duration_days' => 6,
            'is_active' => true,
        ]);
        $this->assertSame(2, DB::table('employee_lifecycle_task_templates')->where('plan_template_id', $unusedTemplateId)->count());

        $service->setTemplateActive($unusedTemplateId, false);
        $this->assertDatabaseHas('employee_lifecycle_plan_templates', ['id' => $unusedTemplateId, 'is_active' => false]);
        $this->assertSame('deleted', $service->deleteOrArchiveTemplate($unusedTemplateId));
        $this->assertDatabaseMissing('employee_lifecycle_plan_templates', ['id' => $unusedTemplateId]);

        $usedTemplateId = $service->createTemplate([
            'name' => 'Used onboarding',
            'type' => 'onboarding',
            'default_duration_days' => 7,
            'created_by' => $owner->id,
        ], [
            ['title' => 'Document check', 'owner_type' => 'hr', 'due_offset_days' => 0],
        ]);

        $service->launchForPersonnel($usedTemplateId, $personnel->id, '2026-05-03', $owner->id, $owner->id);

        $this->assertSame('archived', $service->deleteOrArchiveTemplate($usedTemplateId));
        $this->assertDatabaseHas('employee_lifecycle_plan_templates', ['id' => $usedTemplateId, 'is_active' => false]);
    }

    public function test_it_schedules_and_completes_probation_review(): void
    {
        app()->setLocale('az');
        Carbon::setTestNow('2026-05-03 10:00:00');

        $manager = User::factory()->create(['name' => 'Line Manager']);
        $reviewer = User::factory()->create(['name' => 'HR Reviewer']);
        $personnel = $this->makePersonnel();

        $service = app(LifecyclePlanTemplateService::class);
        $reviewId = $service->scheduleProbationReview($personnel->id, '2026-05-01', $manager->id, $reviewer->id, $reviewer->id);

        $payload = app(LifecycleDashboardReadService::class)->dashboard();

        $this->assertSame(1, $payload['summary']['probation_queue']);
        $this->assertSame('Sınaq müddəti baxışı', $payload['events']->firstWhere('type', 'probation')['title']);
        $this->assertSame('Line Manager', $payload['probationReviews']->first()['manager_name']);
        $this->assertTrue($payload['probationReviews']->first()['is_overdue']);

        $service->completeProbationReview($reviewId, 'confirm', 86, 'Probation uğurla tamamlandı.', $reviewer->id);

        $this->assertDatabaseHas('employee_lifecycle_probation_reviews', [
            'id' => $reviewId,
            'status' => 'completed',
            'decision' => 'confirm',
            'score' => 86,
            'reviewed_by' => $reviewer->id,
        ]);

        $eventId = DB::table('employee_lifecycle_probation_reviews')->where('id', $reviewId)->value('event_id');

        $this->assertDatabaseHas('employee_lifecycle_events', [
            'id' => $eventId,
            'status' => 'completed',
        ]);
    }

    public function test_it_schedules_and_completes_internal_movement(): void
    {
        Carbon::setTestNow('2026-05-03 10:00:00');

        $owner = User::factory()->create(['name' => 'HR Business Partner']);
        $personnel = $this->makePersonnel();

        $service = app(LifecyclePlanTemplateService::class);
        $movementId = $service->scheduleMovement(
            $personnel->id,
            'promotion',
            2,
            2,
            '2026-05-08',
            'Vəzifə yüksəlişi qərarı',
            $owner->id,
            $owner->id
        );

        $payload = app(LifecycleDashboardReadService::class)->dashboard();
        $movement = $payload['movements']->first();

        $this->assertSame(1, $payload['summary']['movement_queue']);
        $this->assertSame($movementId, $movement['id']);
        $this->assertSame('Vəzifə yüksəlişi', $movement['movement_type_label']);
        $this->assertSame('Lifecycle HQ', $movement['current_structure_name']);
        $this->assertSame('Lifecycle Target', $movement['target_structure_name']);
        $this->assertSame('Lifecycle Manager', $movement['target_position_name']);

        $service->completeMovement($movementId, $owner->id);

        $this->assertDatabaseHas('employee_lifecycle_movements', [
            'id' => $movementId,
            'status' => 'completed',
            'approved_by' => $owner->id,
        ]);
        $this->assertDatabaseHas('personnels', [
            'id' => $personnel->id,
            'structure_id' => 2,
            'position_id' => 2,
        ]);

        $eventId = DB::table('employee_lifecycle_movements')->where('id', $movementId)->value('event_id');

        $this->assertDatabaseHas('employee_lifecycle_events', [
            'id' => $eventId,
            'status' => 'completed',
        ]);
    }

    public function test_it_opens_and_completes_offboarding_case_with_exit_checklist(): void
    {
        app()->setLocale('az');
        Carbon::setTestNow('2026-05-03 10:00:00');

        $owner = User::factory()->create(['name' => 'Exit Owner']);
        $personnel = $this->makePersonnel();

        $service = app(LifecyclePlanTemplateService::class);
        $caseId = $service->openOffboardingCase(
            $personnel->id,
            '2026-05-07',
            'Müqavilə müddəti bitir',
            $owner->id,
            $owner->id
        );

        $payload = app(LifecycleDashboardReadService::class)->dashboard();
        $case = $payload['offboardingCases']->first();
        $eventId = DB::table('employee_lifecycle_offboarding_cases')->where('id', $caseId)->value('event_id');

        $this->assertSame(1, $payload['summary']['offboarding_queue']);
        $this->assertSame($caseId, $case['id']);
        $this->assertSame('Exit Owner', $case['owner_name']);
        $this->assertFalse($case['exit_interview_done']);
        $this->assertSame(4, DB::table('employee_lifecycle_tasks')->where('event_id', $eventId)->count());
        $this->assertDatabaseHas('employee_lifecycle_tasks', [
            'event_id' => $eventId,
            'title' => 'Sistem giriş hüquqlarını bağla',
        ]);
        $this->assertSame('İşdən ayrılma prosesi', $payload['events']->firstWhere('type', 'offboarding')['title']);

        $service->completeOffboardingCase($caseId, 'Exit interview tamamlandı.', $owner->id);

        $this->assertDatabaseHas('employee_lifecycle_offboarding_cases', [
            'id' => $caseId,
            'status' => 'completed',
            'completed_by' => $owner->id,
        ]);
        $this->assertDatabaseHas('employee_lifecycle_events', [
            'id' => $eventId,
            'status' => 'completed',
        ]);
    }

    public function test_it_creates_lifecycle_movement_and_offboarding_from_configured_orders(): void
    {
        Carbon::setTestNow('2026-05-03 10:00:00');

        config()->set('employee_lifecycle.order_integration.promotion_order_ids', [8101]);
        config()->set('employee_lifecycle.order_integration.offboarding_order_ids', [8102]);

        $owner = User::factory()->create(['name' => 'Order Owner']);
        $personnel = $this->makePersonnel();
        $service = app(OrderLifecycleIntegrationService::class);

        $promotionOrder = $this->makeLifecycleOrder(8101, 'PROM-2026-1', $owner, $personnel, [
            '$target_structure_id' => ['id' => 2, 'value' => 'Lifecycle Target'],
            '$target_position_id' => ['id' => 2, 'value' => 'Lifecycle Manager'],
            '$day' => ['value' => '9'],
            '$month' => ['value' => 'may'],
            '$year' => ['value' => '2026'],
        ]);

        $service->handleApprovedOrder($promotionOrder, $owner->id);

        $this->assertDatabaseHas('employee_lifecycle_movements', [
            'personnel_id' => $personnel->id,
            'movement_type' => 'promotion',
            'target_structure_id' => 2,
            'target_position_id' => 2,
            'effective_date' => '2026-05-09',
            'status' => 'planned',
        ]);
        $this->assertDatabaseHas('employee_lifecycle_events', [
            'personnel_id' => $personnel->id,
            'source_type' => 'order_log_movement',
            'source_id' => $promotionOrder->id,
        ]);
        $orderEvent = app(LifecycleDashboardReadService::class)
            ->events()
            ->firstWhere('source_id', $promotionOrder->id);

        $this->assertTrue($orderEvent['source_is_order']);
        $this->assertSame('Əmr: PROM-2026-1', $orderEvent['source_label']);

        $offboardingOrder = $this->makeLifecycleOrder(8102, 'EXIT-2026-1', $owner, $personnel, [
            '$last_working_date' => ['value' => '2026-05-31'],
        ]);

        $service->handleApprovedOrder($offboardingOrder, $owner->id);

        $this->assertDatabaseHas('employee_lifecycle_offboarding_cases', [
            'personnel_id' => $personnel->id,
            'last_working_date' => '2026-05-31',
            'status' => 'open',
        ]);
        $this->assertDatabaseHas('employee_lifecycle_events', [
            'personnel_id' => $personnel->id,
            'source_type' => 'order_log_offboarding',
            'source_id' => $offboardingOrder->id,
        ]);

        $service->handleApprovedOrder($promotionOrder->fresh(), $owner->id);

        $this->assertSame(1, DB::table('employee_lifecycle_events')->where('source_type', 'order_log_movement')->where('source_id', $promotionOrder->id)->count());
    }

    public function test_dashboard_manager_can_run_lifecycle_operations(): void
    {
        Carbon::setTestNow('2026-05-03 10:00:00');

        foreach (['show-employee-lifecycle', 'manage-employee-lifecycle'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user = User::factory()->create(['name' => 'Lifecycle Manager', 'is_active' => true]);
        $user->givePermissionTo(['show-employee-lifecycle', 'manage-employee-lifecycle']);

        $personnel = $this->makePersonnel();

        Livewire::actingAs($user);

        $component = Livewire::test(\App\Modules\EmployeeLifecycle\Livewire\Dashboard::class)
            ->set('templateForm.name', 'İlk həftə planı')
            ->set('templateForm.type', 'onboarding')
            ->set('templateForm.default_duration_days', 7)
            ->set('templateForm.description', 'Yeni əməkdaş üçün standart plan')
            ->set('templateForm.tasks', "Sənədləri yoxla\nAccessləri hazırla")
            ->call('createTemplate')
            ->assertHasNoErrors();

        $templateId = DB::table('employee_lifecycle_plan_templates')
            ->where('name', 'İlk həftə planı')
            ->value('id');

        $component
            ->set('launchForm.template_id', $templateId)
            ->set('launchForm.personnel_id', $personnel->id)
            ->set('launchForm.start_date', '2026-05-03')
            ->set('launchForm.owner_user_id', $user->id)
            ->call('launchTemplate')
            ->assertHasNoErrors()
            ->set('probationForm.personnel_id', $personnel->id)
            ->set('probationForm.review_due_at', '2026-05-20')
            ->set('probationForm.manager_user_id', $user->id)
            ->set('probationForm.hr_reviewer_user_id', $user->id)
            ->call('scheduleProbation')
            ->assertHasNoErrors()
            ->set('movementForm.personnel_id', $personnel->id)
            ->set('movementForm.movement_type', 'transfer')
            ->set('movementForm.target_structure_id', 2)
            ->set('movementForm.target_position_id', 2)
            ->set('movementForm.effective_date', '2026-05-21')
            ->set('movementForm.owner_user_id', $user->id)
            ->call('scheduleMovement')
            ->assertHasNoErrors()
            ->set('offboardingForm.personnel_id', $personnel->id)
            ->set('offboardingForm.last_working_date', '2026-06-30')
            ->set('offboardingForm.reason', 'Müqavilə bitir')
            ->set('offboardingForm.owner_user_id', $user->id)
            ->call('openOffboarding')
            ->assertHasNoErrors()
            ->set('completionForm.probation_review_id', DB::table('employee_lifecycle_probation_reviews')->value('id'))
            ->set('completionForm.probation_decision', 'confirm')
            ->set('completionForm.probation_score', 91)
            ->call('completeProbationReview')
            ->assertHasNoErrors()
            ->set('completionForm.movement_id', DB::table('employee_lifecycle_movements')->value('id'))
            ->call('completeMovement')
            ->assertHasNoErrors()
            ->set('completionForm.offboarding_case_id', DB::table('employee_lifecycle_offboarding_cases')->value('id'))
            ->set('completionForm.exit_summary', 'Exit interview tamamlandı.')
            ->call('completeOffboarding')
            ->assertHasNoErrors();

        $this->assertSame(2, DB::table('employee_lifecycle_task_templates')->where('plan_template_id', $templateId)->count());
        $this->assertDatabaseHas('employee_lifecycle_probation_reviews', ['status' => 'completed', 'score' => 91]);
        $this->assertDatabaseHas('employee_lifecycle_movements', ['status' => 'completed']);
        $this->assertDatabaseHas('employee_lifecycle_offboarding_cases', ['status' => 'completed']);
    }

    public function test_dashboard_manager_can_edit_and_remove_plan_templates(): void
    {
        Carbon::setTestNow('2026-05-03 10:00:00');

        foreach (['show-employee-lifecycle', 'manage-employee-lifecycle'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user = User::factory()->create(['name' => 'Lifecycle Manager', 'is_active' => true]);
        $user->givePermissionTo(['show-employee-lifecycle', 'manage-employee-lifecycle']);

        $templateId = app(LifecyclePlanTemplateService::class)->createTemplate([
            'name' => 'Editable onboarding',
            'type' => 'onboarding',
            'default_duration_days' => 14,
            'created_by' => $user->id,
        ], [
            ['title' => 'Old task', 'owner_type' => 'hr', 'due_offset_days' => 0],
        ]);

        Livewire::actingAs($user);

        Livewire::test(\App\Modules\EmployeeLifecycle\Livewire\Dashboard::class)
            ->call('selectTemplate', $templateId)
            ->assertSet('selectedTemplateId', $templateId)
            ->set('editingTemplateForm.name', 'Editable probation plan')
            ->set('editingTemplateForm.type', 'probation')
            ->set('editingTemplateForm.default_duration_days', 30)
            ->set('editingTemplateForm.description', 'Updated from editor')
            ->set('editingTemplateForm.tasks', [
                ['title' => 'Manager feedback', 'owner_type' => 'manager', 'due_offset_days' => 3, 'is_required' => true],
                ['title' => 'HR decision', 'owner_type' => 'hr', 'due_offset_days' => 7, 'is_required' => true],
            ])
            ->call('updateTemplate')
            ->assertHasNoErrors()
            ->call('toggleTemplateActive')
            ->assertHasNoErrors()
            ->call('deleteOrArchiveTemplate')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('employee_lifecycle_plan_templates', ['id' => $templateId]);
        $this->assertDatabaseMissing('employee_lifecycle_task_templates', ['plan_template_id' => $templateId]);
    }

    public function test_dashboard_validation_errors_use_localized_attribute_labels(): void
    {
        app()->setLocale('az');

        foreach (['show-employee-lifecycle', 'manage-employee-lifecycle'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user = User::factory()->create(['name' => 'Lifecycle Manager', 'is_active' => true]);
        $user->givePermissionTo(['show-employee-lifecycle', 'manage-employee-lifecycle']);

        Livewire::actingAs($user);

        Livewire::test(\App\Modules\EmployeeLifecycle\Livewire\Dashboard::class)
            ->call('scheduleProbation')
            ->assertHasErrors([
                'probationForm.personnel_id' => ['required'],
                'probationForm.review_due_at' => ['required'],
            ])
            ->assertSee('Əməkdaş mütləqdir')
            ->assertSee('Qiymətləndirmə tarixi mütləqdir')
            ->assertDontSee('personnel id mütləqdir')
            ->assertDontSee('probation form personnel id');
    }

    private function makePersonnel(): Personnel
    {
        $this->seedReferenceData();

        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'LC'.Str::upper(Str::random(6)),
            'surname' => 'Lifecycle',
            'name' => 'Employee',
            'patronymic' => 'Test',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'email' => 'lifecycle@example.test',
            'mobile' => '994501112233',
            'nationality_id' => 1,
            'pin' => 'LC'.str_pad((string) random_int(1, 99999), 7, '0', STR_PAD_LEFT),
            'residental_address' => 'Main st',
            'education_degree_id' => 1,
            'structure_id' => 1,
            'position_id' => 1,
            'work_norm_id' => 1,
            'join_work_date' => '2026-05-01',
            'added_by' => 1,
            'is_pending' => false,
        ]));
    }

    private function seedReferenceData(): void
    {
        DB::table('countries')->insertOrIgnore(['id' => 1, 'code' => 'AZ']);
        DB::table('country_translations')->insertOrIgnore([
            'id' => 1,
            'country_id' => 1,
            'locale' => 'az',
            'title' => 'Azərbaycan',
        ]);
        DB::table('education_degrees')->insertOrIgnore([
            'id' => 1,
            'title_az' => 'Bakalavr',
            'title_en' => 'Bachelor',
            'title_ru' => 'Bachelor',
        ]);
        DB::table('structures')->insertOrIgnore([
            'id' => 1,
            'name' => 'Lifecycle HQ',
            'shortname' => 'LHQ',
            'parent_id' => null,
            'coefficient' => 1.10,
            'code' => 30,
            'level' => 1,
        ]);
        DB::table('structures')->insertOrIgnore([
            'id' => 2,
            'name' => 'Lifecycle Target',
            'shortname' => 'LHT',
            'parent_id' => null,
            'coefficient' => 1.20,
            'code' => 31,
            'level' => 1,
        ]);
        DB::table('positions')->insertOrIgnore([
            'id' => 1,
            'name' => 'Lifecycle Officer',
        ]);
        DB::table('positions')->insertOrIgnore([
            'id' => 2,
            'name' => 'Lifecycle Manager',
        ]);
        DB::table('work_norms')->insertOrIgnore([
            'id' => 1,
            'name_az' => 'Tam iş günü',
            'name_en' => 'Full time',
            'name_ru' => 'Full time',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeLifecycleOrder(int $orderId, string $orderNo, User $owner, Personnel $personnel, array $attributes): OrderLog
    {
        OrderCategory::query()->firstOrCreate([
            'id' => 9900,
        ], [
            'name_az' => 'Lifecycle order',
            'name_en' => 'Lifecycle order',
            'name_ru' => 'Lifecycle order',
        ]);
        Order::query()->forceCreate([
            'id' => $orderId,
            'order_category_id' => 9900,
            'name' => 'Lifecycle order',
            'content' => 'templates/lifecycle.docx',
            'order_model' => '\\App\\Models\\Personnel',
            'blade' => Order::BLADE_DEFAULT,
        ]);
        $orderType = OrderType::query()->create([
            'order_id' => $orderId,
            'name' => 'Lifecycle order',
        ]);
        OrderStatus::query()->firstOrCreate([
            'id' => OrderStatusEnum::APPROVED->value,
            'locale' => 'az',
        ], [
            'name' => 'Təsdiqlənib',
        ]);
        $componentPayload = [
            'name' => 'Lifecycle component',
            'title' => 'Lifecycle component',
            'content' => '$fullname',
            'dynamic_fields' => [],
        ];

        if (Schema::hasColumn('components', 'order_type_id')) {
            $componentPayload['order_type_id'] = $orderType->id;
        } else {
            $componentPayload['order_id'] = $orderId;
        }

        $component = Component::query()->forceCreate($componentPayload);
        $orderLog = OrderLog::query()->create([
            'order_id' => $orderId,
            'order_type_id' => $orderType->id,
            'order_no' => $orderNo,
            'given_date' => '2026-05-01 10:00:00',
            'given_by' => 'HR Director',
            'given_by_rank' => 'director',
            'status_id' => OrderStatusEnum::APPROVED->value,
            'creator_id' => $owner->id,
        ]);

        DB::table('order_log_personnels')->insert([
            'order_no' => $orderLog->order_no,
            'component_id' => $component->id,
            'tabel_no' => $personnel->tabel_no,
        ]);
        OrderLogComponentAttributes::query()->create([
            'order_no' => $orderLog->order_no,
            'component_id' => $component->id,
            'attributes' => $attributes,
            'row_number' => 1,
        ]);

        return $orderLog;
    }
}
