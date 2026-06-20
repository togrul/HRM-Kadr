<?php

namespace Tests\Feature\EmployeeLifecycle;

use App\Models\Personnel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class LifecycleDeadlineReminderCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_sends_lifecycle_deadline_reminders_and_respects_cooldown(): void
    {
        Carbon::setTestNow('2026-05-08 09:00:00');

        $this->seedReferenceData();

        $owner = User::factory()->create(['is_active' => true, 'name' => 'Lifecycle Owner']);
        $taskOwner = User::factory()->create(['is_active' => true, 'name' => 'Task Owner']);
        $personnel = $this->makePersonnel($owner->id);

        $eventId = DB::table('employee_lifecycle_events')->insertGetId([
            'personnel_id' => $personnel->id,
            'tabel_no' => $personnel->tabel_no,
            'type' => 'onboarding',
            'status' => 'in_progress',
            'title' => 'Yeni əməkdaş onboarding',
            'effective_date' => '2026-05-08',
            'deadline_at' => '2026-05-10',
            'owner_user_id' => $owner->id,
            'created_by' => $owner->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $taskId = DB::table('employee_lifecycle_tasks')->insertGetId([
            'event_id' => $eventId,
            'title' => 'IT hesablarını aktiv et',
            'owner_type' => 'it',
            'owner_user_id' => $taskOwner->id,
            'due_at' => '2026-05-09',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(0, Artisan::call('employee-lifecycle:send-reminders', [
            '--days-ahead' => 3,
            '--cooldown-hours' => 24,
            '--max' => 10,
            '--json' => true,
        ]));
        $firstOutput = Artisan::output();

        $this->assertStringContainsString('"event_reminders_sent":1', $firstOutput);
        $this->assertStringContainsString('"task_reminders_sent":1', $firstOutput);

        $this->assertSame(1, $owner->notifications()->count());
        $this->assertSame(1, $taskOwner->notifications()->count());
        $this->assertSame('employeeLifecycleDeadlineReminder', $taskOwner->notifications()->first()->data['action']);

        $this->assertDatabaseHas('employee_lifecycle_events', [
            'id' => $eventId,
            'reminder_count' => 1,
        ]);
        $this->assertDatabaseHas('employee_lifecycle_tasks', [
            'id' => $taskId,
            'reminder_count' => 1,
        ]);

        $this->assertSame(0, Artisan::call('employee-lifecycle:send-reminders', [
            '--days-ahead' => 3,
            '--cooldown-hours' => 24,
            '--max' => 10,
            '--json' => true,
        ]));
        $secondOutput = Artisan::output();

        $this->assertStringContainsString('"event_reminders_sent":0', $secondOutput);
        $this->assertStringContainsString('"task_reminders_sent":0', $secondOutput);

        $this->assertSame(1, $owner->notifications()->count());
        $this->assertSame(1, $taskOwner->notifications()->count());
    }

    private function makePersonnel(int $userId): Personnel
    {
        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'LC'.Str::upper(Str::random(6)),
            'surname' => 'Lifecycle',
            'name' => 'Employee',
            'patronymic' => 'Reminder',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'email' => 'lifecycle-reminder@example.test',
            'mobile' => '994501112233',
            'nationality_id' => 1,
            'pin' => 'LC'.str_pad((string) random_int(1, 99999), 7, '0', STR_PAD_LEFT),
            'residental_address' => 'Main st',
            'education_degree_id' => 1,
            'structure_id' => 1,
            'position_id' => 1,
            'work_norm_id' => 1,
            'join_work_date' => '2026-05-01',
            'added_by' => $userId,
            'is_pending' => false,
        ]));
    }

    private function seedReferenceData(): void
    {
        DB::table('countries')->insertOrIgnore(['id' => 1, 'code' => 'AZ']);
        DB::table('country_translations')->insertOrIgnore([
            'id' => 1,
            'country_id' => 1,
            'locale' => 'az',
            'title' => 'Azərbaycan',
        ]);
        DB::table('education_degrees')->insertOrIgnore([
            'id' => 1,
            'title_az' => 'Bakalavr',
            'title_en' => 'Bachelor',
            'title_ru' => 'Bachelor',
        ]);
        DB::table('structures')->insertOrIgnore([
            'id' => 1,
            'name' => 'Lifecycle HQ',
            'shortname' => 'LHQ',
            'parent_id' => null,
            'coefficient' => 1.10,
            'code' => 30,
            'level' => 1,
        ]);
        DB::table('positions')->insertOrIgnore([
            'id' => 1,
            'name' => 'Lifecycle Officer',
        ]);
        DB::table('work_norms')->insertOrIgnore([
            'id' => 1,
            'name_az' => 'Tam iş günü',
            'name_en' => 'Full time',
            'name_ru' => 'Full time',
        ]);
    }
}
