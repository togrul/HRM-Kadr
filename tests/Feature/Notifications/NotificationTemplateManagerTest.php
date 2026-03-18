<?php

namespace Tests\Feature\Notifications;

use App\Mail\NotificationTemplatePreviewMail;
use App\Models\NotificationTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class NotificationTemplateManagerTest extends TestCase
{
    use RefreshDatabase;

    private function grantTemplatePermissions(User $user): void
    {
        Permission::findOrCreate('access-settings', 'web');
        Permission::findOrCreate('manage-notification-templates', 'web');
        $user->givePermissionTo(['access-settings', 'manage-notification-templates']);
    }

    public function test_template_manager_can_create_update_and_delete_template(): void
    {
        $user = User::factory()->create();
        $this->grantTemplatePermissions($user);
        $this->actingAs($user);

        $component = Livewire::test(\App\Modules\Notifications\Livewire\TemplateManager::class)
            ->set('form.key', 'birthday.mail')
            ->set('form.category', 'birthday')
            ->set('form.channel', 'mail')
            ->set('form.format', 'html')
            ->set('form.subject_template', 'Ad günü bildirişi')
            ->set('form.body_template', '<p>Salam</p>')
            ->call('save')
            ->assertDispatched('notify');

        $template = NotificationTemplate::query()->firstOrFail();

        $this->assertSame('birthday.mail', $template->key);

        $component
            ->call('edit', $template->id)
            ->set('form.subject_template', 'Yenilənmiş mövzu')
            ->call('save')
            ->assertDispatched('notify')
            ->call('delete', $template->id);

        $this->assertDatabaseMissing('notification_templates', [
            'id' => $template->id,
        ]);
    }

    public function test_template_manager_uses_translated_validation_attributes(): void
    {
        $user = User::factory()->create();
        $this->grantTemplatePermissions($user);
        $this->actingAs($user);

        Livewire::test(\App\Modules\Notifications\Livewire\TemplateManager::class)
            ->call('save')
            ->assertSee('Açar mütləqdir.');
    }

    public function test_template_manager_can_send_preview_mail(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $this->grantTemplatePermissions($user);
        $this->actingAs($user);

        Livewire::test(\App\Modules\Notifications\Livewire\TemplateManager::class)
            ->set('form.key', 'birthday.mail')
            ->set('form.category', 'birthday')
            ->set('form.format', 'html')
            ->set('form.subject_template', 'Ad günü: {{ name }}')
            ->set('form.body_template', '<p>{{ name }} / {{ position }}</p>')
            ->set('testEmail', 'demo@example.test')
            ->call('sendTest')
            ->assertDispatched('notify')
            ->assertSee('Test e-poçtu göndərildi: demo@example.test');

        Mail::assertSent(NotificationTemplatePreviewMail::class, function ($mail) {
            return $mail->hasTo('demo@example.test')
                && $mail->subjectLine === 'Ad günü: Murad Əliyev'
                && $mail->isHtml === true;
        });
    }

    public function test_template_manager_save_requires_manage_permission(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('access-settings', 'web');
        $user->givePermissionTo(['access-settings']);
        $this->actingAs($user);

        Livewire::test(\App\Modules\Notifications\Livewire\TemplateManager::class)
            ->set('form.key', 'birthday.mail')
            ->set('form.category', 'birthday')
            ->set('form.body_template', 'Salam')
            ->call('save')
            ->assertForbidden();
    }
}
