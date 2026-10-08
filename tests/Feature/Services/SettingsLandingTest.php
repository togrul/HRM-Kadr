<?php

namespace Tests\Feature\Services;

use App\Models\User;
use App\Modules\Services\Livewire\Service;
use App\Modules\Services\Livewire\Settings\SettingsList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SettingsLandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_open_on_the_general_section_instead_of_an_empty_screen(): void
    {
        $this->actingAsAdmin();

        Livewire::test(Service::class)->assertSet('selectedService', 'general');
        Livewire::withQueryParams(['selectedService' => 'users'])->test(Service::class)->assertSet('selectedService', 'users');
    }

    public function test_organization_name_setting_has_a_localized_label(): void
    {
        $this->actingAsAdmin();
        app()->setLocale('az');

        $label = Livewire::test(SettingsList::class, ['section' => 'general'])->instance()->resolveSettingLabel('Organization name');

        $this->assertSame('Təşkilatın adı', $label);
    }

    private function actingAsAdmin(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo(Permission::findOrCreate('access-settings', 'web'));
        $this->actingAs($admin);
    }
}
