<?php

namespace Tests\Feature\Reports;

use App\Models\Personnel;
use App\Models\User;
use App\Modules\Reports\Application\Mail\ScheduledReportMail;
use App\Modules\Reports\Application\Services\ReportsAuditLogger;
use App\Modules\Reports\Application\Services\ReportScheduleService;
use App\Modules\Reports\Domain\Models\ReportSchedule;
use App\Modules\Reports\Livewire\Dashboard;
use App\Modules\Reports\Livewire\Schedules;
use App\Modules\Reports\Support\ReportPermissions;
use App\Services\StructureService;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Feature\Reports\Concerns\GrantsReportPermissions;
use Tests\TestCase;

use function grantAllStructures;
use function grantStructures;

/**
 * Planlı hesabatlar: yalnız `schedule-reports` ilə qurulur, alıcılar yalnız hesabata baxa
 * bilən aktiv sistem istifadəçiləridir, fayl SAHİBİN icazə və strukturları ilə qurulur,
 * uğursuzluq qeyd olunur, job sync növbədə də işləyir.
 */
class ReportSchedulesTest extends TestCase
{
    use GrantsReportPermissions;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['queue.default' => 'sync']);
        Carbon::setTestNow('2026-03-10 09:00:00');

        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');
        DB::table('countries')->insertOrIgnore(['id' => 1, 'code' => 'AZ']);
        DB::table('education_degrees')->insertOrIgnore(['id' => 1, 'title_az' => 'Bakalavr', 'title_en' => 'Bachelor', 'title_ru' => 'Bachelor']);
        DB::table('structures')->insertOrIgnore([
            ['id' => 1, 'name' => 'Daxili', 'shortname' => 'D'],
            ['id' => 2, 'name' => 'Kənar', 'shortname' => 'K'],
        ]);
        DB::table('positions')->insertOrIgnore(['id' => 1, 'name' => 'Analitik']);
        DB::table('work_norms')->insertOrIgnore(['id' => 1, 'name_az' => 'Tam', 'name_en' => 'Full', 'name_ru' => 'Full']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_owner_creates_a_schedule_with_system_user_recipients_from_the_component(): void
    {
        $owner = $this->scheduler();
        $recipient = $this->reportUser('Rəhbər Əliyev', 'rehber@example.test');
        $this->actingAs($owner);

        Livewire::test(Schedules::class)
            ->set('name', 'Aylıq kadr')
            ->set('report', 'headcount')
            ->set('frequency', 'monthly')
            ->set('monthDay', 5)
            ->set('sendTime', '07:30')
            ->set('format', 'csv')
            ->set('recipientSearch', 'Rəhbər')
            ->assertSee('rehber@example.test')
            ->call('addRecipient', $recipient->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Aylıq kadr');

        $schedule = ReportSchedule::query()->sole();
        $this->assertSame($owner->id, $schedule->user_id);
        $this->assertSame(['report' => 'headcount', 'structure_id' => null], $schedule->filters);
        $this->assertSame('2026-04-05 07:30:00', $schedule->next_run_at?->toDateTimeString());
        $this->assertSame([$recipient->id], $schedule->recipients()->pluck('users.id')->all());
        $this->assertTrue(Activity::query()->where('event', ReportsAuditLogger::EVENT_SCHEDULE_CREATED)->exists());
    }

    public function test_scheduling_requires_its_own_permission(): void
    {
        $user = grantAllStructures($this->grantReports(User::factory()->create()));
        $this->actingAs($user);

        Livewire::test(Dashboard::class)->assertSet('availableTabs', ['overview', 'standard', 'dynamic', 'comparisons']);
        Livewire::test(Schedules::class)->assertForbidden();

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        app(ReportScheduleService::class)->save($user, $this->scheduleData([$user->id]));
    }

    public function test_recipients_must_be_active_system_users_allowed_to_see_the_report(): void
    {
        $owner = $this->scheduler();
        $service = app(ReportScheduleService::class);

        $noReports = User::factory()->create(['email' => 'nope@example.test']);
        $otherGroup = $this->reportUser('Davamiyyət', 'att@example.test', [ReportPermissions::GROUP_ATTENDANCE]);
        $inactive = $this->reportUser('Passiv', 'off@example.test');
        $inactive->forceFill(['is_active' => false])->save();

        foreach ([[$noReports->id], [$otherGroup->id], [$inactive->id], [999999], []] as $ids) {
            try {
                $service->save($owner, $this->scheduleData($ids));
                $this->fail('Alıcı qəbul olunmamalı idi: '.json_encode($ids));
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('recipient_ids', $exception->errors());
            }
        }

        $this->assertSame(0, ReportSchedule::query()->count());
    }

    public function test_a_recipient_must_see_every_structure_the_file_will_contain(): void
    {
        $service = app(ReportScheduleService::class);
        $narrow = grantStructures($this->grantReports(User::factory()->create(['email' => 'narrow@example.test']), export: false), [1]);
        $wide = grantStructures($this->grantReports(User::factory()->create(['email' => 'wide@example.test']), export: false), [1, 2]);

        // Sahib hamısını görür, struktur seçilməyib: fayl bütün strukturları daşıyır —
        // yalnız «bütün strukturlar» bayraqlı alıcı qəbul olunur.
        try {
            $service->save($this->scheduler(), $this->scheduleData([$wide->id]));
            $this->fail('Dar görünürlüklü alıcı qəbul olunmamalı idi.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('recipient_ids', $exception->errors());
        }

        // Sahib 1 və 2-ni görür: alıcı da hər ikisini görməlidir.
        $owner = grantStructures($this->grantReports(User::factory()->create(), extra: [ReportPermissions::SCHEDULE]), [1, 2]);

        try {
            $service->save($owner, $this->scheduleData([$narrow->id]));
            $this->fail('Sahibdən dar görünürlüklü alıcı qəbul olunmamalı idi.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('recipient_ids', $exception->errors());
        }

        $this->assertSame(1, $service->save($owner, $this->scheduleData([$wide->id]))->recipients()->count());

        // Struktur seçilibsə, alıcının həmin strukturu görməsi kifayətdir.
        $this->assertSame(1, $service->save($owner, $this->scheduleData([$narrow->id], [
            'name' => 'Yalnız 1',
            'filters' => ['report' => 'headcount', 'structure_id' => 1],
        ]))->recipients()->count());
    }

    public function test_due_schedule_is_built_with_the_owners_structures_and_mailed_via_sync_job(): void
    {
        Mail::fake();
        $this->personnel('RP-001', 1);
        $this->personnel('RP-002', 2);
        $this->personnel('RP-003', 2);

        // Sahib yalnız «Daxili» strukturunu görür; alıcı isə bütün təşkilatı.
        $owner = grantStructures($this->grantReports(User::factory()->create(), extra: [ReportPermissions::SCHEDULE]), [1]);
        $recipient = $this->reportUser('Direktor', 'director@example.test');

        $schedule = app(ReportScheduleService::class)->save($owner, $this->scheduleData([$recipient->id, $owner->id], ['format' => 'csv', 'frequency' => 'daily', 'send_time' => '08:00']));
        $this->assertSame('2026-03-11 08:00:00', $schedule->next_run_at?->toDateTimeString());

        Carbon::setTestNow('2026-03-11 08:02:00');
        $this->artisan('reports:send-scheduled')->assertSuccessful();

        Mail::assertSent(ScheduledReportMail::class, 2);
        Mail::assertSent(ScheduledReportMail::class, function (ScheduledReportMail $mail) use ($recipient): bool {
            return $mail->hasTo($recipient->email)
                && str_ends_with($mail->attachmentName, '.csv')
                && str_contains($mail->attachmentContent, 'Daxili')
                && ! str_contains($mail->attachmentContent, 'Kənar');
        });

        $schedule->refresh();
        $this->assertSame(ReportSchedule::STATUS_SENT, $schedule->last_status);
        $this->assertSame('2026-03-12 08:00:00', $schedule->next_run_at?->toDateTimeString());
        $this->assertTrue(Activity::query()->where('event', ReportsAuditLogger::EVENT_SCHEDULE_SENT)->where('causer_id', $owner->id)->exists());

        // Eyni dəqiqədə ikinci icra planı yenidən göndərmir.
        $this->artisan('reports:send-scheduled')->assertSuccessful();
        Mail::assertSent(ScheduledReportMail::class, 2);

        // İcradan sonra cari istifadəçi qalmır (sahibin adı başqa işə sızmır).
        $this->assertNull(auth()->user());
    }

    public function test_owner_who_lost_the_permission_gets_nothing_sent_and_the_failure_is_logged(): void
    {
        Mail::fake();
        Log::spy();

        $owner = $this->scheduler();
        $recipient = $this->reportUser('Direktor', 'director@example.test');
        $schedule = app(ReportScheduleService::class)->save($owner, $this->scheduleData([$recipient->id], ['frequency' => 'daily']));

        $owner->revokePermissionTo(ReportPermissions::SCHEDULE);
        $this->travelTo(Carbon::parse('2026-03-11 08:05:00'));
        $this->artisan('reports:send-scheduled')->assertSuccessful();

        Mail::assertNothingSent();
        $schedule->refresh();
        $this->assertSame(ReportSchedule::STATUS_FAILED, $schedule->last_status);
        $this->assertSame('owner_not_allowed', $schedule->last_error);
        $this->assertSame('2026-03-12 08:00:00', $schedule->next_run_at?->toDateTimeString());
        $this->assertTrue(Activity::query()->where('event', ReportsAuditLogger::EVENT_SCHEDULE_FAILED)->exists());
        Log::shouldHaveReceived('error')->withArgs(fn (string $message): bool => $message === 'reports.schedule.failed')->once();
    }

    public function test_a_structure_that_left_the_owners_scope_stops_the_delivery(): void
    {
        Mail::fake();

        $owner = grantStructures($this->grantReports(User::factory()->create(), extra: [ReportPermissions::SCHEDULE]), [1, 2]);
        $recipient = $this->reportUser('Direktor', 'director@example.test');
        $schedule = app(ReportScheduleService::class)->save($owner, $this->scheduleData([$recipient->id], ['filters' => ['report' => 'headcount', 'structure_id' => 2], 'frequency' => 'daily']));

        $owner->roles()->first()?->structures()->sync([1]);
        app(StructureService::class)->forgetUser((int) $owner->id);

        $this->travelTo(Carbon::parse('2026-03-11 08:05:00'));
        $this->artisan('reports:send-scheduled')->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertSame('structure_out_of_scope', $schedule->fresh()?->last_error);
    }

    public function test_recipient_who_lost_access_is_skipped_and_none_left_fails(): void
    {
        Mail::fake();

        $owner = $this->scheduler();
        $recipient = $this->reportUser('Direktor', 'director@example.test');
        $schedule = app(ReportScheduleService::class)->save($owner, $this->scheduleData([$recipient->id], ['frequency' => 'daily']));

        $recipient->revokePermissionTo('show-personnel-reports');
        $this->travelTo(Carbon::parse('2026-03-11 08:05:00'));
        $this->artisan('reports:send-scheduled')->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertSame('no_recipients', $schedule->fresh()?->last_error);
    }

    public function test_paused_schedule_is_not_sent_and_send_now_works_from_the_component(): void
    {
        Mail::fake();

        $owner = $this->scheduler();
        $recipient = $this->reportUser('Direktor', 'director@example.test');
        $schedule = app(ReportScheduleService::class)->save($owner, $this->scheduleData([$recipient->id], ['frequency' => 'daily']));

        $this->actingAs($owner);
        Livewire::test(Schedules::class)->call('toggle', $schedule->id);
        $this->assertFalse($schedule->fresh()?->is_active);
        $this->assertNull($schedule->fresh()?->next_run_at);

        $this->travelTo(Carbon::parse('2026-03-11 08:05:00'));
        $this->artisan('reports:send-scheduled')->assertSuccessful();
        Mail::assertNothingSent();

        Livewire::test(Schedules::class)
            ->call('sendNow', $schedule->id)
            ->assertDispatched('notify', type: 'success');
        Mail::assertSent(ScheduledReportMail::class, 1);
        $this->assertSame($owner->id, auth()->id());
    }

    public function test_only_the_owner_manages_a_schedule(): void
    {
        $owner = $this->scheduler();
        $other = $this->scheduler();
        $schedule = app(ReportScheduleService::class)->save($owner, $this->scheduleData([$owner->id]));

        $this->actingAs($other);
        Livewire::test(Schedules::class)->assertDontSee($schedule->name)->call('edit', $schedule->id)->assertForbidden();
        Livewire::test(Schedules::class)->call('sendNow', $schedule->id)->assertForbidden();
        Livewire::test(Schedules::class)->call('confirmScheduleDeletion', $schedule->id)->assertForbidden();

        $this->actingAs($owner);
        Livewire::test(Schedules::class)
            ->call('confirmScheduleDeletion', $schedule->id)
            ->call('runConfirmedDeletion');

        $this->assertSame(0, ReportSchedule::query()->count());
        $this->assertTrue(Activity::query()->where('event', ReportsAuditLogger::EVENT_SCHEDULE_DELETED)->exists());
    }

    public function test_the_scheduler_runs_the_command_every_five_minutes(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->filter(fn (Event $event): bool => str_contains((string) $event->command, 'reports:send-scheduled'));

        $this->assertCount(1, $events);
        $this->assertSame('*/5 * * * *', $events->first()?->expression);
    }

    public function test_schedule_tables_migration_is_reversible(): void
    {
        $migration = require base_path('app/Modules/Reports/Database/Migrations/2026_10_11_130000_create_report_schedules_tables.php');

        $migration->down();
        $this->assertFalse(Schema::hasTable('report_schedules'));
        $this->assertFalse(Schema::hasTable('report_schedule_recipients'));

        $migration->up();
        $this->assertTrue(Schema::hasTable('report_schedule_recipients'));
    }

    private function scheduler(): User
    {
        return grantAllStructures($this->grantReports(User::factory()->create(), extra: [ReportPermissions::SCHEDULE]));
    }

    /**
     * @param  list<string>|null  $groups
     */
    private function reportUser(string $name, string $email, ?array $groups = null): User
    {
        return grantAllStructures($this->grantReports(User::factory()->create(['name' => $name, 'email' => $email]), groups: $groups, export: false));
    }

    /**
     * @param  list<int>  $recipientIds
     * @param  array<string,mixed>  $overrides
     * @return array<string,mixed>
     */
    private function scheduleData(array $recipientIds, array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Kadr hesabatı',
            'tab' => 'standard',
            'filters' => ['report' => 'headcount', 'structure_id' => null],
            'period' => 'current_month',
            'frequency' => 'monthly',
            'month_day' => 1,
            'send_time' => '08:00',
            'format' => 'xlsx',
            'recipient_ids' => $recipientIds,
        ];
    }

    private function personnel(string $tabelNo, int $structureId): Personnel
    {
        return Personnel::query()->create([
            'tabel_no' => $tabelNo,
            'surname' => 'Hesabat',
            'name' => 'Test',
            'patronymic' => 'Test',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'mobile' => '0500000000',
            'nationality_id' => 1,
            'pin' => substr(str_replace('-', '', $tabelNo).'12345', 0, 7),
            'residental_address' => 'Bakı',
            'education_degree_id' => 1,
            'structure_id' => $structureId,
            'position_id' => 1,
            'work_norm_id' => 1,
            'join_work_date' => '2024-01-01',
            'added_by' => User::query()->value('id') ?? User::factory()->create()->id,
        ]);
    }
}
