<?php

namespace Tests\Feature\Notifications;

use App\Models\NotificationCampaign;
use App\Models\NotificationDispatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class NotificationInsightsPanelsTest extends TestCase
{
    use RefreshDatabase;

    private function grantSettingsPermissions(User $user): void
    {
        foreach ([
            'access-settings',
            'manage-notification-campaigns',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo(['access-settings', 'manage-notification-campaigns']);
    }

    public function test_history_board_renders_dispatch_and_audit_details(): void
    {
        $user = User::factory()->create(['is_active' => true, 'email' => 'owner@example.test']);
        $this->grantSettingsPermissions($user);
        $this->actingAs($user);

        $campaign = NotificationCampaign::query()->create([
            'category' => 'announcement',
            'trigger' => 'manual_announcement',
            'title' => 'Audit test',
            'channel' => 'mail',
            'payload' => ['action' => 'announcement', 'category' => 'notifications::common.categories.announcement'],
            'format' => 'html',
            'status' => 'failed',
            'approval_status' => 'approved',
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        $campaign->approvals()->create([
            'action' => 'approved',
            'acted_by' => $user->id,
            'acted_at' => now(),
        ]);

        NotificationDispatch::query()->create([
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'channel' => 'mail',
            'status' => 'failed',
            'attempt_count' => 2,
            'error_message' => 'SMTP timeout',
            'meta' => ['recipient_email' => 'owner@example.test'],
            'failed_at' => now(),
        ]);

        Livewire::test(\App\Modules\Notifications\Livewire\HistoryBoard::class)
            ->assertSee('Audit test')
            ->assertSee('SMTP timeout')
            ->assertSee('Cəhd sayı');
    }

    public function test_history_board_surfaces_failed_campaign_without_dispatches(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->grantSettingsPermissions($user);
        $this->actingAs($user);

        $campaign = NotificationCampaign::query()->create([
            'category' => 'announcement',
            'trigger' => 'manual_announcement',
            'title' => 'Recipients missing',
            'channel' => 'database',
            'payload' => ['action' => 'announcement'],
            'format' => 'text',
            'status' => 'failed',
            'approval_status' => 'approved',
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        $campaign->approvals()->create([
            'action' => 'failed',
            'acted_by' => $user->id,
            'acted_at' => now(),
            'note' => 'Uyğun qəbul edən tapılmadı.',
        ]);

        Livewire::test(\App\Modules\Notifications\Livewire\HistoryBoard::class)
            ->assertSee('Recipients missing')
            ->assertSee('Uyğun qəbul edən tapılmadı.')
            ->assertSee('1');
    }

    public function test_analytics_panel_renders_failure_and_turnaround_metrics(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->grantSettingsPermissions($user);
        $this->actingAs($user);

        $campaign = NotificationCampaign::query()->create([
            'category' => 'announcement',
            'trigger' => 'manual_announcement',
            'title' => 'Analytics test',
            'channel' => 'mail',
            'payload' => ['action' => 'announcement'],
            'format' => 'html',
            'status' => 'sent',
            'approval_status' => 'approved',
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'approved_at' => now()->addMinutes(12),
        ]);

        $campaign->forceFill([
            'created_at' => now(),
            'updated_at' => now(),
        ])->save();

        NotificationDispatch::query()->create([
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'channel' => 'mail',
            'status' => 'failed',
            'attempt_count' => 1,
            'error_message' => 'SMTP timeout',
            'meta' => ['recipient_email' => 'owner@example.test'],
            'failed_at' => now(),
        ]);

        Livewire::test(\App\Modules\Notifications\Livewire\AnalyticsPanel::class)
            ->assertSee('SMTP timeout')
            ->assertSee('Təsdiq müddəti');
    }

    public function test_analytics_panel_can_filter_by_custom_date_range(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->grantSettingsPermissions($user);
        $this->actingAs($user);

        $campaign = NotificationCampaign::query()->create([
            'category' => 'announcement',
            'trigger' => 'manual_announcement',
            'title' => 'Windowed analytics',
            'channel' => 'mail',
            'payload' => ['action' => 'announcement'],
            'format' => 'html',
            'status' => 'sent',
            'approval_status' => 'approved',
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'approved_at' => now()->subDay(),
        ]);

        $campaign->forceFill([
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ])->save();

        $dispatch = NotificationDispatch::query()->create([
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'channel' => 'mail',
            'status' => 'sent',
            'attempt_count' => 1,
            'sent_at' => now()->subDay(),
        ]);

        $dispatch->forceFill([
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ])->save();

        Livewire::test(\App\Modules\Notifications\Livewire\AnalyticsPanel::class)
            ->set('range', 'custom')
            ->set('dateFrom', now()->subDays(2)->toDateString())
            ->set('dateTo', now()->toDateString())
            ->assertSee('Göndərildi')
            ->assertSee('1');
    }
}
