<?php

use App\Models\OrderLog;
use App\Models\OrderStatus;
use App\Models\User;
use App\Modules\Orders\Infrastructure\Document\OrderDeletionService;
use App\Modules\Orders\Infrastructure\Document\OrderIssueService;
use App\Modules\Orders\Livewire\AllOrders;
use App\Services\StructureService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/**
 * An approved order has already changed the employee record and reached payroll: it is
 * never deleted (soft or force) — it is cancelled first, which reverses its effect.
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

    $this->user = User::factory()->create();
    foreach (['show-orders', 'add-orders', 'delete-orders'] as $permission) {
        $this->user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    $this->actingAs($this->user);
});

function deletionGuardOrder(string $no, int $status): OrderLog
{
    return OrderLog::query()->create([
        'order_no' => $no,
        'given_date' => now(),
        'given_by' => 'Test',
        'given_by_rank' => '',
        'status_id' => $status,
        'template_render_mode' => OrderIssueService::RENDER_MODE_DOCX,
        'template_snapshot' => ['template_code' => 'ise_qebul', 'hire_structure_id' => 7],
    ]);
}

it('refuses to soft or force delete an approved order at service level', function (): void {
    $order = deletionGuardOrder('APP-1', 20);
    $service = app(OrderDeletionService::class);

    expect(fn () => $service->softDelete($order))->toThrow(DomainException::class, __('orders::order_list.messages.approved_not_deletable'))
        ->and(fn () => $service->forceDelete($order))->toThrow(DomainException::class);

    expect(OrderLog::query()->whereKey($order->id)->exists())->toBeTrue();

    $pending = deletionGuardOrder('PEN-1', 10);
    $service->softDelete($pending);
    expect($pending->fresh()->trashed())->toBeTrue();
});

it('denies delete and force delete of an approved order in the policy', function (): void {
    $approved = deletionGuardOrder('APP-2', 20);
    $pending = deletionGuardOrder('PEN-2', 10);
    $cancelled = deletionGuardOrder('CAN-2', 30);

    expect(Gate::forUser($this->user)->allows('delete', $approved))->toBeFalse()
        ->and(Gate::forUser($this->user)->allows('forceDelete', $approved))->toBeFalse()
        ->and(Gate::forUser($this->user)->allows('delete', $pending))->toBeTrue()
        ->and(Gate::forUser($this->user)->allows('delete', $cancelled))->toBeTrue()
        ->and(Gate::forUser($this->user)->allows('forceDelete', $cancelled))->toBeTrue();
});

it('shows a translated error instead of deleting an approved order from the list', function (): void {
    $order = deletionGuardOrder('APP-3', 20);

    Livewire::test(AllOrders::class)
        ->call('deleteOrder', 'APP-3')
        ->assertDispatched('orderError', __('orders::order_list.messages.approved_not_deletable'))
        ->assertNotDispatched('orderWasDeleted');

    expect($order->fresh()->trashed())->toBeFalse();
});

it('refuses to purge an approved order that is already in the trash', function (): void {
    $this->user->assignRole(\Spatie\Permission\Models\Role::findOrCreate('Admin', 'web'));
    $order = deletionGuardOrder('APP-4', 20);
    $order->delete(); // left over from before the guard existed

    Livewire::test(AllOrders::class)
        ->call('forceDeleteData', 'APP-4')
        ->assertDispatched('orderError');

    expect(OrderLog::withTrashed()->whereKey($order->id)->exists())->toBeTrue();
});

it('offers delete only for orders that are not approved', function (): void {
    deletionGuardOrder('PEN-5', 10);
    deletionGuardOrder('APP-5', 20);

    Livewire::test(AllOrders::class)
        ->assertSee("\$wire.deleteOrder('PEN-5')")
        ->assertDontSee("\$wire.deleteOrder('APP-5')");
});

it('reports soft-deleted approved orders without changing them', function (): void {
    $leftover = deletionGuardOrder('APP-6', 20);
    $leftover->delete();
    deletionGuardOrder('PEN-6', 10)->delete();
    deletionGuardOrder('APP-7', 20);

    expect(Artisan::call('orders:audit-deleted-approved', ['--json' => true]))->toBe(0);
    $report = json_decode(Artisan::output(), true);

    expect($report['count'])->toBe(1)
        ->and($report['orders'][0]['order_no'])->toBe('APP-6');

    expect($leftover->fresh()->trashed())->toBeTrue()
        ->and((int) $leftover->fresh()->status_id)->toBe(20);

    $this->artisan('orders:audit-deleted-approved')
        ->expectsOutputToContain('APP-6')
        ->assertExitCode(0);
});
