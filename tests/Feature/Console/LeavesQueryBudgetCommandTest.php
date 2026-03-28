<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LeavesQueryBudgetCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_query_budget_metrics_for_leaves_flows(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('show-leaves', 'web');
        Permission::findOrCreate('add-leaves', 'web');
        Permission::findOrCreate('edit-leaves', 'web');
        $user->givePermissionTo('show-leaves');
        $user->givePermissionTo('add-leaves');
        $user->givePermissionTo('edit-leaves');

        DB::table('leave_types')->insert([
            'id' => 1,
            'name' => 'Məzuniyyət',
            'max_days' => 0,
            'requires_document' => false,
        ]);

        DB::table('structures')->insert([
            ['id' => 1, 'name' => 'Baş idarə', 'shortname' => 'BG', 'parent_id' => null],
            ['id' => 18, 'name' => 'Texniki vasitələr və rabitə idarəsi', 'shortname' => 'PTX', 'parent_id' => 1],
        ]);

        DB::table('positions')->insert([
            ['id' => 1000, 'name' => 'Proqramçı', 'approval_rank' => 0, 'is_approval_target' => 1],
        ]);

        DB::table('countries')->insert(['id' => 1, 'code' => 'AZ']);
        DB::table('education_degrees')->insert([
            'id' => 1,
            'title_az' => 'Bakalavr',
            'title_en' => 'Bachelor',
            'title_ru' => 'Бакалавр',
        ]);
        DB::table('work_norms')->insert([
            'id' => 1,
            'name_az' => 'Tam iş günü',
            'name_en' => 'Full time',
            'name_ru' => 'Полный день',
        ]);

        DB::table('order_statuses')->insert([
            'id' => 1,
            'name' => 'Gözləyir',
        ]);

        DB::table('personnels')->insert([
            'id' => 1,
            'tabel_no' => 'TCI-0001',
            'surname' => 'Calalli',
            'name' => 'Togrul',
            'patronymic' => 'Test',
            'has_changed_initials' => false,
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'mobile' => '0501234567',
            'nationality_id' => 1,
            'has_changed_nationality' => false,
            'pin' => 'PIN1',
            'residental_address' => 'Baku',
            'education_degree_id' => 1,
            'structure_id' => 18,
            'position_id' => 1000,
            'work_norm_id' => 1,
            'join_work_date' => '2020-01-01',
            'added_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
            'is_pending' => false,
        ]);

        DB::table('leaves')->insert([
            'id' => 1,
            'tabel_no' => 'TCI-0001',
            'leave_type_id' => 1,
            'starts_at' => '2026-03-01',
            'ends_at' => '2026-03-01',
            'duration_unit' => 'day',
            'total_days' => 1,
            'status_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $exitCode = Artisan::call('leaves:query-budget', ['--json' => true]);

        $payload = json_decode(Artisan::output(), true);
        $results = collect(data_get($payload, 'results', []))->keyBy('flow');

        $this->assertSame(0, $exitCode, json_encode($payload, JSON_UNESCAPED_UNICODE));
        $this->assertSame(0, data_get($payload, 'summary.failed_probes'));
        $this->assertSame('ok', data_get($results, 'leaves_render.status'));
        $this->assertSame('ok', data_get($results, 'leaves_status_update.status'));
        $this->assertSame('ok', data_get($results, 'leaves_add_modal_open.status'));
        $this->assertSame('ok', data_get($results, 'leaves_edit_manual_toggle.status'));
    }
}
