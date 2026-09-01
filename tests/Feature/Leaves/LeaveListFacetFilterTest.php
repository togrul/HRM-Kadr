<?php

namespace Tests\Feature\Leaves;

use App\Enums\OrderStatusEnum;
use App\Models\Leave;
use App\Models\OrderStatus;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Modules\Leaves\Livewire\Leaves;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LeaveListFacetFilterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The gender chips sit next to the instant status chips, so they must apply on click.
     * $set would only touch $filter, while the list is drawn from $search.
     */
    public function test_gender_chip_filters_the_list_without_pressing_search(): void
    {
        $this->seedReferenceData();
        $this->actingAs($this->permittedUser());

        $man = $this->makePersonnel('TB-MAN', 1);
        $woman = $this->makePersonnel('TB-WOMAN', 2);

        $this->makeLeave($man->tabel_no, 'kişi icazəsi');
        $this->makeLeave($woman->tabel_no, 'qadın icazəsi');

        Livewire::test(Leaves::class)
            ->assertSee('kişi icazəsi')
            ->assertSee('qadın icazəsi')
            ->call('applyFilter', ['gender' => '2'])
            ->assertDontSee('kişi icazəsi')
            ->assertSee('qadın icazəsi')
            ->call('applyFilter', ['gender' => null])
            ->assertSee('kişi icazəsi');
    }

    public function test_status_counts_ignore_the_status_bucket_so_the_facet_stays_navigable(): void
    {
        $this->seedReferenceData();
        $this->actingAs($this->permittedUser());

        $personnel = $this->makePersonnel('TB-COUNT', 1);
        $this->makeLeave($personnel->tabel_no, 'birinci', OrderStatusEnum::APPROVED->value);
        $this->makeLeave($personnel->tabel_no, 'ikinci', OrderStatusEnum::APPROVED->value);
        $this->makeLeave($personnel->tabel_no, 'ucuncu', 10);

        $component = Livewire::test(Leaves::class)->call('setStatus', 10);

        $counts = $component->instance()->statusCounts();

        $this->assertSame(3, $counts['all']);
        $this->assertSame(2, $counts['by_status'][OrderStatusEnum::APPROVED->value]);
        $this->assertSame(1, $counts['by_status'][10]);
    }

    private function permittedUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('show-leaves', 'web'));

        return $user;
    }

    private function makeLeave(string $tabelNo, string $reason, int $statusId = OrderStatusEnum::APPROVED->value): Leave
    {
        return Leave::withoutEvents(fn () => Leave::query()->create([
            'tabel_no' => $tabelNo,
            'leave_type_id' => null,
            'starts_at' => '2026-03-01',
            'ends_at' => '2026-03-03',
            'duration_unit' => 'day',
            'total_days' => 3,
            'reason' => $reason,
            'status_id' => $statusId,
        ]));
    }

    private function makePersonnel(string $tabelNo, int $gender): Personnel
    {
        return Personnel::withoutEvents(fn () => Personnel::factory()->create([
            'tabel_no' => $tabelNo,
            'surname' => 'Test',
            'name' => 'Person',
            'patronymic' => 'Oglu',
            'birthdate' => '1990-01-01',
            'gender' => $gender,
            'email' => strtolower($tabelNo).'@example.test',
            'mobile' => '0500000000',
            'nationality_id' => 1,
            'pin' => 'PIN'.$tabelNo,
            'residental_address' => 'Baku',
            'education_degree_id' => 1,
            'structure_id' => 1,
            'position_id' => 1,
            'work_norm_id' => 1,
            'join_work_date' => '2026-01-05',
            'added_by' => 1,
        ]));
    }

    private function seedReferenceData(): void
    {
        foreach ([[10, 'Təsdiq gözləyən'], [OrderStatusEnum::APPROVED->value, 'Təsdiqlənmiş']] as [$id, $name]) {
            OrderStatus::query()->firstOrCreate(['id' => $id], ['locale' => 'az', 'name' => $name]);
        }

        DB::table('countries')->insertOrIgnore(['id' => 1, 'code' => 'AZ']);
        DB::table('education_degrees')->insertOrIgnore(['id' => 1, 'title_az' => 'Bakalavr']);
        DB::table('work_norms')->insertOrIgnore(['id' => 1, 'name_az' => 'Tam ştat']);
        Structure::query()->firstOrCreate(['id' => 1], ['name' => 'İR', 'shortname' => 'IR', 'code' => 1, 'level' => 1]);
        Position::query()->firstOrCreate(['id' => 1], ['name' => 'Məsləhətçi']);
    }
}
