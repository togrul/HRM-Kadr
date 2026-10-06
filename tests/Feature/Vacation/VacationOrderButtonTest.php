<?php

use App\Models\OrderWordTemplate;
use App\Models\User;
use App\Modules\Vacation\Livewire\Vacations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function vacationViewer(array $permissions): User
{
    $user = User::factory()->create();
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $user->givePermissionTo($permissions);

    return $user;
}

function vacationTemplate(string $code, string $effect = 'vacation'): void
{
    OrderWordTemplate::create(['code' => $code, 'label' => ucfirst($code), 'effect' => $effect, 'docx_path' => 'x.docx', 'variables' => [], 'is_active' => true]);
}

it('opens the order composer on the vacation template in a side panel on the same page', function (): void {
    $this->actingAs(vacationViewer(['show-vacations', 'add-orders']));
    vacationTemplate('annual');
    vacationTemplate('award', 'award');

    Livewire::test(Vacations::class)
        ->assertSet('vacationOrderTemplates', ['annual' => 'Annual'])
        ->assertSee(__('vacation::common.actions.vacation_order'))
        ->assertSeeHtml('openSideMenu(&#039;order-composer&#039;, &#039;annual&#039;)')
        ->call('openSideMenu', 'order-composer', 'annual')
        ->assertSet('showSideMenu', 'order-composer')
        ->assertSeeLivewire('orders.order-composer');
});

it('lists several vacation templates and falls back to the plain composer without one', function (): void {
    $this->actingAs(vacationViewer(['show-vacations', 'add-orders']));

    Livewire::test(Vacations::class)->assertSeeHtml('openSideMenu(&#039;order-composer&#039;, &#039;&#039;)');

    vacationTemplate('annual');
    vacationTemplate('unpaid');

    Livewire::test(Vacations::class)
        ->assertSeeHtml('openSideMenu(\'order-composer\', \'annual\')')
        ->assertSeeHtml('openSideMenu(\'order-composer\', \'unpaid\')');
});

it('hides the button from users who cannot issue orders', function (): void {
    $this->actingAs(vacationViewer(['show-vacations']));

    Livewire::test(Vacations::class)->assertDontSee(__('vacation::common.actions.vacation_order'));
});
