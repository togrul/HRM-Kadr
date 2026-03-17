<?php

namespace Tests\Feature\Notifications;

use App\Models\NotificationCampaign;
use App\Models\NotificationDispatch;
use App\Models\NotificationRule;
use App\Models\NotificationTemplate;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotifyBirthdaysCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_notify_birthdays_creates_campaign_dispatch_and_database_notification(): void
    {
        Carbon::setTestNow('2026-03-16 10:00:00');

        $adminRole = Role::findOrCreate('admin');
        Permission::findOrCreate('get-notification');
        DB::table('countries')->insert(['id' => 1, 'code' => 'AZ']);
        DB::table('education_degrees')->insert(['id' => 1, 'title_az' => 'Bakalavr']);
        DB::table('work_norms')->insert(['id' => 1, 'name_az' => 'Tam ştat']);
        Structure::query()->create(['id' => 7, 'name' => 'İnsan resursları', 'shortname' => 'İR', 'code' => 7, 'level' => 1]);
        Position::query()->create(['id' => 12, 'name' => 'Baş məsləhətçi']);

        NotificationTemplate::query()->create([
            'key' => 'birthday.database',
            'category' => 'birthday',
            'channel' => 'database',
            'format' => 'text',
            'subject_template' => 'Ad günü: {{ name }}',
            'body_template' => '{{ name }} - {{ position }} - {{ structure }}',
            'is_active' => true,
        ]);

        NotificationRule::query()->create([
            'category' => 'birthday',
            'trigger' => 'birthday_due',
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

        Personnel::factory()->create([
            'tabel_no' => '0901',
            'surname' => 'Əliyev',
            'name' => 'Murad',
            'patronymic' => 'Aydın',
            'birthdate' => '1990-03-16',
            'mobile' => '0500000091',
            'nationality_id' => 1,
            'pin' => 'PIN0901',
            'residental_address' => 'Baku',
            'education_degree_id' => 1,
            'is_pending' => false,
            'work_norm_id' => 1,
            'join_work_date' => '2020-01-01',
            'leave_work_date' => null,
            'position_id' => 12,
            'structure_id' => 7,
        ]);

        $this->artisan('notify:birthdays')
            ->expectsOutputToContain('Sent successfully!')
            ->assertSuccessful();

        $campaign = NotificationCampaign::query()->first();
        $dispatch = NotificationDispatch::query()->first();

        $this->assertNotNull($campaign);
        $this->assertNotNull($dispatch);
        $this->assertSame('birthday', $campaign->category);
        $this->assertSame('sent', $campaign->status);
        $this->assertSame('sent', $dispatch->status);
        $this->assertDatabaseCount('notifications', 1);
        $this->assertSame('birthday', $recipient->notifications()->first()->data['action']);

        Carbon::setTestNow();
    }
}
