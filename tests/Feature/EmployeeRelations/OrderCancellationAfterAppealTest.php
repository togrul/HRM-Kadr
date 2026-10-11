<?php

namespace Tests\Feature\EmployeeRelations;

use App\Models\OrderLog;
use App\Models\Personnel;
use App\Models\User;
use App\Modules\EmployeeRelations\Application\Services\AppealService;
use App\Modules\EmployeeRelations\Application\Services\CaseAccess;
use App\Modules\EmployeeRelations\Application\Services\CaseWorkflow;
use App\Modules\EmployeeRelations\Application\Services\DecisionService;
use App\Modules\EmployeeRelations\Application\Services\OrderCancellationTracker;
use App\Modules\EmployeeRelations\Domain\CaseStatus;
use App\Modules\EmployeeRelations\Domain\DecisionOutcome;
use App\Modules\EmployeeRelations\Domain\Models\RelationCase;
use App\Modules\EmployeeRelations\Domain\Models\RelationCaseAppeal;
use App\Modules\EmployeeRelations\Domain\OrderCancellationState;
use App\Modules\EmployeeRelations\Livewire\CaseDetail;
use App\Modules\EmployeeRelations\Livewire\Dashboard;
use App\Modules\Orders\Contracts\LinkedOrderCancellation;
use App\Modules\Orders\Contracts\OrderStatusChanged;
use App\Modules\Orders\Infrastructure\Document\LinkedOrderCancellationLookup;
use App\Modules\Orders\Infrastructure\Document\OrderIssueService;
use App\Modules\Orders\Infrastructure\Document\OrderStatusTransitionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Feature\EmployeeRelations\Concerns\BuildsEmployeeRelationCases;
use Tests\TestCase;

/**
 * Apellyasiya intizam qərarını ləğv edəndə bağlı əmr özü ləğv OLUNMUR: iş «ləğv gözləyir»
 * vəziyyətinə düşür, əmr Orders-un öz axını ilə ləğv olunana (və ya istisna təsdiq
 * edilənə) qədər bağlanmır.
 */
class OrderCancellationAfterAppealTest extends TestCase
{
    use BuildsEmployeeRelationCases;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-07-01 10:00:00');
        $this->erFakeDrafter();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * Apellyasiya baxışına qədər gətirilmiş iş.
     *
     * @return array{0: RelationCase, 1: User, 2: User, 3: Personnel}
     */
    private function caseInReview(DecisionOutcome $outcome = DecisionOutcome::Reprimand): array
    {
        $structure = $this->erStructure('Logistika');
        $subject = $this->erPersonnel('Etirazçıyev', $structure->id);
        $subjectUser = $this->erUser([]);
        $this->erLink($subjectUser, $subject);
        $hr = $this->erUser([CaseAccess::SHOW, CaseAccess::MANAGE], [$structure->id], name: 'HR Əsas');

        $case = $this->erOpenCase($hr, $subject, ['occurred_at' => '2026-06-25', 'discovered_at' => '2026-06-28']);
        app(CaseWorkflow::class)->transition($case, CaseStatus::Investigating, $hr);
        app(DecisionService::class)->decide($case->fresh(), $hr, $outcome, 'Qərarın əsaslandırılması.');
        app(AppealService::class)->submit($case->fresh(), $subjectUser, $subject->id, 'Qərarla razı deyiləm, yenidən baxılsın.');
        app(AppealService::class)->startReview($case->fresh(), $hr);

        return [$case->fresh(), $hr, $subjectUser, $subject];
    }

    private function annul(RelationCase $case, User $hr): array
    {
        return app(AppealService::class)->resolve($case->fresh(), $hr, RelationCaseAppeal::OUTCOME_ANNULLED, 'İzahat qəbul edildi.');
    }

