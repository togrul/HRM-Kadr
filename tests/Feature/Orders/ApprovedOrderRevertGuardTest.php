<?php

use App\Models\FinancePeriodState;
use App\Models\OrderLog;
use App\Models\OrderStatus;
use App\Models\OrderWordTemplate;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Modules\Orders\Infrastructure\Document\OrderIssueService;
use App\Modules\Orders\Infrastructure\Document\OrderStatusTransitionService;
use App\Modules\Orders\Livewire\AllOrders;
use App\Modules\Payroll\Contracts\ClosedPeriodCheck;
use App\Services\StructureService;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Taking an order out of the approved state (revert or cancel) undoes an effect payroll may
 * already have used: it needs revert-orders, a written reason, and an open pay period.
 */
beforeEach(function (): void {
    foreach ([[10, 'Təsdiq gözləyən'], [20, 'Təsdiqlənmiş'], [30, 'Ləğv edilmiş']] as [$id, $name]) {
        OrderStatus::query()->firstOrCreate(['id' => $id], ['locale' => 'az', 'name' => $name]);
    }

    $this->app->instance(StructureService::class, new class extends StructureService
    {
        public function __construct() {}

        public function getAccessibleStructures(?User $user = null): array
        {
            return [7];
        }
    });

    config(['integration.payroll_owner' => 'self']);
});

/** An approved order with no HR effect, dated by given_date (and optionally a start date field). */
function revertGuardOrder(string $no, string $givenDate = '2026-03-10', ?string $startDate = null): OrderLog
{
    $snapshot = ['template_code' => 'revert_guard', 'hire_structure_id' => 7, 'fields' => []];

    if ($startDate !== null) {
        OrderWordTemplate::query()->firstOrCreate(['code' => 'revert_guard'], [
            'label' => 'Test',
            'effect' => 'none',
            'docx_path' => 'order-templates/none.docx',
            'variables' => [['token' => 'var_1', 'label' => 'Başlama', 'source' => 'manual', 'field' => ['key' => 'var_1', 'type' => 'date'], 'effect_role' => 'start_date']],
            'is_active' => true,
        ]);
        $snapshot['fields'] = ['var_1' => $startDate];
    }

    return OrderLog::query()->create([
        'order_no' => $no,
        'given_date' => $givenDate.' 09:00:00',
        'given_by' => 'Test',
        'given_by_rank' => '',
        'status_id' => 20,
        'template_render_mode' => OrderIssueService::RENDER_MODE_DOCX,
        'template_snapshot' => $snapshot,
    ]);
}

