<?php

use App\Models\EducationDegree;
use App\Models\User;
use App\Modules\Notifications\Livewire\Notifications;
use App\Support\Ui\ContextPanelState;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/*
 * Shared UI behaviour from the 08.10.2026 audit: unsaved-changes guard on side panels,
 * one app-wide date field, stale validation errors, keyboard-friendly searchable selects,
 * auto-collapsing empty context panels, the bell badge and translation leftovers.
 */

class ClearsErrorProbe extends Component
{
    public string $name = '';

    public string $email = '';

    public function save(): void
    {
        $this->validate(['name' => 'required', 'email' => 'required']);
    }

    public function render(): string
    {
        return '<div>@error("name")<span>name-error</span>@enderror @error("email")<span>email-error</span>@enderror</div>';
    }
}

class RendersBladeProbe extends Component
{
    public static string $template = '<div></div>';

    public ?string $dateFrom = null;

    /** @var array<string, mixed> */
    public array $form = ['due' => null];

    public mixed $pick = null;

    public function render(): string
    {
        return '<div>'.self::$template.'</div>';
    }
}

function renderInLivewire(string $template): string
{
    RendersBladeProbe::$template = $template;

    return Livewire::test(RendersBladeProbe::class)->html();
}

class RevalidatesOnUpdateProbe extends Component
{
    public string $code = '';

    public function updatedCode(): void
    {
        $this->validateOnly('code', ['code' => 'min:3']);
    }

    public function render(): string
    {
        return '<div>@error("code")<span>code-error</span>@enderror</div>';
    }
}

it('guards a dirty side panel with the in-app dialog and resets scroll and focus on open', function (): void {
    $html = Blade::render('<x-side-modal :local-state="true"><input type="text" /></x-side-modal>');

    expect($html)
        ->toContain('window.hrmUnsavedGuard')
        ->toContain('texts: ')
        ->toContain(__('ui::common.unsaved.keep_editing'))
        ->toContain('trackDirty($event)')
        ->toContain('data-panel-scroll')
        ->toContain('resetPanelScroll')
        ->toContain('focusFirstField')
        ->not->toContain('confirm(');
});

it('lets a side panel opt out of the unsaved-changes guard', function (): void {
    $html = Blade::render('<x-side-modal :local-state="true" :guard-unsaved="false">x</x-side-modal>');

    expect($html)->toContain('enabled: false');
});

it('renders x-ui.input type=date as the shared localized date field, keeping the ISO binding', function (): void {
    $html = renderInLivewire('<x-ui.input type="date" wire:model.live="dateFrom" />');

    expect($html)
        ->not->toContain('type="date"')
        ->toContain('window.hrmDateField')
        ->toContain('data-date-input')
        ->toContain(".entangle('dateFrom').live")
        ->toContain(__('ui::date.placeholder'));
});

it('renders x-livewire-input type=date through the same date field', function (): void {
    $html = renderInLivewire('<x-livewire-input mode="gray" name="form.due" type="date" wire:model="form.due" />');

    expect($html)
        ->not->toContain('type="date"')
        ->toContain('window.hrmDateField')
        ->toContain(".entangle('form.due'),");
});

it('keeps x-pikaday-input syncing on change and adds the typing mask and limits', function (): void {
    $html = Blade::render('<x-pikaday-input name="d" wire:model.live="form.date" min="2026-01-01" />');

    expect($html)
        ->toContain('wire:model.change="form.date"')
        ->toContain('hrmMaskDateInput')
        ->toContain('maxlength="10"')
        ->toContain('2026-01-01T00:00:00');
});

it('publishes the calendar language for the app locale', function (): void {
    expect(__('ui::date.months'))->toHaveCount(12)
        ->and(__('ui::date.weekdays_short'))->toHaveCount(7)
        ->and(__('ui::date.months')[9])->toBe('Oktyabr');
});

it('marks validation messages and invalid fields so a correction clears them client-side', function (): void {
    $errors = new Illuminate\Support\ViewErrorBag;
    $errors->put('default', new Illuminate\Support\MessageBag(['title' => ['Required']]));
    view()->share('errors', $errors);

    expect(Blade::render('<x-validation>Required</x-validation>'))->toContain('data-field-error')
        ->and(Blade::render('<x-ui.input wire:model="title" />'))
        ->toContain('aria-invalid="true"')
        ->toContain('data-error-classes=')
        ->toContain('data-error-key="title"');
});

