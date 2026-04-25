<?php

namespace Tests\Feature\Candidates;

use App\Models\AppealStatus;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CandidateListPresetSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget(\App\Modules\Candidates\Livewire\CandidateList::SETTINGS_CACHE_KEY);
        Cache::forget('appeal-statuses:'.app()->getLocale());
        config()->set('candidates.mode', 'military');
        config()->set('candidates.workflow_pack', 'military');
    }

    public function test_status_tabs_stay_stable_after_livewire_status_change(): void
    {
        $user = $this->authorizedUser();
        $locale = app()->getLocale();

        AppealStatus::query()->create(['id' => 10, 'locale' => $locale, 'name' => 'Status A']);
        AppealStatus::query()->create(['id' => 20, 'locale' => $locale, 'name' => 'Status B']);
        AppealStatus::query()->create(['id' => 30, 'locale' => $locale, 'name' => 'Status C']);
        AppealStatus::query()->create(['id' => 40, 'locale' => $locale, 'name' => 'Hidden Status']);

        Setting::query()->create([
            'name' => 'candidates.list_presets.military.status_whitelist',
            'value' => json_encode([10, 20, 30], JSON_UNESCAPED_UNICODE),
            'type' => 'string',
        ]);

        $this->actingAs($user);

        $component = Livewire::test(\App\Modules\Candidates\Livewire\CandidateList::class);

        $this->assertSame(
            ['Status A', 'Status B', 'Status C'],
            $component->instance()->appealStatusTabs->pluck('name')->all()
        );

        $component
            ->call('setStatus', 20)
            ->assertSet('status', 20);

        $this->assertSame(
            ['Status A', 'Status B', 'Status C'],
            $component->instance()->appealStatusTabs->pluck('name')->all()
        );

        $component
            ->call('setStatus', 'all')
            ->assertSet('status', 'all');

        $this->assertSame(
            ['Status A', 'Status B', 'Status C'],
            $component->instance()->appealStatusTabs->pluck('name')->all()
        );
    }

    public function test_default_status_and_enabled_filters_can_be_overridden_from_settings(): void
    {
        $user = $this->authorizedUser();
        $locale = app()->getLocale();

        AppealStatus::query()->create(['id' => 10, 'locale' => $locale, 'name' => 'Status A']);
        AppealStatus::query()->create(['id' => 20, 'locale' => $locale, 'name' => 'Status B']);

        Setting::query()->create([
            'name' => 'candidates.list_presets.military.default_status',
            'value' => '20',
            'type' => 'string',
        ]);
        Setting::query()->create([
            'name' => 'candidates.list_presets.military.enabled_filters',
            'value' => json_encode(['fullname', 'gender'], JSON_UNESCAPED_UNICODE),
            'type' => 'string',
        ]);

        $this->actingAs($user);

        Livewire::test(\App\Modules\Candidates\Livewire\CandidateList::class)
            ->assertSet('status', 20)
            ->assertSee(__('candidates::common.labels.fullname'))
            ->assertSee(__('candidates::common.labels.gender'))
            ->assertDontSee(__('candidates::common.labels.test_results'))
            ->assertDontSee(__('candidates::common.labels.age'))
            ->assertDontSee(__('candidates::common.labels.appeal_date'));
    }

    private function authorizedUser(): User
    {
        $permission = Permission::findOrCreate('show-candidates', 'web');

        $user = User::factory()->create();
        $user->givePermissionTo($permission);

        return $user;
    }
}
