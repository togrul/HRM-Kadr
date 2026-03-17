<?php

namespace Tests\Feature\Notifications;

use App\Mail\NotificationCampaignMail;
use App\Models\NotificationCampaign;
use App\Models\NotificationDispatch;
use App\Models\NotificationRule;
use App\Models\NotificationTemplate;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificationCampaignWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function grantNotificationWorkflowPermissions(User $user, array $extra = []): void
    {
        $permissions = array_unique(array_merge([
            'access-settings',
            'manage-notification-campaigns',
            'approve-notification-campaigns',
        ], $extra));

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo($permissions);
    }

    public function test_announcement_composer_creates_and_dispatches_campaign(): void
    {
        $adminRole = Role::findOrCreate('admin');
        $creator = User::factory()->create(['is_active' => true]);
        $creator->assignRole($adminRole);
        $this->grantNotificationWorkflowPermissions($creator);

        $this->actingAs($creator);

        Livewire::test(\App\Modules\Notifications\Livewire\AnnouncementComposer::class)
            ->set('form.title', 'Sistem yenilənməsi')
            ->set('form.body', 'Bu gün saat 18:00-da qısa fasilə olacaq.')
            ->set('form.audience_targets', 'admins')
            ->set('form.approval_required', false)
            ->set('form.send_now', true)
            ->call('save')
            ->assertDispatched('notify');

        $campaign = NotificationCampaign::query()->first();

        $this->assertNotNull($campaign);
        $this->assertSame('announcement', $campaign->category);
        $this->assertSame('sent', $campaign->status);
        $this->assertDatabaseCount('notification_dispatches', 1);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_announcement_composer_persists_department_and_specific_user_targets(): void
    {
        $adminRole = Role::findOrCreate('admin');
        $creator = User::factory()->create(['is_active' => true]);
        $creator->assignRole($adminRole);
        $this->grantNotificationWorkflowPermissions($creator);

        $this->actingAs($creator);

        Livewire::test(\App\Modules\Notifications\Livewire\AnnouncementComposer::class)
            ->set('form.title', 'Departament elanı')
            ->set('form.body', 'Maliyyə şöbəsi üçün daxili elan.')
            ->set('form.audience_targets', 'department, specific_users')
            ->set('form.structure_ids', [8, 9])
            ->set('form.user_ids', [4, 6])
            ->set('form.approval_required', true)
            ->set('form.send_now', false)
            ->call('save')
            ->assertDispatched('notify');

        $campaign = NotificationCampaign::query()->firstOrFail();

        $this->assertSame(['department', 'specific_users'], data_get($campaign->audience_config, 'targets'));
        $this->assertSame([8, 9], data_get($campaign->audience_config, 'structure_ids'));
        $this->assertSame([4, 6], data_get($campaign->audience_config, 'user_ids'));
        $this->assertSame('draft', $campaign->status);
    }

    public function test_announcement_composer_rejects_unsupported_audience_targets(): void
    {
        $creator = User::factory()->create(['is_active' => true]);
        $this->grantNotificationWorkflowPermissions($creator);
        $this->actingAs($creator);

        Livewire::test(\App\Modules\Notifications\Livewire\AnnouncementComposer::class)
            ->set('form.title', 'Yanlış auditoriya')
            ->set('form.body', 'Test')
            ->set('form.audience_targets', 'all_employees, bogus_target')
            ->call('save')
            ->assertHasErrors(['form.audience_targets']);

        $this->assertDatabaseCount('notification_campaigns', 0);
    }

    public function test_announcement_mail_channel_uses_mail_delivery_without_database_notification(): void
    {
        Mail::fake();

        $adminRole = Role::findOrCreate('admin');
        $creator = User::factory()->create(['is_active' => true]);
        $recipient = User::factory()->create([
            'is_active' => true,
            'email' => 'recipient@example.test',
        ]);
        $recipient->assignRole($adminRole);
        $this->grantNotificationWorkflowPermissions($creator);

        $this->actingAs($creator);

        Livewire::test(\App\Modules\Notifications\Livewire\AnnouncementComposer::class)
            ->set('form.title', 'Mail elanı')
            ->set('form.body', '<p>Mail ilə test bildirişi.</p>')
            ->set('form.channel', 'mail')
            ->set('form.format', 'html')
            ->set('form.audience_targets', 'admins')
            ->set('form.approval_required', false)
            ->set('form.send_now', true)
            ->call('save');

        Mail::assertSent(NotificationCampaignMail::class, function ($mail) {
            return $mail->hasTo('recipient@example.test') && $mail->isHtml === true;
        });

        $this->assertDatabaseCount('notification_dispatches', 1);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_announcement_composer_can_store_scheduled_holiday_campaign_without_dispatching(): void
    {
        $creator = User::factory()->create(['is_active' => true]);
        $this->grantNotificationWorkflowPermissions($creator);
        $this->actingAs($creator);

        Livewire::test(\App\Modules\Notifications\Livewire\AnnouncementComposer::class)
            ->set('form.category', 'holiday')
            ->set('form.title', 'Novruz bildirişi')
            ->set('form.holiday_name', 'Novruz bayramı')
            ->set('form.holiday_date', now()->addDays(3)->toDateString())
            ->set('form.scope', 'Bütün əməkdaşlar')
            ->set('form.schedule_mode', 'custom')
            ->set('form.scheduled_at', now()->addDay()->format('Y-m-d\TH:i'))
            ->set('form.audience_targets', 'all_employees')
            ->set('form.approval_required', false)
            ->set('form.send_now', true)
            ->call('save')
            ->assertDispatched('notify');

        $campaign = NotificationCampaign::query()->firstOrFail();

        $this->assertSame('holiday', $campaign->category);
        $this->assertSame('queued', $campaign->status);
        $this->assertNotNull($campaign->scheduled_at);
        $this->assertDatabaseCount('notification_dispatches', 0);
    }

    public function test_announcement_composer_prefills_defaults_from_active_rule(): void
    {
        $creator = User::factory()->create(['is_active' => true]);
        $this->grantNotificationWorkflowPermissions($creator);
        $this->actingAs($creator);

        $template = NotificationTemplate::query()->create([
            'key' => 'announcement.default',
            'category' => 'announcement',
            'channel' => 'mail',
            'format' => 'text',
            'body_template' => 'Salam',
            'is_active' => true,
            'created_by' => $creator->id,
        ]);

        NotificationRule::query()->create([
            'category' => 'announcement',
            'trigger' => 'manual_announcement',
            'template_id' => $template->id,
            'channel' => 'mail',
            'audience_config' => [
                'targets' => ['specific_users'],
                'structure_ids' => [4],
                'user_ids' => [7],
            ],
            'approval_required' => false,
            'is_active' => true,
            'created_by' => $creator->id,
        ]);

        Livewire::test(\App\Modules\Notifications\Livewire\AnnouncementComposer::class)
            ->assertSet('form.template_id', $template->id)
            ->assertSet('form.channel', 'mail')
            ->assertSet('form.approval_required', false)
            ->assertSet('form.audience_targets', 'specific_users')
            ->assertSet('form.structure_ids', [4])
            ->assertSet('form.user_ids', [7]);
    }

    public function test_approval_queue_can_approve_pending_campaign_and_dispatch_it(): void
    {
        $adminRole = Role::findOrCreate('admin');
        $approver = User::factory()->create(['is_active' => true]);
        $recipient = User::factory()->create(['is_active' => true]);
        $recipient->assignRole($adminRole);
        $this->grantNotificationWorkflowPermissions($approver);

        $this->actingAs($approver);

        $campaign = NotificationCampaign::query()->create([
            'category' => 'announcement',
            'trigger' => 'manual_announcement',
            'title' => 'Təcili elan',
            'channel' => 'database',
            'audience_config' => ['targets' => ['admins']],
            'payload' => [
                'action' => 'announcement',
                'name' => 'Təcili elan',
                'message' => 'Bu gün 16:00-da iclas olacaq.',
                'category' => 'notifications::common.categories.announcement',
            ],
            'format' => 'text',
            'status' => 'draft',
            'approval_status' => 'pending',
            'created_by' => $approver->id,
        ]);

        Livewire::test(\App\Modules\Notifications\Livewire\ApprovalQueue::class)
            ->set("notes.{$campaign->id}", 'Yoxlanıldı')
            ->call('approve', $campaign->id);

        $campaign->refresh();

        $this->assertSame('approved', $campaign->approval_status);
        $this->assertSame('sent', $campaign->status);
        $this->assertDatabaseCount('notification_dispatches', 1);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_campaign_board_can_duplicate_resend_and_retry_campaigns(): void
    {
        Mail::fake();

        $user = User::factory()->create(['is_active' => true, 'email' => 'owner@example.test']);
        $recipient = User::factory()->create(['is_active' => true, 'email' => 'recipient@example.test']);
        $this->grantNotificationWorkflowPermissions($user);
        $this->actingAs($user);

        $campaign = NotificationCampaign::query()->create([
            'category' => 'announcement',
            'trigger' => 'manual_announcement',
            'title' => 'Retry test',
            'channel' => 'mail',
            'audience_config' => ['targets' => ['specific_users'], 'user_ids' => [$recipient->id]],
            'payload' => [
                'action' => 'announcement',
                'name' => 'Retry test',
                'message' => 'Elan',
                'body' => 'Body',
                'category' => 'notifications::common.categories.announcement',
            ],
            'format' => 'html',
            'status' => 'failed',
            'approval_status' => 'not_required',
            'created_by' => $user->id,
        ]);

        NotificationDispatch::query()->create([
            'campaign_id' => $campaign->id,
            'user_id' => $recipient->id,
            'channel' => 'mail',
            'status' => 'failed',
            'attempt_count' => 1,
            'error_message' => 'SMTP timeout',
        ]);

        Livewire::test(\App\Modules\Notifications\Livewire\CampaignBoard::class)
            ->call('retry', $campaign->id)
            ->call('duplicate', $campaign->id)
            ->call('resend', $campaign->id);

        $this->assertDatabaseHas('notification_dispatches', [
            'campaign_id' => $campaign->id,
            'status' => 'sent',
            'attempt_count' => 2,
        ]);

        $this->assertGreaterThanOrEqual(3, NotificationCampaign::query()->count());
    }

    public function test_campaign_board_ignores_invalid_status_updates(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->grantNotificationWorkflowPermissions($user);
        $this->actingAs($user);

        $campaign = NotificationCampaign::query()->create([
            'category' => 'announcement',
            'trigger' => 'manual_announcement',
            'title' => 'Status test',
            'channel' => 'database',
            'audience_config' => ['targets' => ['admins']],
            'payload' => ['message' => 'test'],
            'format' => 'text',
            'status' => 'draft',
            'approval_status' => 'not_required',
            'created_by' => $user->id,
        ]);

        Livewire::test(\App\Modules\Notifications\Livewire\CampaignBoard::class)
            ->call('updateStatus', $campaign->id, 'hacked_status');

        $campaign->refresh();

        $this->assertSame('draft', $campaign->status);
    }

    public function test_campaign_retry_respects_backoff_window(): void
    {
        Mail::fake();

        $user = User::factory()->create(['is_active' => true, 'email' => 'owner@example.test']);
        $recipient = User::factory()->create(['is_active' => true, 'email' => 'recipient@example.test']);
        $this->grantNotificationWorkflowPermissions($user);
        $this->actingAs($user);

        $campaign = NotificationCampaign::query()->create([
            'category' => 'announcement',
            'trigger' => 'manual_announcement',
            'title' => 'Backoff test',
            'channel' => 'mail',
            'audience_config' => ['targets' => ['specific_users'], 'user_ids' => [$recipient->id]],
            'payload' => [
                'action' => 'announcement',
                'name' => 'Backoff test',
                'message' => 'Elan',
                'body' => 'Body',
                'category' => 'notifications::common.categories.announcement',
            ],
            'format' => 'html',
            'status' => 'failed',
            'approval_status' => 'not_required',
            'created_by' => $user->id,
        ]);

        $dispatch = NotificationDispatch::query()->create([
            'campaign_id' => $campaign->id,
            'user_id' => $recipient->id,
            'channel' => 'mail',
            'status' => 'failed',
            'attempt_count' => 2,
            'error_message' => 'SMTP timeout',
            'meta' => [
                'recipient_email' => $recipient->email,
                'channel' => 'mail',
                'next_retry_at' => now()->addMinutes(10)->toIso8601String(),
            ],
        ]);

        Livewire::test(\App\Modules\Notifications\Livewire\CampaignBoard::class)
            ->call('retry', $campaign->id);

        $dispatch->refresh();

        $this->assertSame(2, $dispatch->attempt_count);
        $this->assertSame('failed', $dispatch->status);
    }

    public function test_position_change_observer_creates_campaign_and_notification(): void
    {
        $this->seedPersonnelLookups();

        $adminRole = Role::findOrCreate('admin');
        $recipient = User::factory()->create(['is_active' => true]);
        $recipient->assignRole($adminRole);

        $oldPosition = Position::query()->create(['id' => 10, 'name' => 'Məsləhətçi']);
        $newPosition = Position::query()->create(['id' => 11, 'name' => 'Aparıcı məsləhətçi']);
        Structure::query()->create(['id' => 5, 'name' => 'İR', 'shortname' => 'IR', 'code' => 5, 'level' => 1]);

        $template = NotificationTemplate::query()->create([
            'key' => 'position-change.default',
            'category' => 'position_change',
            'channel' => 'database',
            'format' => 'text',
            'subject_template' => 'Vəzifə dəyişikliyi: {{ name }}',
            'body_template' => '{{ old_position }} → {{ new_position }}',
            'is_active' => true,
        ]);

        NotificationRule::query()->create([
            'category' => 'position_change',
            'trigger' => 'position_changed',
            'template_id' => $template->id,
            'channel' => 'database',
            'audience_config' => ['targets' => ['admins']],
            'approval_required' => false,
            'is_active' => true,
        ]);

        $personnel = Personnel::factory()->create([
            'tabel_no' => '1001',
            'surname' => 'Əliyev',
            'name' => 'Murad',
            'patronymic' => 'Aydın',
            'birthdate' => '1990-03-16',
            'mobile' => '0500000101',
            'nationality_id' => 1,
            'pin' => 'PIN1001',
            'residental_address' => 'Baku',
            'education_degree_id' => 1,
            'structure_id' => 5,
            'position_id' => $oldPosition->id,
            'work_norm_id' => 1,
            'join_work_date' => now()->toDateString(),
            'is_pending' => false,
            'leave_work_date' => null,
        ]);

        $personnel->update([
            'position_id' => $newPosition->id,
        ]);

        $campaign = NotificationCampaign::query()->first();

        $this->assertNotNull($campaign);
        $this->assertSame('position_change', $campaign->category);
        $this->assertSame('sent', $campaign->status);
        $this->assertSame('Məsləhətçi', data_get($campaign->payload, 'old_position'));
        $this->assertSame('Aparıcı məsləhətçi', data_get($campaign->payload, 'new_position'));
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $recipient->id,
        ]);
        $this->assertDatabaseCount('notification_dispatches', 1);
    }

    private function seedPersonnelLookups(): void
    {
        Permission::findOrCreate('get-notification');
        DB::table('countries')->insert(['id' => 1, 'code' => 'AZ']);
        DB::table('education_degrees')->insert(['id' => 1, 'title_az' => 'Bakalavr']);
        DB::table('work_norms')->insert(['id' => 1, 'name_az' => 'Tam ştat']);
    }
}
