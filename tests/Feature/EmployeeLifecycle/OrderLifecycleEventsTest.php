<?php

namespace Tests\Feature\EmployeeLifecycle;

use App\Models\OrderLog;
use App\Models\OrderWordTemplate;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Modules\EmployeeLifecycle\Application\Services\LifecycleDashboardReadService;
use App\Modules\EmployeeLifecycle\Contracts\OrderLifecycleSync;
use App\Modules\Orders\Application\Services\DeferredTransferService;
use App\Modules\Orders\Infrastructure\Document\OrderStatusTransitionService;
use App\Services\Modules\ModuleState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Köçürmə və xitam əmrləri həyat dövrü hadisəsi açır: təsdiqdə (və ya gələcək tarixli köçürmə
 * qüvvəyə minəndə), idempotent, ləğvdə ləğv olunan, yenidən təsdiqdə yenidən açılan.
 */
class OrderLifecycleEventsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
    }

    public function test_an_approved_transfer_opens_a_role_change_event_with_checklist(): void
    {
        $personnel = $this->makePersonnel('Transfer');
        $oldPosition = $personnel->position_id;
        $target = Position::query()->create(['name' => 'Lead']);
        $order = $this->singleOrder('transfer', $personnel, ['target' => $target->id, 'on' => '01.01.2020']);

        app(OrderStatusTransitionService::class)->approve($order);

        $event = $this->event(OrderLifecycleSync::SOURCE_TRANSFER, $order->id, $personnel->id);
        $this->assertNotNull($event);
        $this->assertSame('movement', $event->type);
        $this->assertSame('in_progress', $event->status);
        $this->assertSame('2020-01-01', Carbon::parse($event->effective_date)->toDateString());
        $this->assertSame($order->fresh()->order_no, json_decode($event->meta, true)['order_no']);
        $this->assertGreaterThan(0, DB::table('employee_lifecycle_tasks')->where('event_id', $event->id)->where('status', 'open')->count());

        $movement = DB::table('employee_lifecycle_movements')->where('event_id', $event->id)->first();
        $this->assertSame('completed', $movement->status);
        $this->assertSame($oldPosition, (int) $movement->current_position_id);
        $this->assertSame($target->id, (int) $movement->target_position_id);
    }

    public function test_repeated_sync_is_idempotent_and_cancel_then_reapprove_reopens_the_same_event(): void
    {
        $personnel = $this->makePersonnel('Idempotent');
        $target = Position::query()->create(['name' => 'Lead']);
        $order = $this->singleOrder('transfer', $personnel, ['target' => $target->id, 'on' => '01.01.2020']);
        $transitions = app(OrderStatusTransitionService::class);

        $transitions->approve($order);
        app(OrderLifecycleSync::class)->transferApplied($order->id, 'X', $personnel->id, '2020-01-01', []);
        $this->assertSame(1, $this->eventCount(OrderLifecycleSync::SOURCE_TRANSFER, $order->id));

        $event = $this->event(OrderLifecycleSync::SOURCE_TRANSFER, $order->id, $personnel->id);
        $firstTask = DB::table('employee_lifecycle_tasks')->where('event_id', $event->id)->orderBy('id')->first();
        DB::table('employee_lifecycle_tasks')->where('id', $firstTask->id)->update(['status' => 'completed', 'completed_at' => now()]);

        $transitions->cancel($order->fresh(), 'Səhv köçürmə');

        $event = $this->event(OrderLifecycleSync::SOURCE_TRANSFER, $order->id, $personnel->id);
        $this->assertSame('cancelled', $event->status);
        $this->assertSame('completed', DB::table('employee_lifecycle_tasks')->where('id', $firstTask->id)->value('status'));
        $this->assertSame(0, DB::table('employee_lifecycle_tasks')->where('event_id', $event->id)->where('status', 'open')->count());
        $this->assertSame('cancelled', DB::table('employee_lifecycle_movements')->where('event_id', $event->id)->value('status'));

        $transitions->reopen($order->fresh());
        $transitions->approve($order->fresh());

        $this->assertSame(1, $this->eventCount(OrderLifecycleSync::SOURCE_TRANSFER, $order->id));
        $event = $this->event(OrderLifecycleSync::SOURCE_TRANSFER, $order->id, $personnel->id);
        $this->assertSame('in_progress', $event->status);
        $this->assertSame('completed', DB::table('employee_lifecycle_tasks')->where('id', $firstTask->id)->value('status'));
        $this->assertGreaterThan(0, DB::table('employee_lifecycle_tasks')->where('event_id', $event->id)->where('status', 'open')->count());
    }

    public function test_a_future_transfer_opens_its_event_only_when_it_is_applied(): void
    {
        Carbon::setTestNow('2026-10-10 12:00:00');
        try {
            $personnel = $this->makePersonnel('Deferred');
            $target = Position::query()->create(['name' => 'Future lead']);
            $order = $this->singleOrder('transfer', $personnel, ['target' => $target->id, 'on' => '15.10.2026']);

            app(OrderStatusTransitionService::class)->approve($order);
            $this->assertSame(0, $this->eventCount(OrderLifecycleSync::SOURCE_TRANSFER, $order->id));

            Carbon::setTestNow('2026-10-15 00:10:00');
            $this->artisan('orders:apply-due-transfers')->assertSuccessful();
            $this->assertSame(['applied' => 0, 'skipped' => 0, 'failed' => 0], app(DeferredTransferService::class)->applyDue());

            $this->assertSame(1, $this->eventCount(OrderLifecycleSync::SOURCE_TRANSFER, $order->id));
            $event = $this->event(OrderLifecycleSync::SOURCE_TRANSFER, $order->id, $personnel->id);
            $this->assertSame('2026-10-15', Carbon::parse($event->effective_date)->toDateString());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_a_cancelled_pending_transfer_never_opens_an_event(): void
    {
        $personnel = $this->makePersonnel('Never');
        $target = Position::query()->create(['name' => 'Never lead']);
        $order = $this->singleOrder('transfer', $personnel, ['target' => $target->id, 'on' => '01.01.2099']);

        app(OrderStatusTransitionService::class)->approve($order);
        app(OrderStatusTransitionService::class)->cancel($order->fresh(), 'Səhv köçürmə');

        $this->assertSame(0, $this->eventCount(OrderLifecycleSync::SOURCE_TRANSFER, $order->id));
    }

    public function test_each_participant_of_a_multi_person_transfer_gets_their_own_event(): void
    {
        [$a, $b] = [$this->makePersonnel('Multi A'), $this->makePersonnel('Multi B')];
        $target = Position::query()->create(['name' => 'Team lead']);
        OrderWordTemplate::query()->create([
            'code' => 'lifecycle_multi_transfer', 'label' => 'Transfer', 'docx_path' => 'test.docx', 'effect' => 'transfer', 'multi_participant' => true,
            'variables' => [
                ['token' => 'target', 'source' => 'manual', 'label' => 'Target', 'scope' => 'participant', 'field' => ['key' => 'target', 'type' => 'position', 'required' => true], 'effect_role' => 'new_position'],
                ['token' => 'on', 'source' => 'manual', 'label' => 'On', 'scope' => 'participant', 'field' => ['key' => 'on', 'type' => 'date', 'required' => true], 'effect_role' => 'effective_date'],
            ],
        ]);
        $order = OrderLog::query()->create([
            'order_no' => 'LC-MULTI', 'given_date' => '2020-01-01', 'given_by' => 'Test', 'given_by_rank' => '', 'status_id' => 10,
            'template_render_mode' => 'docx_v1', 'template_snapshot' => ['template_code' => 'lifecycle_multi_transfer', 'personnel_id' => $a->id, 'fields' => []],
        ]);
        foreach ([$a, $b] as $index => $personnel) {
            $order->participants()->create(['personnel_id' => $personnel->id, 'position' => $index + 1, 'fields' => ['target' => $target->id, 'on' => '2020-01-01']]);
        }

        app(OrderStatusTransitionService::class)->approve($order);
        $this->assertSame(2, $this->eventCount(OrderLifecycleSync::SOURCE_TRANSFER, $order->id));

        app(OrderStatusTransitionService::class)->cancel($order->fresh(), 'Səhv köçürmə');
        $this->assertSame(2, DB::table('employee_lifecycle_events')->where('source_type', OrderLifecycleSync::SOURCE_TRANSFER)->where('status', 'cancelled')->count());
    }

    public function test_an_approved_termination_opens_offboarding_with_final_settlement_and_cancel_cancels_it(): void
    {
        $personnel = $this->makePersonnel('Termination');
        $order = $this->singleOrder('termination', $personnel, ['last_day' => '01.03.2020']);
        $transitions = app(OrderStatusTransitionService::class);

        $transitions->approve($order);

        $event = $this->event(OrderLifecycleSync::SOURCE_TERMINATION, $order->id, $personnel->id);
        $this->assertNotNull($event);
        $this->assertSame('offboarding', $event->type);
        $this->assertSame('in_progress', $event->status);
        $case = DB::table('employee_lifecycle_offboarding_cases')->where('event_id', $event->id)->first();
        $this->assertSame('open', $case->status);
        $this->assertSame('2020-03-01', Carbon::parse($case->last_working_date)->toDateString());
        $titles = DB::table('employee_lifecycle_tasks')->where('event_id', $event->id)->pluck('title')->all();
        $this->assertContains(__('employee-lifecycle::dashboard.checklist.final_settlement'), $titles);

        $transitions->revert($order->fresh(), 'Səhv xitam əmri');

        $this->assertSame('cancelled', $this->event(OrderLifecycleSync::SOURCE_TERMINATION, $order->id, $personnel->id)->status);
        $this->assertSame('cancelled', DB::table('employee_lifecycle_offboarding_cases')->where('event_id', $event->id)->value('status'));
        $this->assertNull($personnel->fresh()->leave_work_date);
    }

    public function test_without_templates_the_built_in_checklists_are_used(): void
    {
        DB::table('employee_lifecycle_plan_templates')->update(['is_active' => false]);
        $personnel = $this->makePersonnel('Defaults');

        app(OrderLifecycleSync::class)->terminationApplied(999, 'T-1', $personnel->id, '2020-03-01');
        app(OrderLifecycleSync::class)->transferApplied(998, 'K-1', $personnel->id, '2020-01-01', ['to_position_id' => $personnel->position_id]);

        $offboarding = $this->event(OrderLifecycleSync::SOURCE_TERMINATION, 999, $personnel->id);
        $movement = $this->event(OrderLifecycleSync::SOURCE_TRANSFER, 998, $personnel->id);
        $this->assertSame([
            __('employee-lifecycle::dashboard.checklist.verify_handover_assets'),
            __('employee-lifecycle::dashboard.checklist.close_system_access'),
            __('employee-lifecycle::dashboard.checklist.final_settlement'),
            __('employee-lifecycle::dashboard.checklist.complete_exit_interview'),
        ], DB::table('employee_lifecycle_tasks')->where('event_id', $offboarding->id)->orderBy('sort_order')->pluck('title')->all());
        $this->assertContains(
            __('employee-lifecycle::dashboard.checklist.update_access_rights'),
            DB::table('employee_lifecycle_tasks')->where('event_id', $movement->id)->pluck('title')->all(),
        );
    }

    public function test_nothing_is_recorded_while_the_lifecycle_module_is_off(): void
    {
        $catalog = config('modules.catalog');
        $catalog['employee-lifecycle']['enabled'] = false;
        app(ModuleState::class)->refresh($catalog);

        try {
            $personnel = $this->makePersonnel('Off');
            app(OrderLifecycleSync::class)->terminationApplied(1, 'T-1', $personnel->id, '2020-03-01');
            $this->assertSame(0, DB::table('employee_lifecycle_events')->count());
        } finally {
            app(ModuleState::class)->refresh(config('modules.catalog'));
        }
    }

    public function test_order_events_respect_the_viewers_structure_scope(): void
    {
        $inside = $this->makePersonnel('Inside');
        $outsideStructure = Structure::query()->create(['name' => 'Kənar', 'shortname' => 'KN']);
        $outside = $this->makePersonnel('Outside', $outsideStructure->id);
        app(OrderLifecycleSync::class)->terminationApplied(501, 'T-501', $inside->id, '2020-03-01');
        app(OrderLifecycleSync::class)->terminationApplied(502, 'T-502', $outside->id, '2020-03-01');

        $viewer = grantStructures(User::factory()->create(), [$inside->structure_id]);
        $read = app(LifecycleDashboardReadService::class)->forViewer($viewer);
        $names = collect($read->events()->items())->pluck('employee_name')->implode('|');

        $this->assertStringContainsString('Inside', $names);
        $this->assertStringNotContainsString('Outside', $names);
        $this->assertTrue($read->isRecordVisible('employee_lifecycle_events', $this->event(OrderLifecycleSync::SOURCE_TERMINATION, 501, $inside->id)->id));
        $this->assertFalse($read->isRecordVisible('employee_lifecycle_events', $this->event(OrderLifecycleSync::SOURCE_TERMINATION, 502, $outside->id)->id));
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function singleOrder(string $effect, Personnel $personnel, array $fields): OrderLog
    {
        $variables = $effect === 'transfer'
            ? [
                ['token' => 'target', 'source' => 'manual', 'label' => 'Target', 'field' => ['key' => 'target', 'type' => 'position', 'required' => true], 'effect_role' => 'new_position'],
                ['token' => 'on', 'source' => 'manual', 'label' => 'On', 'field' => ['key' => 'on', 'type' => 'date', 'required' => true], 'effect_role' => 'effective_date'],
            ]
            : [
                ['token' => 'last_day', 'source' => 'manual', 'label' => 'Last day', 'field' => ['key' => 'last_day', 'type' => 'date', 'required' => true], 'effect_role' => 'date'],
            ];

        OrderWordTemplate::query()->firstOrCreate(['code' => 'lifecycle_'.$effect], [
            'label' => ucfirst($effect), 'docx_path' => 'test.docx', 'effect' => $effect, 'variables' => $variables,
        ]);

        return OrderLog::query()->create([
            'order_no' => 'LC-'.Str::upper(Str::random(5)), 'given_date' => '2020-01-01', 'given_by' => 'Test', 'given_by_rank' => '', 'status_id' => 10,
            'template_render_mode' => 'docx_v1',
            'template_snapshot' => ['template_code' => 'lifecycle_'.$effect, 'personnel_id' => $personnel->id, 'fields' => $fields],
        ]);
    }

    private function event(string $sourceType, int $orderId, int $personnelId): ?object
    {
        return DB::table('employee_lifecycle_events')
            ->where('source_type', $sourceType)
            ->where('source_id', $orderId)
            ->where('personnel_id', $personnelId)
            ->first();
    }

    private function eventCount(string $sourceType, int $orderId): int
    {
        return DB::table('employee_lifecycle_events')->where('source_type', $sourceType)->where('source_id', $orderId)->count();
    }

    private function makePersonnel(string $surname, ?int $structureId = null): Personnel
    {
        $structure = $structureId ?? Structure::query()->firstOrCreate(['name' => 'Mərkəz'], ['shortname' => 'M'])->id;
        $position = Position::query()->firstOrCreate(['name' => 'operator']);

        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'LC'.Str::upper(Str::random(6)),
            'surname' => $surname,
            'name' => 'Rauf',
            'patronymic' => 'Elşən',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'email' => Str::lower(Str::random(8)).'@example.com',
            'mobile' => '994501112233',
            'nationality_id' => 1,
            'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
            'residental_address' => 'Main st',
            'education_degree_id' => 1,
            'work_norm_id' => 1,
            'structure_id' => $structure,
            'position_id' => $position->id,
            'join_work_date' => '2015-01-01',
            'added_by' => 1,
            'is_pending' => false,
        ]));
    }
}