function revertGuardUser(array $permissions): User
{
    $user = User::factory()->create();
    foreach ($permissions as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    return $user;
}

function closePayrollPeriod(int $year, int $month): void
{
    PayrollPeriod::query()->create([
        'code' => sprintf('%04d-%02d', $year, $month),
        'year' => $year,
        'month' => $month,
        'starts_on' => sprintf('%04d-%02d-01', $year, $month),
        'ends_on' => sprintf('%04d-%02d-28', $year, $month),
        'currency' => 'AZN',
        'status' => 'closed',
    ]);
}

it('requires a reason of at least five characters to revert or cancel an approved order', function (): void {
    $transitions = app(OrderStatusTransitionService::class);
    $order = revertGuardOrder('RG-1');

    expect(fn () => $transitions->revert($order))->toThrow(DomainException::class, __('orders::order_composer.errors.reason_required', ['min' => 5]))
        ->and(fn () => $transitions->revert($order, '  abc  '))->toThrow(DomainException::class)
        ->and(fn () => $transitions->cancel($order))->toThrow(DomainException::class);

    expect((int) $order->fresh()->status_id)->toBe(20);

    $transitions->revert($order, 'Səhv işçi seçilib');

    expect((int) $order->fresh()->status_id)->toBe(10);
    $entry = Activity::query()->where('log_name', 'orders')->where('event', 'reverted')->latest('id')->first();
    expect($entry->getExtraProperty('reason'))->toBe('Səhv işçi seçilib');
});

it('cancels a pending order without a reason', function (): void {
    $order = revertGuardOrder('RG-2');
    $order->update(['status_id' => 10]);

    app(OrderStatusTransitionService::class)->cancel($order);

    expect((int) $order->fresh()->status_id)->toBe(30);
});

it('blocks leaving the approved state once our payroll period is closed', function (): void {
    closePayrollPeriod(2026, 3);
    $order = revertGuardOrder('RG-3', '2026-03-10');

    expect(fn () => app(OrderStatusTransitionService::class)->revert($order, 'Səhv tərtib edilib'))
        ->toThrow(DomainException::class, __('orders::order_composer.errors.period_closed', [
            'period' => '03.2026',
            'reason' => __('orders::order_composer.errors.period_closed_by.payroll'),
        ]));

    expect(fn () => app(OrderStatusTransitionService::class)->cancel($order, 'Səhv tərtib edilib'))->toThrow(DomainException::class);
    expect((int) $order->fresh()->status_id)->toBe(20);

    // Another month is still open.
    $open = revertGuardOrder('RG-3B', '2026-04-02');
    app(OrderStatusTransitionService::class)->cancel($open, 'Səhv tərtib edilib');
    expect((int) $open->fresh()->status_id)->toBe(30);
});

it('uses the start date of the order rather than the day it was given', function (): void {
    closePayrollPeriod(2026, 2);
    $order = revertGuardOrder('RG-4', '2026-03-10', '15.02.2026');

    expect(app(OrderStatusTransitionService::class)->effectiveDate($order)->format('Y-m-d'))->toBe('2026-02-15');
    expect(fn () => app(OrderStatusTransitionService::class)->revert($order, 'Səhv tərtib edilib'))->toThrow(DomainException::class);
});

it('follows the finance system period when payroll belongs to finance', function (): void {
    config(['integration.payroll_owner' => 'finance']);
    closePayrollPeriod(2026, 3); // ours no longer decides
    $order = revertGuardOrder('RG-5', '2026-03-10');

    expect(app(ClosedPeriodCheck::class)->closedBy(now()->setDate(2026, 3, 10)))->toBeNull();

    FinancePeriodState::query()->create(['year' => 2026, 'month' => 3, 'closed' => true]);

    expect(fn () => app(OrderStatusTransitionService::class)->revert($order, 'Səhv tərtib edilib'))
        ->toThrow(DomainException::class, __('orders::order_composer.errors.period_closed_by.finance'));
});

it('blocks leaving the approved state when the attendance month is locked', function (): void {
    DB::table('attendance_monthly_summaries')->insert([
        'tabel_no' => 'ATT-1', 'year' => 2026, 'month' => 3, 'is_locked' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $order = revertGuardOrder('RG-6', '2026-03-10');

    expect(fn () => app(OrderStatusTransitionService::class)->revert($order, 'Səhv tərtib edilib'))
        ->toThrow(DomainException::class, __('orders::order_composer.errors.period_closed_by.attendance'));
});

it('needs revert-orders in the list, not add-orders', function (): void {
    revertGuardOrder('RG-7');

    $this->actingAs(revertGuardUser(['show-orders', 'add-orders']));
    Livewire::test(AllOrders::class)
        ->assertDontSee("revertOrder('RG-7'")
        ->call('revertOrder', 'RG-7', 'Səhv tərtib edilib')
        ->assertForbidden();
    expect((int) OrderLog::where('order_no', 'RG-7')->value('status_id'))->toBe(20);

    $this->actingAs(revertGuardUser(['show-orders', 'revert-orders']));
    Livewire::test(AllOrders::class)
        ->assertSee("revertOrder('RG-7'")
        ->call('revertOrder', 'RG-7', '')
        ->assertDispatched('orderError')
        ->call('revertOrder', 'RG-7', 'Səhv tərtib edilib')
        ->assertDispatched('orderAdded');
    expect((int) OrderLog::where('order_no', 'RG-7')->value('status_id'))->toBe(10);
});

it('grants revert-orders to Admin and HR Admin on existing installs', function (): void {
    foreach (['Admin', 'HR Admin', 'HR Employee'] as $role) {
        Role::findOrCreate($role, 'web');
    }

    $migration = require base_path('app/Modules/Orders/Database/Migrations/2026_10_09_120000_add_revert_orders_permission.php');
    $migration->up();
    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    expect(Role::findByName('Admin', 'web')->hasPermissionTo('revert-orders'))->toBeTrue()
        ->and(Role::findByName('HR Admin', 'web')->hasPermissionTo('revert-orders'))->toBeTrue()
        ->and(Role::findByName('HR Employee', 'web')->hasPermissionTo('revert-orders'))->toBeFalse();
});
