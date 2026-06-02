<?php

namespace Tests\Feature\Compliance;

use App\Models\Personnel;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

class ComplianceDocumentReminderCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_document_reminder_command_reports_expired_missing_and_expiring_documents(): void
    {
        Carbon::setTestNow('2026-05-11 09:00:00');

        $personnel = $this->makePersonnel();

        DB::table('personnel_cards')->insert([
            'tabel_no' => $personnel->tabel_no,
            'card_number' => 'CARD-REM-1',
            'valid_date' => '2026-05-20',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('personnel_passports')->insert([
            'tabel_no' => $personnel->tabel_no,
            'serial_number' => 'PASS-REM-1',
            'given_date' => '2025-01-01',
            'valid_date' => '2026-05-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $exitCode = Artisan::call('compliance:document-reminders', [
            '--days' => 30,
            '--json' => true,
        ]);

        $payload = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exitCode);
        $this->assertSame(30, $payload['days_ahead']);
        $this->assertGreaterThanOrEqual(3, $payload['count']);
        $this->assertContains('expired', collect($payload['rows'])->pluck('status')->all());
        $this->assertContains('expiring_30', collect($payload['rows'])->pluck('status')->all());
        $this->assertContains('missing', collect($payload['rows'])->pluck('status')->all());
    }

    public function test_document_reminder_scheduler_is_registered_when_enabled(): void
    {
        config()->set('compliance.document_expiry.reminders.schedule_enabled', true);
        config()->set('compliance.document_expiry.reminders.daily_at', '05:55');

        $schedule = app(Schedule::class);
        $method = new ReflectionMethod(\App\Console\Kernel::class, 'schedule');
        $method->setAccessible(true);
        $method->invoke(app(\App\Console\Kernel::class), $schedule);

        $commands = collect($schedule->events())->map(fn ($event) => (string) $event->command)->all();

        $this->assertTrue(
            collect($commands)->contains(fn (string $command): bool => str_contains($command, 'compliance:document-reminders --notify')),
            'Compliance reminder scheduler command is not registered.'
        );
    }

    private function makePersonnel(): Personnel
    {
        $this->seedReferenceData();

        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'REM-001',
            'surname' => 'Reminder',
            'name' => 'Employee',
            'patronymic' => 'Test',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'email' => 'compliance-reminder@example.test',
            'mobile' => '994501112233',
            'nationality_id' => 1,
            'pin' => 'REM000001',
            'residental_address' => 'Main st',
            'education_degree_id' => 1,
            'structure_id' => 1,
            'position_id' => 1,
            'work_norm_id' => 1,
            'join_work_date' => '2026-03-01',
            'added_by' => 1,
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
            'name' => 'Compliance HQ',
            'shortname' => 'CHQ',
            'parent_id' => null,
            'coefficient' => 1.10,
            'code' => 10,
            'level' => 1,
        ]);
        DB::table('positions')->insertOrIgnore(['id' => 1, 'name' => 'Compliance Officer']);
        DB::table('work_norms')->insertOrIgnore([
            'id' => 1,
            'name_az' => 'Tam iş günü',
            'name_en' => 'Full time',
            'name_ru' => 'Full time',
        ]);
    }
}
