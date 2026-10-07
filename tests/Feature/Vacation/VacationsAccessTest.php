<?php

namespace Tests\Feature\Vacation;

use App\Models\Personnel;
use App\Models\PersonnelVacation;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Modules\Vacation\Livewire\Vacations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class VacationsAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_is_forbidden_without_show_permission(): void
    {
        // The route group is only web+auth; the component itself is the access gate.
        $this->actingAs(User::factory()->create());

        Livewire::test(Vacations::class)->assertForbidden();
    }

    public function test_listing_is_allowed_with_show_permission(): void
    {
        $this->actingAs($this->userWith('show-vacations'));

        Livewire::test(Vacations::class)->assertOk();
    }

    public function test_search_filter_applies_the_current_filter(): void
    {
        $this->actingAs($this->userWith('show-vacations'));

        Livewire::test(Vacations::class)
            ->set('filter.structure_id', 7)
            ->call('searchFilter')
            ->assertSet('search.structure_id', 7);
    }

    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $user;
    }

    public function test_status_type_and_year_filters_are_selects_bound_to_the_component(): void
    {
        $this->actingAs($this->userWith('show-vacations'));

        // The selects must live in the component's own output (not the layout's sidebar
        // slot), or their wire:model bindings are unreachable from the UI.
        Livewire::test(Vacations::class)
            ->assertSeeHtml('vacations-status-filter')
            ->assertSeeHtml('vacations-year-filter')
            ->assertSee(__('vacation::common.labels.in_vacation'))
            ->set('filter.vacation_status', 'in_vacation')
            ->assertSet('search.vacation_status', 'in_vacation')
            ->set('selectedType', 'tpl:annual')
            ->assertSet('selectedType', 'tpl:annual')
            ->set('selectedType', '')
            ->assertSet('selectedType', null);
    }

    public function test_summary_counts_the_scoped_vacations_and_their_days(): void
    {
        $user = $this->userWith('show-vacations');
        $user->assignRole($role = \Spatie\Permission\Models\Role::findOrCreate('structure-scope', 'web'));
        DB::table('role_structures')->insert(['role_id' => $role->id, 'structure_id' => 1]);
        $this->actingAs($user);

        DB::table('countries')->insert(['id' => 1, 'code' => 'AZ']);
        DB::table('education_degrees')->insert(['id' => 1, 'title_az' => 'Bakalavr']);
        DB::table('work_norms')->insert(['id' => 1, 'name_az' => 'Tam ştat']);
        Structure::query()->create(['id' => 1, 'name' => 'İR', 'shortname' => 'IR', 'code' => 1, 'level' => 1]);
        Position::query()->create(['id' => 1, 'name' => 'Məsləhətçi']);

        $personnel = Personnel::withoutEvents(fn () => Personnel::factory()->create([
            'tabel_no' => 'VAC001',
            'surname' => 'Sayim',
            'name' => 'Test',
            'patronymic' => 'Oglu',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'email' => 'vac001@example.test',
            'mobile' => '0500000001',
            'nationality_id' => 1,
            'pin' => 'PINVAC1',
            'residental_address' => 'Baku',
            'education_degree_id' => 1,
            'structure_id' => 1,
            'position_id' => 1,
            'work_norm_id' => 1,
            'join_work_date' => '2026-01-05',
            'added_by' => $user->id,
        ]));

        // One already back at work, one still to come, one running now. Only the running
        // one is "Məzuniyyətdə" — the same rule as the row chip; upcoming counts as "İşdə".
        foreach ([[5, -30], [3, 30], [4, -1]] as [$duration, $offsetDays]) {
            $start = now()->addDays($offsetDays);

            PersonnelVacation::query()->create([
                'tabel_no' => $personnel->tabel_no,
                'vacation_places' => 'Quba',
                'duration' => $duration,
                'start_date' => $start->toDateString(),
                'end_date' => $start->copy()->addDays($duration - 1)->toDateString(),
                'return_work_date' => $start->copy()->addDays($duration)->toDateString(),
                'order_given_by' => 'HR',
                'vacation_days_total' => 30,
                'remaining_days' => 30 - $duration,
                'added_by' => $user->id,
            ]);
        }

        $component = Livewire::test(Vacations::class);
        $summary = $component->instance()->summary();

        $this->assertSame(3, $summary['all']);
        $this->assertSame(12, $summary['days']);
        $this->assertSame(2, $summary['at_work']);
        $this->assertSame(1, $summary['in_vacation']);

        // The status bucket narrows the list the same way it counts.
        $component->call('setStatus', 'in_vacation');
        $this->assertSame([4], $component->instance()->vacations()->pluck('duration')->all());
        $component->call('setStatus', 'at_work');
        $this->assertEqualsCanonicalizing([5, 3], $component->instance()->vacations()->pluck('duration')->all());
    }

    public function test_export_needs_export_permission_and_follows_the_status_bucket(): void
    {
        $this->actingAs($this->userWith('show-vacations'));
        Livewire::test(Vacations::class)->call('exportExcel')->assertForbidden();

        \Maatwebsite\Excel\Facades\Excel::fake();
        $this->actingAs($this->userWith('show-vacations', 'export-vacations'));
        Livewire::test(Vacations::class)->call('setStatus', 'in_vacation')->call('exportExcel');

        \Maatwebsite\Excel\Facades\Excel::matchByRegex();
        \Maatwebsite\Excel\Facades\Excel::assertDownloaded('/vacation-.*\\.xlsx/');
    }

    public function test_bind_order_button_is_hidden_without_permission(): void
    {
        $this->actingAs($this->userWith('show-vacations'));

        $this->assertFalse(Livewire::test(Vacations::class)->instance()->canBindOrder());
    }
}
