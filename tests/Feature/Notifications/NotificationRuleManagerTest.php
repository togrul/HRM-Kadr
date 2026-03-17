<?php

namespace Tests\Feature\Notifications;

use App\Models\NotificationRule;
use App\Models\NotificationTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class NotificationRuleManagerTest extends TestCase
{
    use RefreshDatabase;

    private function grantRulePermissions(User $user): void
    {
        Permission::findOrCreate('access-settings', 'web');
        Permission::findOrCreate('manage-notification-rules', 'web');
        $user->givePermissionTo(['access-settings', 'manage-notification-rules']);
    }

    public function test_rule_manager_can_create_update_and_delete_rule(): void
    {
        $user = User::factory()->create();
        $this->grantRulePermissions($user);
        $this->actingAs($user);

        $template = NotificationTemplate::query()->create([
            'key' => 'birthday.mail',
            'category' => 'birthday',
            'channel' => 'mail',
            'format' => 'html',
            'body_template' => '<p>Salam</p>',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $component = Livewire::test(\App\Modules\Notifications\Livewire\RuleManager::class)
            ->set('form.category', 'birthday')
            ->set('form.trigger', 'birthday_due')
            ->set('form.template_id', $template->id)
            ->set('form.channel', 'mail')
            ->set('form.audience_targets', 'employee, department, specific_users')
            ->set('form.structure_ids', [5, 9])
            ->set('form.user_ids', [2, 17])
            ->set('form.approval_required', true)
            ->call('save')
            ->assertDispatched('notify');

        $rule = NotificationRule::query()->firstOrFail();

        $this->assertSame([
            'targets' => ['employee', 'department', 'specific_users'],
            'structure_ids' => [5, 9],
            'user_ids' => [2, 17],
        ], $rule->audience_config);

        $component
            ->call('edit', $rule->id)
            ->set('form.trigger', 'birthday_reminder')
            ->call('save')
            ->assertDispatched('notify')
            ->call('delete', $rule->id);

        $this->assertDatabaseMissing('notification_rules', [
            'id' => $rule->id,
        ]);
    }

    public function test_rule_manager_validates_required_fields(): void
    {
        $user = User::factory()->create();
        $this->grantRulePermissions($user);
        $this->actingAs($user);

        Livewire::test(\App\Modules\Notifications\Livewire\RuleManager::class)
            ->set('form.trigger', '')
            ->call('save')
            ->assertHasErrors(['form.trigger' => 'required']);
    }

    public function test_rule_manager_updates_trigger_options_when_category_changes(): void
    {
        $user = User::factory()->create();
        $this->grantRulePermissions($user);
        $this->actingAs($user);

        Livewire::test(\App\Modules\Notifications\Livewire\RuleManager::class)
            ->set('form.category', 'announcement')
            ->assertSet('form.trigger', 'announcement_published')
            ->assertSee('Elan yayımlandı')
            ->assertSee('Planlaşdırılmış elan')
            ->assertSee('Əl ilə elan yaradıldı')
            ->set('form.category', 'holiday')
            ->assertSet('form.trigger', 'holiday_due')
            ->assertSee('Bayram / tətil vaxtı')
            ->assertSee('Bayram / tətil xatırlatması')
            ->assertSee('Əl ilə bayram / tətil elan edildi');
    }

    public function test_rule_manager_translates_announcement_audience_targets(): void
    {
        $user = User::factory()->create();
        $this->grantRulePermissions($user);
        $this->actingAs($user);

        Livewire::test(\App\Modules\Notifications\Livewire\RuleManager::class)
            ->set('form.category', 'announcement')
            ->set('form.audience_targets', 'all_employees, admins')
            ->assertSee('Bütün əməkdaşlar')
            ->assertSee('Sistem administratorları');
    }

    public function test_rule_manager_rejects_unsupported_audience_targets(): void
    {
        $user = User::factory()->create();
        $this->grantRulePermissions($user);
        $this->actingAs($user);

        Livewire::test(\App\Modules\Notifications\Livewire\RuleManager::class)
            ->set('form.audience_targets', 'employee, bogus_target')
            ->call('save')
            ->assertHasErrors(['form.audience_targets']);

        $this->assertDatabaseCount('notification_rules', 0);
    }
}
