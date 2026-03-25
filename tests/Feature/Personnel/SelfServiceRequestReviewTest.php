<?php

namespace Tests\Feature\Personnel;

use App\Enums\OrderStatusEnum;
use App\Models\Leave;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\OrderType;
use App\Models\Personnel;
use App\Models\Role;
use App\Models\User;
use App\Modules\Personnel\Livewire\MyHr\SelfServiceRequestReviews;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SelfServiceRequestReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_approve_pending_self_service_leave_from_review_queue(): void
    {
        $this->seedReferenceData();

        $reviewer = User::factory()->create(['is_active' => true]);
        $reviewer->givePermissionTo(Permission::findOrCreate('review-self-service-requests', 'web'));

        $requester = User::factory()->create(['is_active' => true, 'email' => 'employee@example.test']);
        $personnel = $this->makePersonnel($requester->email);

        $leave = Leave::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'leave_type_id' => 1,
            'starts_at' => '2026-04-01',
            'ends_at' => '2026-04-01',
            'duration_unit' => 'day',
            'total_days' => 1,
            'reason' => 'Şəxsi səbəb',
            'status_id' => OrderStatusEnum::PENDING->value,
            'submission_source' => 'employee_self_service',
            'submitted_by_user_id' => $requester->id,
        ]);

        $this->actingAs($reviewer);

        Livewire::test(SelfServiceRequestReviews::class)
            ->set('notes.leave-'.$leave->id, 'Uyğundur')
            ->call('approve', 'leave', $leave->id)
            ->assertDispatched('notify');

        $this->assertSame(OrderStatusEnum::APPROVED->value, (int) $leave->fresh()->status_id);
    }

    public function test_reviewer_with_global_permission_can_switch_to_all_scope_and_see_audit_fields(): void
    {
        $this->seedReferenceData();

        $reviewer = User::factory()->create(['is_active' => true]);
        $reviewer->givePermissionTo(Permission::findOrCreate('review-all-self-service-requests', 'web'));

        $requester = User::factory()->create(['is_active' => true, 'email' => 'employee@example.test']);
        $personnel = $this->makePersonnel($requester->email);

        Leave::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'leave_type_id' => 1,
            'starts_at' => '2026-04-01',
            'ends_at' => '2026-04-01',
            'duration_unit' => 'day',
            'total_days' => 1,
            'reason' => 'Şəxsi səbəb',
            'status_id' => OrderStatusEnum::PENDING->value,
            'submission_source' => 'employee_self_service',
            'submitted_by_user_id' => $requester->id,
            'approval_route_source' => 'hierarchy_policy',
        ]);

        $this->actingAs($reviewer);

        Livewire::test(SelfServiceRequestReviews::class)
            ->set('scopeFilter', 'all')
            ->assertSet('scopeFilter', 'all')
            ->assertSee(__('personnel::my_hr.review.labels.audit_timeline'))
            ->assertSee(__('personnel::my_hr.review.audit.route_source'))
            ->assertSee(__('personnel::my_hr.review.audit.hr_line'));
    }

    public function test_hr_can_approve_pending_self_service_vacation_and_it_gets_operational_order_binding(): void
    {
        $this->seedReferenceData();

        $reviewer = User::factory()->create(['is_active' => true]);
        $reviewer->givePermissionTo(Permission::findOrCreate('review-self-service-requests', 'web'));

        $requester = User::factory()->create(['is_active' => true, 'email' => 'vacation.employee@example.test']);
        $personnel = $this->makePersonnel($requester->email);

        $this->actingAs($requester);

        $vacation = \App\Models\PersonnelVacation::query()->create([
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
            'approval_status' => 'pending',
            'submission_source' => 'employee_self_service',
            'submitted_by_user_id' => $requester->id,
            'added_by' => $requester->id,
        ]);

        $this->actingAs($reviewer);

        Livewire::test(SelfServiceRequestReviews::class)
            ->set('notes.vacation-'.$vacation->id, 'Uyğundur')
            ->call('approve', 'vacation', $vacation->id)
            ->assertDispatched('notify');

        $vacation->refresh();

        $this->assertSame('approved', (string) $vacation->approval_status);
        $this->assertNotNull($vacation->order_no);
        $this->assertNotNull($vacation->order);
        $this->assertSame(Order::BLADE_VACATION, (string) $vacation->order?->order?->blade);
        $this->assertDatabaseHas('order_logs', [
            'order_no' => $vacation->order_no,
            'order_type_id' => 1,
        ]);
        $this->assertSame(1, \App\Models\PersonnelVacation::query()->count());
    }

    public function test_vacation_operational_order_binding_falls_back_to_order_model_when_blade_is_missing(): void
    {
        $this->seedReferenceData();

        DB::table('orders')
            ->where('id', 1001)
            ->update([
                'blade' => null,
                'order_model' => \App\Models\PersonnelVacation::class,
            ]);

        $reviewer = User::factory()->create(['is_active' => true]);
        $reviewer->givePermissionTo(Permission::findOrCreate('review-self-service-requests', 'web'));

        $requester = User::factory()->create(['is_active' => true, 'email' => 'vacation.fallback@example.test']);
        $personnel = $this->makePersonnel($requester->email);

        $this->actingAs($requester);

        $vacation = \App\Models\PersonnelVacation::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'vacation_places' => 'Şəki',
            'duration' => 5,
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-14',
            'return_work_date' => '2026-06-15',
            'order_given_by' => 'Employee Self-Service',
            'order_no' => null,
            'order_date' => null,
            'vacation_days_total' => 0,
            'remaining_days' => 0,
            'approval_status' => 'pending',
            'submission_source' => 'employee_self_service',
            'submitted_by_user_id' => $requester->id,
            'added_by' => $requester->id,
        ]);

        $this->actingAs($reviewer);

        Livewire::test(SelfServiceRequestReviews::class)
            ->call('approve', 'vacation', $vacation->id)
            ->assertDispatched('notify');

        $this->assertNotNull($vacation->fresh()->order_no);
        $this->assertSame(1, \App\Models\PersonnelVacation::query()->count());
    }

    public function test_vacation_operational_order_binding_creates_missing_order_type_from_existing_order(): void
    {
        $this->seedReferenceData();

        DB::table('order_types')->where('id', 1)->delete();

        $reviewer = User::factory()->create(['is_active' => true]);
        $reviewer->givePermissionTo(Permission::findOrCreate('review-self-service-requests', 'web'));

        $requester = User::factory()->create(['is_active' => true, 'email' => 'vacation.create-type@example.test']);
        $personnel = $this->makePersonnel($requester->email);

        $this->actingAs($requester);

        $vacation = \App\Models\PersonnelVacation::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'vacation_places' => 'Bakı',
            'duration' => 3,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-03',
            'return_work_date' => '2026-07-04',
            'order_given_by' => 'Employee Self-Service',
            'order_no' => null,
            'order_date' => null,
            'vacation_days_total' => 0,
            'remaining_days' => 0,
            'approval_status' => 'pending',
            'submission_source' => 'employee_self_service',
            'submitted_by_user_id' => $requester->id,
            'added_by' => $requester->id,
        ]);

        $this->actingAs($reviewer);

        Livewire::test(SelfServiceRequestReviews::class)
            ->call('approve', 'vacation', $vacation->id)
            ->assertDispatched('notify');

        $vacation->refresh();

        $this->assertNotNull($vacation->order_no);
        $this->assertDatabaseHas('order_types', [
            'order_id' => 1001,
        ]);
        $this->assertDatabaseHas('order_log_personnels', [
            'order_no' => $vacation->order_no,
            'tabel_no' => $personnel->tabel_no,
        ]);
        $this->assertSame(1, \App\Models\PersonnelVacation::query()->count());
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
        if (! DB::table('leave_types')->where('id', 1)->exists()) {
            DB::table('leave_types')->insert(['id' => 1, 'name' => 'İllik', 'attendance_code' => 'IL', 'max_days' => 20, 'requires_document' => false]);
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
        Role::findOrCreate('Employee Self-Service', 'web');
        OrderStatus::query()->firstOrCreate(['id' => OrderStatusEnum::PENDING->value], ['name' => 'Pending']);
        OrderStatus::query()->firstOrCreate(['id' => OrderStatusEnum::APPROVED->value], ['name' => 'Approved']);
        OrderStatus::query()->firstOrCreate(['id' => OrderStatusEnum::CANCELLED->value], ['name' => 'Cancelled']);
    }
}
