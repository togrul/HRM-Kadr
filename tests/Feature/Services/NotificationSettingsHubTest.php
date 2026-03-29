<?php

namespace Tests\Feature\Services;

use App\Models\NotificationCampaign;
use App\Models\NotificationDispatch;
use App\Models\NotificationRule;
use App\Models\NotificationTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class NotificationSettingsHubTest extends TestCase
{
    use RefreshDatabase;

    private function grantSettingsPermissions(User $user): void
    {
        $permissions = [
            'access-settings',
            'manage-notification-templates',
            'manage-notification-rules',
            'manage-notification-campaigns',
            'approve-notification-campaigns',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo($permissions);
    }

    public function test_notifications_settings_hub_component_renders_backlog_host(): void
    {
        $user = User::factory()->create();
        $this->grantSettingsPermissions($user);
        $this->actingAs($user);

        Livewire::test(\App\Modules\Notifications\Livewire\SettingsHub::class)
            ->assertSee('Bildirişlər modulu')
            ->assertSee('Ümumi baxış')
            ->assertSee('Analitika')
            ->assertSee('Qaydalar')
            ->assertSee(route('docs.guide', ['focus' => 'notifications']), false);
    }

    public function test_services_page_can_open_notifications_settings_section(): void
    {
        $user = User::factory()->create();
        $this->grantSettingsPermissions($user);

        $this->actingAs($user)
            ->get(route('services', ['selectedService' => 'notifications-settings']))
            ->assertOk()
            ->assertSee('Bildirişlər modulu')
            ->assertSee('Ümumi baxış');
    }

    public function test_notifications_settings_hub_renders_live_summary_stats(): void
    {
        $user = User::factory()->create();
        $this->grantSettingsPermissions($user);
        $this->actingAs($user);

        NotificationTemplate::query()->create([
            'key' => 'birthday.mail',
            'category' => 'birthday',
            'channel' => 'mail',
            'format' => 'html',
            'body_template' => '<p>Happy birthday</p>',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        NotificationRule::query()->create([
            'category' => 'birthday',
            'trigger' => 'birthday_due',
            'channel' => 'mail',
            'audience_config' => ['targets' => ['all_employees']],
            'approval_required' => false,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $campaign = NotificationCampaign::query()->create([
            'category' => 'birthday',
            'trigger' => 'birthday_due',
            'title' => 'Mart ad günü dalğası',
            'format' => 'html',
            'status' => 'queued',
            'approval_status' => 'pending',
            'created_by' => $user->id,
        ]);

        NotificationDispatch::query()->create([
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'channel' => 'mail',
            'status' => 'failed',
        ]);

        Livewire::test(\App\Modules\Notifications\Livewire\OverviewPanel::class)
            ->assertSee('Başlanğıc axınları')
            ->assertSee('Şablon önizləməsi')
            ->assertSee('Qayda önizləməsi')
            ->assertSee('Növbədə olan kampaniyalar')
            ->assertSee('Uğursuz göndərişlər')
            ->assertSee('birthday.mail')
            ->assertSee('Ad günü / Ad günü vaxtı')
            ->assertSee('Mart ad günü dalğası')
            ->assertSee('1');
    }

    public function test_notifications_settings_hub_can_seed_birthday_and_position_change_starters(): void
    {
        $user = User::factory()->create();
        $this->grantSettingsPermissions($user);
        $this->actingAs($user);

        Livewire::test(\App\Modules\Notifications\Livewire\OverviewPanel::class)
            ->call('seedBirthdayStarter')
            ->call('seedPositionChangeStarter')
            ->call('seedEmploymentStartedStarter')
            ->call('seedHolidayStarter')
            ->assertSee('Ad günü başlanğıc şablonu')
            ->assertSee('Vəzifə dəyişikliyi başlanğıc şablonu')
            ->assertSee('İşə başlayan əməkdaş başlanğıc şablonu')
            ->assertSee('Bayram / tətil başlanğıc şablonu');

        $this->assertDatabaseHas('notification_templates', [
            'key' => 'birthday.default',
            'category' => 'birthday',
        ]);

        $this->assertDatabaseHas('notification_templates', [
            'key' => 'position-change.default',
            'category' => 'position_change',
        ]);

        $this->assertDatabaseHas('notification_rules', [
            'category' => 'birthday',
            'trigger' => 'birthday_due',
        ]);

        $this->assertDatabaseHas('notification_rules', [
            'category' => 'position_change',
            'trigger' => 'position_changed',
        ]);

        $this->assertDatabaseHas('notification_rules', [
            'category' => 'employment_started',
            'trigger' => 'employment_started',
        ]);

        $this->assertDatabaseHas('notification_rules', [
            'category' => 'holiday',
            'trigger' => 'holiday_due',
        ]);
    }
}