    public function test_annulling_a_sanction_puts_the_case_into_order_cancellation_pending_without_touching_the_order(): void
    {
        [$case, $hr] = $this->caseInReview();
        $orderId = (int) $case->order_log_id;
        $colleague = $this->erUser([CaseAccess::SHOW, CaseAccess::MANAGE], allStructures: true);
        $draftedBefore = count($this->draftedOrders);

        $result = $this->annul($case, $hr);

        $fresh = $result['case'];
        $this->assertTrue($result['cancellation_pending']);
        $this->assertSame(OrderCancellationState::Pending, $fresh->order_cancellation_status);
        $this->assertSame($orderId, (int) $fresh->order_cancellation_order_id);
        $this->assertNotNull($fresh->order_cancellation_requested_at);
        // Qərarın nəticəsi «tənbeh» olaraq qalmır, bağlı əmr isə yerində durur.
        $this->assertSame(DecisionOutcome::Unfounded, $fresh->decision_outcome);
        $this->assertSame($orderId, (int) $fresh->order_log_id);
        $this->assertCount($draftedBefore, $this->draftedOrders);
        $this->assertSame([], $this->fakeOrderStates);

        $this->assertDatabaseHas('employee_relation_case_events', ['case_id' => $case->id, 'action' => 'order_cancellation_pending']);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'employee_relations', 'event' => 'order_cancellation_pending', 'subject_id' => $case->id]);

        $kinds = $colleague->notifications()->get()->pluck('data.kind')->all();
        $this->assertContains('order_cancellation_pending', $kinds);
        $this->assertNotContains('order_cancellation_pending', $hr->notifications()->get()->pluck('data.kind')->all());
    }

    public function test_pending_case_cannot_be_closed_until_the_order_is_cancelled(): void
    {
        [$case, $hr] = $this->caseInReview();
        $this->annul($case, $hr);
        $case = $case->fresh();
        $this->assertSame(CaseStatus::Final, $case->status);

        try {
            app(CaseWorkflow::class)->transition($case, CaseStatus::Closed, $hr, 'Yekun');
            $this->fail('closing must be blocked while the order is not cancelled');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('transition', $exception->errors());
        }

        $this->assertSame(CaseStatus::Final, $case->fresh()->status);

        // Əmr Orders-da ləğv olunur → hadisə işi «tamamlandı»ya keçirir, iş bağlana bilir.
        $this->fakeOrderStates[(int) $case->order_log_id] = LinkedOrderCancellation::STATE_CANCELLED;
        event(new OrderStatusChanged((int) $case->order_log_id, 'X', 10, 30, 'none'));

        $done = $case->fresh();
        $this->assertSame(OrderCancellationState::Completed, $done->order_cancellation_status);
        $this->assertNotNull($done->order_cancellation_settled_at);
        $this->assertDatabaseHas('employee_relation_case_events', ['case_id' => $case->id, 'action' => 'order_cancellation_completed']);

        app(CaseWorkflow::class)->transition($done, CaseStatus::Closed, $hr, 'Yekun');
        $this->assertSame(CaseStatus::Closed, $case->fresh()->status);
    }

    public function test_completion_is_also_noticed_without_the_event_when_the_case_is_opened_or_closed(): void
    {
        [$case, $hr] = $this->caseInReview();
        $this->annul($case, $hr);

        // Əmr silinib: hadisə gəlməyib, işə baxanda vəziyyət özü yenilənir.
        $this->fakeOrderStates[(int) $case->order_log_id] = LinkedOrderCancellation::STATE_DELETED;

        Livewire::actingAs($hr)
            ->test(CaseDetail::class, ['case' => $case->id])
            ->assertSee(__('employee_relations::cases.order_cancellation.title.completed'));

        $this->assertSame(OrderCancellationState::Completed, $case->fresh()->order_cancellation_status);
    }

    public function test_exception_needs_the_dedicated_permission_and_a_reason_and_then_allows_closing(): void
    {
        [$case, $hr] = $this->caseInReview();
        $this->annul($case, $hr);
        $tracker = app(OrderCancellationTracker::class);

        // Adi idarəçi (manage) istisna təsdiq edə bilməz.
        $this->assertFalse($tracker->canWaive($hr, $case->fresh()));
        try {
            $tracker->waive($case->fresh(), $hr, 'Əmr qüvvədə saxlanılır, qərar rəhbərliklə razılaşdırılıb.');
            $this->fail('waive requires the permission');
        } catch (AuthorizationException) {
            $this->assertSame(OrderCancellationState::Pending, $case->fresh()->order_cancellation_status);
        }

        $structureId = (int) $case->structure_id;
        $director = $this->erUser([CaseAccess::SHOW, CaseAccess::MANAGE, OrderCancellationTracker::WAIVE_PERMISSION], [$structureId], name: 'Direktor');

        try {
            $tracker->waive($case->fresh(), $director, 'qısa');
            $this->fail('reason must be stated');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('waiveReason', $exception->errors());
        }

        Livewire::actingAs($director)
            ->test(CaseDetail::class, ['case' => $case->id])
            ->assertSee(__('employee_relations::cases.order_cancellation.title.pending'))
            ->set('waiveReason', 'Əmr qüvvədə saxlanılır, qərar rəhbərliklə razılaşdırılıb.')
            ->call('waiveOrderCancellation')
            ->assertHasNoErrors();

        $waived = $case->fresh();
        $this->assertSame(OrderCancellationState::Waived, $waived->order_cancellation_status);
        $this->assertSame($director->id, (int) $waived->order_cancellation_waived_by);
        $this->assertStringContainsString('rəhbərliklə razılaşdırılıb', (string) $waived->order_cancellation_waive_reason);
        $this->assertDatabaseHas('employee_relation_case_events', ['case_id' => $case->id, 'action' => 'order_cancellation_waived', 'actor_user_id' => $director->id]);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'employee_relations', 'event' => 'order_cancellation_waived', 'subject_id' => $case->id]);
        $this->assertContains('order_cancellation_waived', $hr->notifications()->get()->pluck('data.kind')->all());

        app(CaseWorkflow::class)->transition($waived, CaseStatus::Closed, $hr, 'Yekun');
        $this->assertSame(CaseStatus::Closed, $case->fresh()->status);
    }

    public function test_case_page_shows_the_warning_with_links_into_orders(): void
    {
        [$case, $hr] = $this->caseInReview();
        $this->annul($case, $hr);

        Livewire::actingAs($hr)
            ->test(CaseDetail::class, ['case' => $case->id])
            ->assertSee(__('employee_relations::cases.order_cancellation.title.pending'))
            ->assertSee(__('employee_relations::cases.order_cancellation.open_order'))
            ->assertSee('EMR-'.$case->order_log_id)
            ->assertSee(__('employee_relations::cases.order_cancellation.prepare_order'))
            ->assertDontSee(__('employee_relations::cases.order_cancellation.waive'));
    }

    public function test_dashboard_filters_cases_awaiting_order_cancellation(): void
    {
        [$pending, $hr] = $this->caseInReview();
        $this->annul($pending, $hr);
        $other = $this->erOpenCase($hr, $this->erPersonnel('Digərov', (int) $pending->structure_id), ['title' => 'Başqa şikayət işi']);

        Livewire::actingAs($hr)
            ->test(Dashboard::class)
            ->assertSee('Başqa şikayət işi')
            ->set('cancellationPending', true)
            ->assertSee($pending->label())
            ->assertDontSee('Başqa şikayət işi')
            ->assertSee(__('employee_relations::cases.order_cancellation.badge'));

        $this->assertNotNull($other->id);
    }

    public function test_appeals_that_do_not_annul_a_sanction_leave_orders_alone(): void
    {
        // Qüvvədə saxlanıldı: tənbeh də, əmr də yerində.
        [$case, $hr] = $this->caseInReview();
        $result = app(AppealService::class)->resolve($case->fresh(), $hr, RelationCaseAppeal::OUTCOME_UPHELD, 'Qərar əsaslıdır.');
        $this->assertFalse($result['cancellation_pending']);
        $this->assertNull($result['case']->order_cancellation_status);
        $this->assertSame(DecisionOutcome::Reprimand, $result['case']->decision_outcome);
        app(CaseWorkflow::class)->transition($result['case'], CaseStatus::Closed, $hr, 'Yekun');

        // Əmri olmayan (xəbərdarlıq) qərarın ləğvi: nəticə «əsassız», gözləmə yoxdur.
        [$warning, $hr2] = $this->caseInReview(DecisionOutcome::WrittenWarning);
        $this->assertNull($warning->order_log_id);
        $result = $this->annul($warning, $hr2);
        $this->assertFalse($result['cancellation_pending']);
        $this->assertNull($result['case']->order_cancellation_status);
        $this->assertSame(DecisionOutcome::Unfounded, $result['case']->decision_outcome);
        app(CaseWorkflow::class)->transition($result['case'], CaseStatus::Closed, $hr2, 'Yekun');
        $this->assertSame(CaseStatus::Closed, $warning->fresh()->status);
    }

    public function test_lowering_the_sanction_tracks_the_obsolete_order(): void
    {
        // Tənbeh xəbərdarlığa endirilir: əmr artıq əsassızdır.
        [$case, $hr] = $this->caseInReview();
        $orderId = (int) $case->order_log_id;
        $result = app(AppealService::class)->resolve($case->fresh(), $hr, RelationCaseAppeal::OUTCOME_MODIFIED, 'Yüngülləşdirildi.', DecisionOutcome::WrittenWarning);
        $this->assertTrue($result['cancellation_pending']);
        $this->assertSame($orderId, (int) $result['case']->order_cancellation_order_id);
        $this->assertSame(DecisionOutcome::WrittenWarning, $result['case']->decision_outcome);

        // Tənbehin başqa tənbehlə əvəzlənməsi (bir xətaya görə bir tənbeh, Md.189.1) bloklanır.
        [$second, $hr2] = $this->caseInReview();
        try {
            app(AppealService::class)->resolve($second->fresh(), $hr2, RelationCaseAppeal::OUTCOME_MODIFIED, 'Ağırlaşdırıldı.', DecisionOutcome::SevereReprimand);
            $this->fail('a second sanction for the same violation must be refused');
        } catch (ValidationException) {
            $this->assertNull($second->fresh()->order_cancellation_status);
        }
    }

    public function test_real_orders_cancellation_flow_completes_the_case_through_the_contract_and_event(): void
    {
        $this->app->bind(LinkedOrderCancellation::class, LinkedOrderCancellationLookup::class);

        [$case, $hr] = $this->caseInReview();
        $order = OrderLog::query()->create([
            'order_no' => 'ER-REAL-1',
            'order_type_id' => 3010,
            'given_date' => '2026-06-30 00:00:00',
            'given_by' => 'Test',
            'given_by_rank' => 'Test',
            'creator_id' => User::factory()->create()->id,
            'status_id' => OrderIssueService::STATUS_PENDING,
            'template_render_mode' => OrderIssueService::RENDER_MODE_DOCX,
            'template_snapshot' => [],
        ]);
        $case->forceFill(['order_log_id' => $order->id])->save();

        $transitions = app(OrderStatusTransitionService::class);
        $transitions->approve($order);

        $result = $this->annul($case, $hr);
        $this->assertTrue($result['cancellation_pending']);
        $this->assertSame(LinkedOrderCancellation::STATE_APPROVED, app(LinkedOrderCancellation::class)->stateOf($order->id));

        // Təsdiqlənmiş əmrin ləğvi Orders-un öz qaydasındadır: səbəb tələb olunur.
        $this->actingAs($hr);
        $transitions->cancel($order->fresh(), 'Apellyasiya nəticəsi ilə ləğv edildi.');

        $done = $case->fresh();
        $this->assertSame(OrderCancellationState::Completed, $done->order_cancellation_status);
        app(CaseWorkflow::class)->transition($done, CaseStatus::Closed, $hr, 'Yekun');
        $this->assertSame(CaseStatus::Closed, $case->fresh()->status);
    }
}
