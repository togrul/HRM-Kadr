<?php

namespace Tests\Feature\Notifications;

use App\Models\AttendanceCalendar;
use App\Models\NotificationCampaign;
use App\Models\NotificationDispatch;
use App\Models\NotificationRule;
use App\Models\NotificationTemplate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotifyHolidaysCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_notify_holidays_creates_campaign_dispatch_and_database_notification(): void
    {
        Carbon::setTestNow('2026-03-19 09:00:00');

        $adminRole = Role::findOrCreate('admin');

        NotificationTemplate::query()->create([
            'key' => 'holiday.default',
            'category' => 'holiday',
            'channel' => 'database',
            'format' => 'text',
            'subject_template' => 'Bayram: {{ holiday_name }}',
            'body_template' => '{{ holiday_name }} - {{ holiday_date }}',
            'is_active' => true,
        ]);

        NotificationRule::query()->create([
            'category' => 'holiday',
            'trigger' => 'holiday_due',
            'channel' => 'database',
            'audience_config' => ['targets' => ['admins']],
            'approval_required' => false,
            'is_active' => true,
        ]);

        $recipient = User::factory()->create([
            'email' => 'admin@example.test',
            'is_active' => true,
        ]);
        $recipient->assignRole($adminRole);

        AttendanceCalendar::query()->create([
            'date' => '2026-03-20',
            'day_type' => 'holiday',
            'name' => 'Novruz bayramı',
            'is_paid' => true,
            'scope_type' => 'global',
            'scope_id' => null,
        ]);

        $this->artisan('notify:holidays --days-ahead=1')
            ->expectsOutputToContain('Holiday notifications dispatched: 1')
            ->assertSuccessful();

        $campaign = NotificationCampaign::query()->first();
        $dispatch = NotificationDispatch::query()->first();

        $this->assertNotNull($campaign);
        $this->assertNotNull($dispatch);
        $this->assertSame('holiday', $campaign->category);
        $this->assertSame('sent', $campaign->status);
        $this->assertSame('sent', $dispatch->status);
        $this->assertSame('holiday', $recipient->notifications()->first()->data['action']);

        Carbon::setTestNow();
    }
}
