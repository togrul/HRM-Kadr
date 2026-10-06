<?php

use App\Models\LeaveType;
use App\Models\User;
use App\Modules\Admin\Livewire\LeaveTypes;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/*
 * Admin CRUD feedback used to go through SweetAlert2 ('swal' / 'delete-prompt' →
 * 'goOn-Delete'). It now rides the app-wide toast and the global confirm modal.
 */

beforeEach(function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('access-admin', 'web'));
    $this->actingAs($user);
});

it('toasts a successful save through notify', function (): void {
    Livewire::test(LeaveTypes::class)
        ->call('openCrud')
        ->set('form.name', 'Saatlıq icazə')
        ->set('form.attendance_code', 'SI')
        ->set('form.max_days', 3)
        ->call('store')
        ->assertHasNoErrors()
        ->assertDispatched('notify', type: 'success', message: __('admin::common.alerts.success.text'))
        ->assertNotDispatched('swal');
});

it('asks the global confirm modal to call this component\'s delete, then toasts the deletion', function (): void {
    $type = LeaveType::query()->create(['name' => 'İllik', 'attendance_code' => 'ILL', 'max_days' => 21, 'requires_document' => false]);

    $component = Livewire::test(LeaveTypes::class);

    $component->call('deleteModel', $type->id)
        ->assertDispatched('confirm-action', fn (string $name, array $params): bool => $params['wireId'] === $component->id()
            && $params['method'] === 'delete'
            && $params['title'] === __('admin::common.alerts.delete_prompt.title'))
        ->assertNotDispatched('delete-prompt');

    expect(LeaveType::query()->whereKey($type->id)->exists())->toBeTrue();

    $component->call('delete')
        ->assertDispatched('notify', type: 'success', message: __('ui::common.messages.record_deleted'));

    expect(LeaveType::query()->whereKey($type->id)->exists())->toBeFalse();
});
