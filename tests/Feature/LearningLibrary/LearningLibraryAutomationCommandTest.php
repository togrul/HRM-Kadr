<?php

namespace Tests\Feature\LearningLibrary;

use App\Models\EmployeeContentAsset;
use App\Models\Personnel;
use App\Models\User;
use App\Models\UserPersonnelLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class LearningLibraryAutomationCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_automation_command_auto_assigns_active_asset_to_recent_hire(): void
    {
        Storage::fake('employee_content');
        $this->seedReferenceData();

        $personnel = $this->makePersonnel('employee@example.test');
        EmployeeContentAsset::query()->create([
            'title' => 'Welcome PDF',
            'content_type' => 'pdf',
            'storage_disk' => 'employee_content',
            'storage_path' => UploadedFile::fake()->create('welcome.pdf')->store('learning-content', 'employee_content'),
            'visibility' => 'internal',
            'is_active' => true,
            'auto_assign_new_hires' => true,
            'is_required' => true,
        ]);

        $this->artisan('learning-library:automation --json')
            ->assertSuccessful();

        $this->assertDatabaseHas('employee_content_assignments', [
            'personnel_id' => $personnel->id,
        ]);
    }

    public function test_automation_command_sets_reminder_timestamp_and_respects_cooldown(): void
    {
        Notification::fake();
        Storage::fake('employee_content');
        $this->seedReferenceData();

        config()->set('personnel.my_hr.learning.automation.reminder_days_ahead', 3);
        config()->set('personnel.my_hr.learning.automation.reminder_cooldown_hours', 24);
        config()->set('personnel.my_hr.learning.automation.max_reminders_per_run', 20);

        $user = User::factory()->create(['is_active' => true, 'email' => 'employee@example.test']);
        $personnel = $this->makePersonnel($user->email);
        UserPersonnelLink::query()->create([
            'user_id' => $user->id,
            'personnel_id' => $personnel->id,
            'resolution_source' => 'manual',
            'resolved_at' => now(),
        ]);

        $asset = EmployeeContentAsset::query()->create([
            'title' => 'Welcome PDF',
            'content_type' => 'pdf',
            'storage_disk' => 'employee_content',
            'storage_path' => UploadedFile::fake()->create('welcome.pdf')->store('learning-content', 'employee_content'),
            'visibility' => 'internal',
            'is_active' => true,
            'is_required' => true,
        ]);

        DB::table('employee_content_assignments')->insert([
            'asset_id' => $asset->id,
            'personnel_id' => $personnel->id,
            'assigned_by' => $user->id,
            'assigned_at' => now()->subDay(),
            'due_at' => now()->addDay(),
            'status' => 'assigned',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('learning-library:automation --json')
            ->assertSuccessful();

        $this->assertDatabaseMissing('employee_content_assignments', [
            'personnel_id' => $personnel->id,
            'last_reminder_at' => null,
        ]);

        $firstReminderAt = DB::table('employee_content_assignments')
            ->where('personnel_id', $personnel->id)
            ->value('last_reminder_at');

        $this->assertNotNull($firstReminderAt);

        $this->artisan('learning-library:automation --json')
            ->assertSuccessful();

        $secondReminderAt = DB::table('employee_content_assignments')
            ->where('personnel_id', $personnel->id)
            ->value('last_reminder_at');

        $this->assertSame($firstReminderAt, $secondReminderAt);
    }

    private function makePersonnel(string $email): Personnel
    {
        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'TB'.Str::upper(Str::random(6)),
            'surname' => 'Doe',
            'name' => 'Jane',
            'patronymic' => 'Smith',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'email' => $email,
            'mobile' => '994501112233',
            'nationality_id' => 1,
            'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
            'residental_address' => 'Main st',
            'education_degree_id' => 1,
            'structure_id' => 1,
            'position_id' => 1,
            'work_norm_id' => 1,
            'join_work_date' => now()->subDay()->toDateString(),
            'added_by' => 1,
            'is_pending' => false,
        ]));
    }

    private function seedReferenceData(): void
    {
        if (! DB::table('countries')->where('id', 1)->exists()) {
            DB::table('countries')->insert(['id' => 1, 'code' => 'AZ']);
        }
        if (! DB::table('country_translations')->where('id', 1)->exists()) {
            DB::table('country_translations')->insert(['id' => 1, 'country_id' => 1, 'locale' => 'az', 'title' => 'Azərbaycan']);
        }
        if (! DB::table('education_degrees')->where('id', 1)->exists()) {
            DB::table('education_degrees')->insert(['id' => 1, 'title_az' => 'Bakalavr', 'title_en' => 'Bachelor', 'title_ru' => 'Bachelor']);
        }
        if (! DB::table('structures')->where('id', 1)->exists()) {
            DB::table('structures')->insert(['id' => 1, 'name' => 'HQ', 'shortname' => 'HQ', 'parent_id' => null, 'coefficient' => 1.10, 'code' => 10, 'level' => 1]);
        }
        if (! DB::table('positions')->where('id', 1)->exists()) {
            DB::table('positions')->insert(['id' => 1, 'name' => 'Officer']);
        }
        if (! DB::table('work_norms')->where('id', 1)->exists()) {
            DB::table('work_norms')->insert(['id' => 1, 'name_az' => 'Tam iş günü', 'name_en' => 'Full time', 'name_ru' => 'Full time']);
        }
    }
}
