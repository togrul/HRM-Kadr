<?php

namespace Tests\Feature\Personnel;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\OrderType;
use App\Models\Personnel;
use App\Models\PersonnelVacation;
use App\Models\User;
use App\Modules\Personnel\Console\Commands\RepairLegacySelfServiceVacationOrdersCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RepairLegacySelfServiceVacationOrdersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_repairs_approved_legacy_self_service_vacation_without_order(): void
    {
        $this->seedReferenceData();

        $reviewer = User::factory()->create(['is_active' => true]);
        $reviewer->givePermissionTo(Permission::findOrCreate('review-self-service-requests', 'web'));

        $employee = User::factory()->create(['is_active' => true, 'email' => 'employee@example.test']);
        $personnel = $this->makePersonnel($employee->email);

        $this->actingAs($employee);

        $vacation = PersonnelVacation::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'vacation_places' => 'Qax',
            'duration' => 4,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-04',
            'return_work_date' => '2026-05-05',
            'order_given_by' => 'Employee Self-Service',
            'order_no' => null,
            'order_date' => null,
            'vacation_days_total' => 0,
            'remaining_days' => 0,
            'approval_status' => 'approved',
            'submission_source' => 'employee_self_service',
            'submitted_by_user_id' => $employee->id,
            'added_by' => $employee->id,
        ]);

        $this->artisan(RepairLegacySelfServiceVacationOrdersCommand::class, [
            '--reviewer-id' => $reviewer->id,
            '--json' => true,
        ])->assertSuccessful();

        $this->assertNotNull($vacation->fresh()->order_no);
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
            'join_work_date' => '2026-03-01',
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
        if (! DB::table('order_categories')->where('id', 1)->exists()) {
            DB::table('order_categories')->insert([
                'id' => 1,
                'name_az' => 'Ümumi',
                'name_en' => 'General',
                'name_ru' => 'General',
            ]);
        }
        if (! DB::table('orders')->where('id', 1001)->exists()) {
            DB::table('orders')->insert([
                'id' => 1001,
                'order_category_id' => 1,
                'name' => 'Məzuniyyət əmri',
                'content' => 'Vacation order',
                'order_model' => \App\Models\PersonnelVacation::class,
                'blade' => Order::BLADE_VACATION,
            ]);
        }
        if (! DB::table('order_types')->where('id', 1)->exists()) {
            DB::table('order_types')->insert([
                'id' => 1,
                'order_id' => 1001,
                'name' => 'İllik məzuniyyət',
            ]);
        }
        OrderStatus::query()->firstOrCreate(['id' => 1], ['name' => 'Pending']);
        OrderStatus::query()->firstOrCreate(['id' => 2], ['name' => 'Approved']);
        OrderType::query()->firstOrCreate(['id' => 1], ['order_id' => 1001, 'name' => 'İllik məzuniyyət']);
    }
}
