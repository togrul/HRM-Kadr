<?php

use App\Models\OrderLog;
use App\Models\OrderStatus;
use App\Models\OrderWordTemplate;
use App\Models\PayrollPeriod;
use App\Models\Personnel;
use App\Models\User;
use App\Modules\Orders\Infrastructure\Document\Effects\OrderCancellationEffect;
use App\Modules\Orders\Infrastructure\Document\OrderIssueService;
use App\Modules\Orders\Infrastructure\Document\OrderStatusTransitionService;
use App\Services\StructureService;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;

/*
 * Əmr status keçidlərinin bütövlüyü (H3, M1, M2, M3): eyni əmr iki dəfə təsdiqlənmir, effektin
 * toxunduğu hər ay yoxlanılır, bağlı aya təsdiq olunmur, əmrin ləğvi revert-orders tələb edir.
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

/**
 * @param  array<string,string>  $roles  effect role => raw field value
 */
function integrityOrder(string $no, string $effect, array $roles, int $status, string $givenDate = '2026-05-10'): OrderLog
{
    $variables = [];
    $fields = [];
    $index = 1;
    foreach ($roles as $role => $value) {
        $token = 'var_'.$index++;
        $variables[] = ['token' => $token, 'label' => $role, 'source' => 'manual', 'field' => ['key' => $token, 'type' => 'date'], 'effect_role' => $role];
        $fields[$token] = $value;
    }

    OrderWordTemplate::query()->updateOrCreate(['code' => 'integrity_'.$no], [
        'label' => 'Test',
        'effect' => $effect,
        'docx_path' => 'order-templates/none.docx',
        'variables' => $variables,
        'is_active' => true,
    ]);

    return OrderLog::query()->create([
        'order_no' => $no,
        'given_date' => $givenDate.' 09:00:00',
        'given_by' => 'Test',
        'given_by_rank' => '',
        'status_id' => $status,
        'template_render_mode' => OrderIssueService::RENDER_MODE_DOCX,
        'template_snapshot' => ['template_code' => 'integrity_'.$no, 'hire_structure_id' => 7, 'fields' => $fields],
    ]);
}

function integrityClosePeriod(int $year, int $month): void
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

it('approves an order once even when two requests hold the same pending copy', function (): void {
    $order = integrityOrder('H3-1', 'none', ['start_date' => '12.05.2026'], 10);
    $first = OrderLog::query()->findOrFail($order->id);
    $second = OrderLog::query()->findOrFail($order->id);
    $transitions = app(OrderStatusTransitionService::class);

    $transitions->approve($first);

    expect(fn () => $transitions->approve($second))->toThrow(DomainException::class, __('orders::order_composer.errors.status_changed'))
        ->and(Activity::query()->where('log_name', 'orders')->where('event', 'approved')->count())->toBe(1)
        ->and((int) $order->fresh()->status_id)->toBe(20);

    // A stale copy cannot cancel what someone else already moved either.
    $stale = OrderLog::query()->findOrFail($order->id);
    $transitions->revert(OrderLog::query()->findOrFail($order->id), 'Səhv tərtib edilib');
    expect(fn () => $transitions->cancel($stale, 'Səhv tərtib edilib'))->toThrow(DomainException::class);
    expect((int) $order->fresh()->status_id)->toBe(10);
});

it('checks the effective date of a salary change, not only start dates, before reverting', function (): void {
    integrityClosePeriod(2026, 4);
    $order = integrityOrder('M1-1', 'salary_change', ['effective_date' => '01.04.2026'], 20, '2026-05-10');

    expect(fn () => app(OrderStatusTransitionService::class)->revert($order, 'Səhv tərtib edilib'))
        ->toThrow(DomainException::class, __('orders::order_composer.errors.period_closed', [
            'period' => '04.2026',
            'reason' => __('orders::order_composer.errors.period_closed_by.payroll'),
        ]));

    expect((int) $order->fresh()->status_id)->toBe(20);
});

it('checks every month a leave spans, including its end month', function (): void {
    integrityClosePeriod(2026, 3);
    $order = integrityOrder('M1-2', 'vacation', ['start_date' => '25.02.2026', 'end_date' => '05.03.2026'], 20, '2026-02-20');

    expect(app(OrderStatusTransitionService::class)->effectMonths($order))->toHaveCount(2);
    expect(fn () => app(OrderStatusTransitionService::class)->cancel($order, 'Səhv tərtib edilib'))->toThrow(DomainException::class);
    expect((int) $order->fresh()->status_id)->toBe(20);
});

it('refuses to approve an order into a closed month', function (): void {
    integrityClosePeriod(2026, 6);
    $order = integrityOrder('M2-1', 'none', ['start_date' => '10.06.2026'], 10, '2026-07-01');

    expect(fn () => app(OrderStatusTransitionService::class)->approve($order))
        ->toThrow(DomainException::class, __('orders::order_composer.errors.period_closed_approve', [
            'period' => '06.2026',
            'reason' => __('orders::order_composer.errors.period_closed_by.payroll'),
        ]));

    expect((int) $order->fresh()->status_id)->toBe(10);
});

it('requires revert-orders to approve or reverse an order cancellation', function (): void {
    $this->actingAs(User::factory()->create());
    $effect = app(OrderCancellationEffect::class);

    expect(fn () => $effect->apply(new OrderLog, ['target_order' => 1], new Personnel))
        ->toThrow(DomainException::class, __('orders::order_composer.errors.cancellation_forbidden'))
        ->and(fn () => $effect->reverse(new OrderLog, [], new Personnel))
        ->toThrow(DomainException::class, __('orders::order_composer.errors.cancellation_forbidden'));

    $reverter = User::factory()->create();
    $reverter->givePermissionTo(Permission::findOrCreate('revert-orders', 'web'));
    $this->actingAs($reverter);

    // With the permission the effect goes on to its own checks (here: the target is missing).
    expect(fn () => $effect->apply(new OrderLog, ['target_order' => 0], new Personnel))
        ->toThrow(DomainException::class, __('orders::order_composer.errors.cancellation_target_missing'));
});
