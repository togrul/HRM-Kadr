<?php

use App\Modules\Benefits\Application\Services\BenefitsAvailability;
use App\Modules\Benefits\Livewire\Dashboard;
use App\Modules\Benefits\Livewire\ModuleSwitch;
use App\Modules\Personnel\Support\MyHr\MyHrTabs;
use App\Services\Modules\ModuleState;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\Benefits\Concerns\BuildsBenefitsFixtures;

/*
 * Şirkət növü (quraşdırma profili) → modulun standart vəziyyəti; adminin əl ilə seçimi;
 * vəziyyətin menyu/ModuleState/Şəxsi kabinet/marşruta yayılması.
 */

uses(BuildsBenefitsFixtures::class);

// Lokal .env-də APP_TYPE fərqli ola bilər (məs. PUBLIC) — testlər özəl şirkətdən başlayır.
beforeEach(fn () => $this->useProfile('private'));

it('is on by default for private and corporate profiles and off for public and military ones', function (string $profile, bool $expected): void {
    $this->useProfile($profile);

    $availability = app(BenefitsAvailability::class);

    expect($availability->profileDefault())->toBe($expected)
        ->and($availability->enabled())->toBe($expected);
})->with([
    'default (korporativ)' => ['default', true],
    'private (özəl)' => ['private', true],
    'public (Publik / dövlət)' => ['public', false],
    'military (hərbi)' => ['military', false],
]);

it('lets the admin override the company type default in both directions', function (): void {
    $this->useProfile('public');
    $availability = app(BenefitsAvailability::class);

    $availability->setOverride(true);
    expect($availability->enabled())->toBeTrue()
        ->and(DB::table('settings')->where('name', BenefitsAvailability::SETTING)->value('value'))->toBe('1');

    $availability->setOverride(null);
    expect($availability->enabled())->toBeFalse()
        ->and(DB::table('settings')->where('name', BenefitsAvailability::SETTING)->exists())->toBeFalse();

    $this->useProfile('private');
    $availability = app(BenefitsAvailability::class);
    $availability->setOverride(false);
    expect($availability->enabled())->toBeFalse();

    expect(Activity::query()->where('log_name', 'benefits')->where('event', 'benefits_module_toggled')->count())->toBe(3);
});

it('hides the screen, the menu module and the My HR tab once the module is switched off', function (): void {
    $this->seedBenefitsReferenceData();
    $user = $this->benefitsUser(['show-benefits']);

    $this->actingAs($user)->get(route('benefits'))->assertOk();
    expect(app(ModuleState::class)->enabled('benefits'))->toBeTrue()
        ->and(MyHrTabs::all())->toContain('benefits');

    app(BenefitsAvailability::class)->setOverride(false);

    $this->actingAs($user)->get(route('benefits'))->assertNotFound();
    expect(app(ModuleState::class)->enabled('benefits'))->toBeFalse()
        ->and(MyHrTabs::all())->not->toContain('benefits');
});

it('keeps a public-sector installation closed until the admin opens it', function (): void {
    $this->useProfile('public');
    $user = $this->benefitsUser(['show-benefits']);

    $this->actingAs($user)->get(route('benefits'))->assertNotFound();

    app(BenefitsAvailability::class)->setOverride(true);

    $this->actingAs($user)->get(route('benefits'))->assertOk();
});

it('lets only an admin use the module switch', function (): void {
    $this->actingAs($this->benefitsUser(['show-benefits', 'manage-benefits']));
    Livewire::test(ModuleSwitch::class)->assertForbidden();

    $this->actingAs($this->benefitsUser(['access-admin']));
    Livewire::test(ModuleSwitch::class)
        ->call('setMode', 'off')
        ->assertSee(__('benefits::dashboard.module.disabled'));

    expect(app(BenefitsAvailability::class)->override())->toBeFalse();

    Livewire::test(ModuleSwitch::class)->call('setMode', 'auto');
    expect(app(BenefitsAvailability::class)->override())->toBeNull();
});

it('shows the switch on the HR policy diagnostics screen', function (): void {
    $this->actingAs($this->benefitsUser(['access-admin']))
        ->get(route('admin.hr-policy-diagnostics'))
        ->assertOk()
        ->assertSee(__('benefits::dashboard.module.title'));
});

it('requires the view permission and hides the cost tab without its own permission', function (): void {
    $this->actingAs($this->benefitsUser([]));
    Livewire::test(Dashboard::class)->assertForbidden();

    $this->actingAs($this->benefitsUser(['show-benefits']));
    expect(Livewire::test(Dashboard::class)->instance()->allowedTabsList)->not->toContain('costs');

    $this->actingAs($this->benefitsUser(['show-benefits', 'show-benefit-costs']));
    expect(Livewire::test(Dashboard::class)->instance()->allowedTabsList)->toContain('costs');
});

it('seeds its permissions with descriptions and the menu row', function (): void {
    foreach (['show-benefits', 'manage-benefits', 'show-benefit-costs'] as $permission) {
        expect(DB::table('permissions')->where('name', $permission)->value('description'))
            ->not->toBeNull()
            ->not->toBe(__('permission_descriptions.fallback', [], 'az'));
    }

    expect(DB::table('menus')->where('url', 'benefits')->exists())->toBeTrue()
        ->and(__('services::permissions.groups.benefits'))->toBe('İmtiyazlar')
        ->and(collect(config('menus.global'))->pluck('url'))->toContain('benefits');
});

it('follows an edit of the setting row from the generic settings screen', function (): void {
    $availability = app(BenefitsAvailability::class);
    $availability->setOverride(true);
    expect($availability->override())->toBeTrue();

    // Tənzimləmələr ekranı sətri birbaşa model ilə yeniləyir — keşlənmiş dəyər köhnəlməməlidir.
    \App\Models\Setting::query()->where('name', BenefitsAvailability::SETTING)->firstOrFail()->update(['value' => '0']);
    $availability->forget();

    expect($availability->override())->toBeFalse()
        ->and($availability->enabled())->toBeFalse();
});
