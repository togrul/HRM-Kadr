<?php

namespace Tests\Feature\Leaves;

use App\Enums\OrderStatusEnum;
use App\Livewire\Forms\LeaveForm;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\OrderStatus;
use App\Models\Personnel;
use App\Models\PersonnelBusinessTrip;
use App\Models\PersonnelVacation;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Modules\Leaves\Livewire\AddLeave;
use App\Modules\Leaves\Livewire\DeleteLeave;
use App\Modules\Leaves\Livewire\Leaves;
use App\Modules\UI\Livewire\Confirmation\AddComment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * "İcazə əlavə et" must not let the creator bypass the approval route, approve their own
 * leave, upload an active document, or put an employee in two absences at once.
 */
class LeaveRecordIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private Personnel $employee;

    private Personnel $manager;

    private LeaveType $type;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([[10, 'Təsdiq gözləyən'], [20, 'Təsdiqlənmiş'], [30, 'Ləğv edilmiş']] as [$id, $name]) {
            OrderStatus::query()->firstOrCreate(['id' => $id], ['locale' => 'az', 'name' => $name]);
        }

        DB::table('countries')->insertOrIgnore(['id' => 1, 'code' => 'AZ']);
        DB::table('education_degrees')->insertOrIgnore(['id' => 1, 'title_az' => 'Bakalavr']);
        DB::table('work_norms')->insertOrIgnore(['id' => 1, 'name_az' => 'Tam ştat']);
        Structure::query()->firstOrCreate(['id' => 1], ['name' => 'İR', 'shortname' => 'IR', 'code' => 1, 'level' => 1]);
        Position::query()->firstOrCreate(['id' => 1], ['name' => 'Məsləhətçi']);

        $this->employee = $this->makePersonnel('TB-EMP', 'Əliyev');
        $this->manager = $this->makePersonnel('TB-MNG', 'Rəhbərov');
        $this->type = LeaveType::query()->create(['name' => 'Xəstəlik icazəsi', 'max_days' => 0, 'requires_document' => false]);
    }

    public function test_the_form_defaults_to_pending_and_hides_approved_without_the_permission(): void
    {
        $this->actingAs($this->user(['add-leaves']));

        $component = Livewire::test(AddLeave::class)->assertSet('leave.status_id', OrderStatusEnum::PENDING->value);

        $this->assertSame([OrderStatusEnum::PENDING->value], array_column($component->instance()->statuses(), 'id'));
    }

    public function test_recording_an_approved_leave_without_the_permission_is_rejected_server_side(): void
    {
        $this->actingAs($this->user(['add-leaves']));

        $this->fillForm(Livewire::test(AddLeave::class))
            ->set('leave.status_id', OrderStatusEnum::APPROVED->value)
            ->call('store')
            ->assertHasErrors(['leave.status_id']);

        $this->assertSame(0, Leave::query()->count());
    }

    public function test_a_user_with_approve_leaves_records_it_approved_by_themselves(): void
    {
        $user = $this->user(['add-leaves', 'approve-leaves']);
        $this->actingAs($user);

        $component = $this->fillForm(Livewire::test(AddLeave::class));
        $this->assertContains(OrderStatusEnum::APPROVED->value, array_column($component->instance()->statuses(), 'id'));

        $component->set('leave.status_id', OrderStatusEnum::APPROVED->value)
            ->call('store')
            ->assertHasNoErrors()
            ->assertDispatched('leaveAdded');

        $leave = Leave::query()->sole();
        $this->assertSame(OrderStatusEnum::APPROVED->value, (int) $leave->status_id);
        $this->assertNotNull($leave->approved_at);
        // Təsdiqçi sütunu əməkdaş id-sidir; kartla bağı olmayan istifadəçinin id-si ora yazılmır.
        $this->assertNull($leave->approved_by);
        $this->assertSame(1, $leave->logs()->where('status_id', OrderStatusEnum::APPROVED->value)->count());
    }

    public function test_the_employee_is_neither_offered_nor_accepted_as_their_own_approver(): void
    {
        $this->actingAs($this->user(['add-leaves']));

        $component = $this->fillForm(Livewire::test(AddLeave::class))
            ->call('setAssignmentMode', 'manual')
            ->set('assignedSearch', 'Əliyev');

        $this->assertFalse($component->instance()->assignedPersonnelList()->contains('tabel_no', $this->employee->tabel_no));

        $component->call('selectPersonnel', $this->employee->tabel_no, $this->employee->fullname, 'assigned_to', $this->employee->id)
            ->assertHasErrors(['leave.assigned_to.id']);

        // Forcing it into the payload is refused on save too.
        $component->set('leave.assigned_to', ['id' => $this->employee->id, 'fullname' => $this->employee->fullname])
            ->call('store')
            ->assertHasErrors(['leave.assigned_to.id']);

        $this->assertSame(0, Leave::query()->count());
    }

    public function test_a_leave_overlapping_a_vacation_is_rejected(): void
    {
        $this->actingAs($this->user(['add-leaves']));

        PersonnelVacation::query()->create([
            'tabel_no' => $this->employee->tabel_no,
            'vacation_places' => 'Quba',
            'duration' => 10,
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-29',
            'return_work_date' => '2026-10-30',
            'order_given_by' => 'HR',
            'vacation_days_total' => 30,
            'remaining_days' => 20,
            'added_by' => 1,
        ]);

        $this->fillForm(Livewire::test(AddLeave::class), '2026-10-12', '2026-10-20')
            ->call('store')
            ->assertHasErrors(['leave.starts_at' => ['Bu tarixlərdə əməkdaşın artıq məzuniyyət qeydi var (20.10.2026 – 29.10.2026).']]);

        $this->assertSame(0, Leave::query()->count());
    }

    public function test_approving_a_pending_leave_that_now_overlaps_a_business_trip_is_refused(): void
    {
        // Təsdiqləyici: icazəni görür, redaktə edir və təsdiq hüququ var.
        $this->actingAs($this->user(['show-leaves', 'edit-leaves', 'approve-leaves']));

        $leave = Leave::withoutEvents(fn () => Leave::query()->create([
            'tabel_no' => $this->employee->tabel_no,
            'leave_type_id' => $this->type->id,
            'starts_at' => '2026-11-02',
            'ends_at' => '2026-11-03',
            'duration_unit' => 'day',
            'reason' => 'Ailə',
            'status_id' => OrderStatusEnum::PENDING->value,
        ]));

        PersonnelBusinessTrip::query()->create([
            'tabel_no' => $this->employee->tabel_no,
            'location' => 'Gəncə',
            'start_date' => '2026-11-03',
            'end_date' => '2026-11-05',
            'order_given_by' => 'HR',
        ]);

        Livewire::test(AddComment::class)
            ->call('confirmComment', 'APPROVED', $leave->id)
            ->assertDispatched('notify', type: 'error');

        $this->assertSame(OrderStatusEnum::PENDING->value, (int) $leave->fresh()->status_id);
    }

    public function test_half_day_leaves_on_different_halves_do_not_clash(): void
    {
        $this->actingAs($this->user(['add-leaves']));

        Leave::withoutEvents(fn () => Leave::query()->create([
            'tabel_no' => $this->employee->tabel_no,
            'leave_type_id' => $this->type->id,
            'starts_at' => '2026-11-10',
            'ends_at' => '2026-11-10',
            'duration_unit' => 'half_day',
            'partial_day_part' => 'first_half',
            'reason' => 'Səhər',
            'status_id' => OrderStatusEnum::PENDING->value,
        ]));

        $this->fillForm(Livewire::test(AddLeave::class), '2026-11-10', '2026-11-10')
            ->set('leave.duration_unit', 'half_day')
            ->set('leave.partial_day_part', 'second_half')
            ->call('store')
            ->assertHasNoErrors();

        $this->fillForm(Livewire::test(AddLeave::class), '2026-11-10', '2026-11-10')
            ->set('leave.duration_unit', 'half_day')
            ->set('leave.partial_day_part', 'first_half')
            ->call('store')
            ->assertHasErrors(['leave.starts_at']);
    }

    public function test_an_active_or_disguised_document_is_rejected(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->actingAs($this->user(['add-leaves']));

        $html = UploadedFile::fake()->createWithContent('note.html', '<html><script>alert(1)</script></html>');
        // Livewire's test uploads report the client MIME type; in a real request it is the
        // content-sniffed one, which for a renamed text file is text/plain.
        $fakePdf = UploadedFile::fake()->createWithContent('scan.pdf', 'just some plain text, not a pdf')->mimeType('text/plain');

        foreach ([$html, $fakePdf] as $file) {
            $this->fillForm(Livewire::test(AddLeave::class))
                ->set('leave.document_path', $file)
                ->call('store')
                ->assertHasErrors(['leave.document_path']);
        }

        $this->assertSame(0, Leave::query()->count());
    }

    public function test_the_document_rule_sniffs_content_not_the_file_name(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'leave');
        file_put_contents($path, 'just some plain text, not a pdf');
        $renamed = new UploadedFile($path, 'scan.pdf', 'application/pdf', null, true);

        $validator = Validator::make(['file' => $renamed], ['file' => ['file', 'mimes:'.LeaveForm::ALLOWED_DOCUMENT_EXTENSIONS]]);

        $this->assertTrue($validator->fails());
        @unlink($path);
    }

    public function test_the_end_date_message_does_not_repeat_the_word_date(): void
    {
        $this->actingAs($this->user(['add-leaves']));
        app()->setLocale('az');

        $component = $this->fillForm(Livewire::test(AddLeave::class), '2026-10-20', '2026-10-10')->call('store');

        $message = $component->errors()->first('leave.ends_at');
        $this->assertStringNotContainsString('tarixi tarixi', $message);
        $this->assertStringContainsString('Başlama tarixi', $message);
    }

    public function test_a_deleted_leave_leaves_the_list_on_the_next_render(): void
    {
        $this->actingAs($this->user(['show-leaves', 'delete-leaves']));

        $leave = Leave::query()->create([
            'tabel_no' => $this->employee->tabel_no,
            'leave_type_id' => $this->type->id,
            'starts_at' => '2026-12-01',
            'ends_at' => '2026-12-01',
            'duration_unit' => 'day',
            'reason' => 'silinəcək icazə',
            'status_id' => OrderStatusEnum::PENDING->value,
        ]);

        $list = Livewire::test(Leaves::class)->assertSee('silinəcək icazə');

        Livewire::test(DeleteLeave::class)->call('setDeleteLeave', $leave->id)->call('deleteLeave');

        $list->dispatch('leaveWasDeleted', 'ok')->assertDontSee('silinəcək icazə');
    }

    private function fillForm($component, string $start = '2026-10-12', string $end = '2026-10-13')
    {
        return $component
            ->call('selectPersonnel', $this->employee->tabel_no, $this->employee->fullname, 'tabel_no')
            ->set('leave.leave_type_id', $this->type->id)
            ->set('leave.duration_unit', 'day')
            ->set('leave.starts_at', $start)
            ->set('leave.ends_at', $end)
            ->set('leave.reason', 'Ailə vəziyyəti');
    }

    /**
     * @param  list<string>  $permissions
     */
    private function user(array $permissions): User
    {
        $user = User::factory()->create();
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return grantAllStructures($user);
    }

    private function makePersonnel(string $tabelNo, string $surname): Personnel
    {
        return Personnel::withoutEvents(fn () => Personnel::factory()->create([
            'tabel_no' => $tabelNo,
            'surname' => $surname,
            'name' => 'Test',
            'patronymic' => 'Oglu',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'email' => strtolower($tabelNo).'@example.test',
            'mobile' => '0500000000',
            'nationality_id' => 1,
            'pin' => 'PIN'.$tabelNo,
            'residental_address' => 'Baku',
            'education_degree_id' => 1,
            'structure_id' => 1,
            'position_id' => 1,
            'work_norm_id' => 1,
            'join_work_date' => '2020-01-05',
            'added_by' => 1,
        ]));
    }
}
