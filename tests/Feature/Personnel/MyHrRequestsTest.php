<?php

namespace Tests\Feature\Personnel;

use App\Enums\OrderStatusEnum;
use App\Models\Leave;
use App\Models\OrderStatus;
use App\Models\Personnel;
use App\Models\PersonnelBusinessTrip;
use App\Models\PersonnelVacation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MyHrRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_hr_requests_tab_shows_unified_leave_vacation_and_business_trip_history(): void
    {
        $this->seedReferenceData();

        $user = User::factory()->create([
            'is_active' => true,
            'email' => 'employee@example.test',
        ]);
        $user->givePermissionTo(Permission::findOrCreate('show-my-hr', 'web'));
        $this->actingAs($user);

        $personnel = $this->makePersonnel($user->email);

        Leave::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'leave_type_id' => 1,
            'starts_at' => '2026-03-20',
            'ends_at' => '2026-03-20',
            'duration_unit' => 'day',
            'total_days' => 1,
            'reason' => 'Ailə vəziyyəti',
            'status_id' => OrderStatusEnum::PENDING->value,
        ]);

        PersonnelVacation::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'vacation_places' => 'Quba',
            'duration' => 7,
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-07',
            'return_work_date' => '2026-04-08',
            'order_given_by' => 'HR',
            'order_no' => 'VAC-001',
            'order_date' => '2026-03-15 09:00:00',
            'added_by' => $user->id,
        ]);

        PersonnelBusinessTrip::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'location' => 'Gəncə',
            'description' => 'Regional görüş',
            'start_date' => '2026-04-10',
            'end_date' => '2026-04-12',
            'order_given_by' => 'HR',
            'order_no' => 'BT-001',
            'order_date' => '2026-03-18 10:00:00',
            'added_by' => $user->id,
        ]);

        $this->get(route('my-hr', ['tab' => 'requests']))
            ->assertOk()
            ->assertSee('Ərizələrim')
            ->assertSee('Ailə vəziyyəti')
            ->assertSee('Quba')
            ->assertSee('Gəncə')
            ->assertSee('İcazə')
            ->assertSee('Məzuniyyət')
            ->assertSee('Ezamiyyət');
    }

    public function test_employee_can_submit_leave_request_from_my_hr_requests_tab(): void
    {
        $this->seedReferenceData();

        $user = User::factory()->create([
            'is_active' => true,
            'email' => 'employee@example.test',
        ]);

        $user->givePermissionTo([
            Permission::findOrCreate('show-my-hr', 'web'),
            Permission::findOrCreate('submit-self-service-leaves', 'web'),
        ]);

        $personnel = $this->makePersonnel($user->email);
        $approver = $this->makeApproverPersonnel('approver@example.test');

        $this->actingAs($user);

        Livewire::test(\App\Modules\Personnel\Livewire\MyHr\MyHrRequests::class, ['personnelId' => $personnel->id])
            ->call('openCreateForm', 'leave')
            ->set('leaveForm.leave_type_id', 1)
            ->set('leaveForm.starts_at', '2026-04-01')
            ->set('leaveForm.ends_at', '2026-04-02')
            ->set('leaveForm.duration_unit', 'day')
            ->set('leaveForm.reason', 'Şəxsi səbəb')
            ->call('storeLeaveRequest')
            ->assertDispatched('notify');

        $leave = Leave::query()->latest('id')->first();

        $this->assertNotNull($leave);
        $this->assertSame($personnel->tabel_no, $leave->tabel_no);
        $this->assertSame('employee_self_service', $leave->submission_source);
        $this->assertSame($user->id, $leave->submitted_by_user_id);
        $this->assertSame(OrderStatusEnum::PENDING->value, (int) $leave->status_id);
        $this->assertSame($approver->id, (int) $leave->assigned_to);
        $this->assertSame('hierarchy_policy', $leave->approval_route_source);
    }

    public function test_employee_can_submit_vacation_and_business_trip_requests_from_my_hr_requests_tab(): void
    {
        $this->seedReferenceData();

        $user = User::factory()->create([
            'is_active' => true,
            'email' => 'employee@example.test',
        ]);

        $user->givePermissionTo([
            Permission::findOrCreate('show-my-hr', 'web'),
            Permission::findOrCreate('submit-self-service-vacations', 'web'),
            Permission::findOrCreate('submit-self-service-business-trips', 'web'),
        ]);

        $personnel = $this->makePersonnel($user->email);
        $approver = $this->makeApproverPersonnel('approver@example.test');

        $this->actingAs($user);

        Livewire::test(\App\Modules\Personnel\Livewire\MyHr\MyHrRequests::class, ['personnelId' => $personnel->id])
            ->call('openCreateForm', 'vacation')
            ->set('vacationForm.vacation_places', 'Qax')
            ->set('vacationForm.start_date', '2026-05-01')
            ->set('vacationForm.end_date', '2026-05-05')
            ->call('storeVacationRequest')
            ->assertDispatched('notify');

        Livewire::test(\App\Modules\Personnel\Livewire\MyHr\MyHrRequests::class, ['personnelId' => $personnel->id])
            ->call('openCreateForm', 'business_trip')
            ->set('businessTripForm.location', 'Şəki')
            ->set('businessTripForm.start_date', '2026-06-01')
            ->set('businessTripForm.end_date', '2026-06-03')
            ->set('businessTripForm.description', 'İş görüşü')
            ->call('storeBusinessTripRequest')
            ->assertDispatched('notify');

        $vacation = PersonnelVacation::query()->latest('id')->first();
        $trip = PersonnelBusinessTrip::query()->latest('id')->first();

        $this->assertNotNull($vacation);
        $this->assertSame('pending', $vacation->approval_status);
        $this->assertSame('employee_self_service', $vacation->submission_source);
        $this->assertSame($user->id, $vacation->submitted_by_user_id);
        $this->assertSame($approver->id, (int) $vacation->approver_personnel_id);
        $this->assertSame('hierarchy_policy', $vacation->approval_route_source);

        $this->assertNotNull($trip);
        $this->assertSame('pending', $trip->approval_status);
        $this->assertSame('employee_self_service', $trip->submission_source);
        $this->assertSame($user->id, $trip->submitted_by_user_id);
        $this->assertSame($approver->id, (int) $trip->approver_personnel_id);
        $this->assertSame('hierarchy_policy', $trip->approval_route_source);
    }

    public function test_switching_request_create_form_updates_active_selection(): void
    {
        $this->seedReferenceData();

        $user = User::factory()->create([
            'is_active' => true,
            'email' => 'employee@example.test',
        ]);

        $user->givePermissionTo([
            Permission::findOrCreate('show-my-hr', 'web'),
            Permission::findOrCreate('submit-self-service-leaves', 'web'),
            Permission::findOrCreate('submit-self-service-vacations', 'web'),
            Permission::findOrCreate('submit-self-service-business-trips', 'web'),
        ]);

        $personnel = $this->makePersonnel($user->email);

        $this->actingAs($user);

        Livewire::test(\App\Modules\Personnel\Livewire\MyHr\MyHrRequests::class, ['personnelId' => $personnel->id])
            ->call('openCreateForm', 'leave')
            ->assertSet('activeCreateForm', 'leave')
            ->call('openCreateForm', 'vacation')
            ->assertSet('activeCreateForm', 'vacation')
            ->call('openCreateForm', 'business_trip')
            ->assertSet('activeCreateForm', 'business_trip');
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

    private function makeApproverPersonnel(string $email): Personnel
    {
        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'TB'.Str::upper(Str::random(6)),
            'surname' => 'Boss',
            'name' => 'Big',
            'patronymic' => 'Chief',
            'birthdate' => '1980-01-01',
            'gender' => 1,
            'email' => $email,
            'mobile' => '994501112244',
            'nationality_id' => 1,
            'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
            'residental_address' => 'Main st',
            'education_degree_id' => 1,
            'structure_id' => 1,
            'position_id' => 2,
            'work_norm_id' => 1,
            'join_work_date' => '2020-03-01',
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
            DB::table('country_translations')->insert([
                'id' => 1,
                'country_id' => 1,
                'locale' => 'az',
                'title' => 'Azərbaycan',
            ]);
        }

        if (! DB::table('education_degrees')->where('id', 1)->exists()) {
            DB::table('education_degrees')->insert([
                'id' => 1,
                'title_az' => 'Bakalavr',
                'title_en' => 'Bachelor',
                'title_ru' => 'Bachelor',
            ]);
        }

        if (! DB::table('structures')->where('id', 1)->exists()) {
            DB::table('structures')->insert([
                'id' => 1,
                'name' => 'HQ',
                'shortname' => 'HQ',
                'parent_id' => null,
                'coefficient' => 1.10,
                'code' => 10,
                'level' => 1,
            ]);
        }

        if (! DB::table('positions')->where('id', 1)->exists()) {
            DB::table('positions')->insert([
                'id' => 1,
                'name' => 'Officer',
                'approval_rank' => 10,
                'is_approval_target' => false,
            ]);
        }

        if (! DB::table('positions')->where('id', 2)->exists()) {
            DB::table('positions')->insert([
                'id' => 2,
                'name' => 'Section Chief',
                'approval_rank' => 20,
                'is_approval_target' => true,
            ]);
        }

        if (! DB::table('work_norms')->where('id', 1)->exists()) {
            DB::table('work_norms')->insert([
                'id' => 1,
                'name_az' => 'Tam iş günü',
                'name_en' => 'Full time',
                'name_ru' => 'Full time',
            ]);
        }

        if (! DB::table('leave_types')->where('id', 1)->exists()) {
            DB::table('leave_types')->insert([
                'id' => 1,
                'name' => 'İllik',
                'attendance_code' => 'IL',
                'max_days' => 20,
                'requires_document' => false,
            ]);
        }

        OrderStatus::query()->firstOrCreate(['id' => OrderStatusEnum::PENDING->value], ['name' => 'Pending']);
    }
}