it('drops a field error as soon as that field changes, leaving the others', function (): void {
    Livewire::test(ClearsErrorProbe::class)
        ->call('save')
        ->assertHasErrors(['name', 'email'])
        ->set('name', 'Aysel')
        ->assertHasNoErrors('name')
        ->assertHasErrors('email')
        ->assertDontSee('name-error')
        ->assertSee('email-error');
});

it('still lets a component re-validate a field on update', function (): void {
    Livewire::test(RevalidatesOnUpdateProbe::class)
        ->set('code', 'ab')
        ->assertHasErrors('code')
        ->set('code', 'abcd')
        ->assertHasNoErrors('code');
});

it('gives the searchable select a keyboard: focusable search, listbox roles, Esc kept to itself', function (): void {
    $html = renderInLivewire('<x-ui.select-dropdown :searchable="true" :model="[[\'id\' => 1, \'label\' => \'Bir\']]" wire:model="pick" />');

    expect($html)
        ->toContain('x-ref="search"')
        ->toContain('role="listbox"')
        ->toContain('role="option"')
        ->toContain('onSearchKeydown($event)')
        ->toContain('keydown.escape.window.capture');
});

it('records whether a context panel has content', function (): void {
    $state = app(ContextPanelState::class);

    Blade::render('<x-context-panel title="İcazələr"></x-context-panel>');
    expect($state->pull())->toBeTrue();

    Blade::render('<x-context-panel title="İcazələr"><p>Filtr</p></x-context-panel>');
    expect($state->pull())->toBeFalse();

    expect($state->pull())->toBeFalse();
});

it('starts an empty context panel collapsed on the server, remembering the toggle per page', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('show-leaves', 'web'));

    $this->actingAs($user)
        ->get(route('leaves'))
        ->assertOk()
        ->assertSee('data-panel-empty', false)
        ->assertSee("var key = 'hrm.panel.leaves'", false)
        ->assertSee('var collapsed = true', false);
});

it('caps the bell badge at 9+ and keeps the exact count in the label', function (): void {
    $html = Blade::render('<x-ui.count-badge :count="12" />');

    expect($html)->toContain('9+')->toContain('data-count="12"')->toContain('min-w-4')->toContain('h-4');
    expect(Blade::render('<x-ui.count-badge :count="0" />'))->not->toContain('data-count');

    $user = User::factory()->create();
    foreach (range(1, 12) as $index) {
        DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\SystemNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => ['message' => 'm'.$index],
        ]);
    }

    $this->actingAs($user);

    Livewire::test(Notifications::class)
        ->assertSet('unreadTotal', 12)
        ->assertSee('9+')
        ->assertSee(trans_choice('notifications::common.labels.unread_count', 12, ['count' => 12]));
});

it('translates the performance workflow cards on the HR policy diagnostics screen', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('access-admin', 'web'));

    $this->actingAs($user)
        ->get(route('admin.hr-policy-diagnostics'))
        ->assertOk()
        ->assertSee('KPI kartları')
        ->assertSee('Varislik')
        ->assertDontSee('Kpi Scorecards')
        ->assertDontSee('Succession')
        ->assertDontSee('deployment')
        ->assertDontSee('Workflow');
});

it('normalizes education degree separators to an em dash, idempotently', function (): void {
    expect(EducationDegree::normalizeTitle('Ali təhsil - 1993-cü ilə qədər'))->toBe('Ali təhsil — 1993-cü ilə qədər')
        ->and(EducationDegree::normalizeTitle('Ali təhsil — 1997-ci ilə qədər'))->toBe('Ali təhsil — 1997-ci ilə qədər');

    DB::table('education_degrees')->insert(['id' => 991, 'title_az' => 'Ali təhsil - 1993-cü ilə qədər']);

    $migration = require database_path('migrations/2026_10_08_120000_normalize_education_degree_title_dashes.php');
    $migration->up();
    $migration->up();

    expect(DB::table('education_degrees')->where('id', 991)->value('title_az'))->toBe('Ali təhsil — 1993-cü ilə qədər');
});
