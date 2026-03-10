<?php

namespace Tests\Feature\Services;

use App\Models\AppealStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CandidateSettingsSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_general_section_does_not_render_candidate_presets_panel(): void
    {
        Livewire::test(\App\Modules\Services\Livewire\Settings\SettingsList::class, ['section' => 'general'])
            ->assertDontSee(__('services::settings.labels.candidate_status_whitelist_presets'))
            ->assertDontSee(__('services::settings.actions.save_presets'));
    }

    public function test_candidate_section_renders_candidate_preset_panel(): void
    {
        AppealStatus::query()->create([
            'id' => 10,
            'locale' => app()->getLocale(),
            'name' => 'Baxılır',
        ]);

        Livewire::test(\App\Modules\Services\Livewire\Settings\SettingsList::class, ['section' => 'candidate'])
            ->assertSee(__('services::settings.labels.candidate_status_whitelist_presets'))
            ->assertSee(__('services::settings.actions.save_presets'))
            ->assertSee('Baxılır');
    }
}
