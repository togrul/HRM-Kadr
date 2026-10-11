<?php

namespace Tests\Feature\Personnel;

use App\Models\Structure;
use App\Models\User;
use App\Modules\Personnel\Application\Services\HrCommandCenterReadService;
use App\Modules\Personnel\Livewire\Home;
use App\Services\StructureService;
use App\Support\Database\InstalledTables;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * HR Command Center: ana səhifədə icazə + struktur görünürlüyü ilə süzülən, sayca məhdud
 * «diqqət tələb edənlər» panelləri (lazy island).
 */
class HrCommandCenterTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSIONS = [
        'show-employee-lifecycle',
        'show-candidates',
        'show-attendance-exceptions',
        'show-document-compliance',
    ];

    private int $insideStructure;

    private int $outsideStructure;

    protected function setUp(): void
    {
        parent::setUp();

        $this->insideStructure = Structure::query()->create(['name' => 'Daxili idarə', 'shortname' => 'DI'])->id;
        $this->outsideStructure = Structure::query()->create(['name' => 'Kənar idarə', 'shortname' => 'KI'])->id;
    }

    public function test_panels_count_and_list_attention_items_for_an_all_structures_viewer(): void
    {
        $viewer = $this->viewer(self::PERMISSIONS, all: true);
        $this->seedAttention($this->insideStructure, 'Daxili', 1);
        $this->seedAttention($this->outsideStructure, 'Kənar', 2);

        $panels = collect(app(HrCommandCenterReadService::class)->panels($viewer))->keyBy('key');

        $this->assertSame(
            ['lifecycle_overdue', 'probation_reviews', 'open_vacancies', 'requisition_aging', 'attendance_exceptions', 'expiring_documents'],
            $panels->keys()->all(),
        );
        foreach ($panels as $key => $panel) {
            $this->assertSame(2, $panel['count'], $key);
            $this->assertCount(2, $panel['rows'], $key);
        }
        $this->assertSame(2, $panels['requisition_aging']['stale']);
        $this->assertSame('rose', $panels['lifecycle_overdue']['rows'][0]['tone']);
    }

    public function test_every_panel_is_limited_to_the_viewers_structures(): void
    {
        $viewer = $this->viewer(self::PERMISSIONS, structures: [$this->insideStructure]);
        $this->seedAttention($this->insideStructure, 'Daxili', 1);
        $this->seedAttention($this->outsideStructure, 'Kənar', 2);

        $panels = app(HrCommandCenterReadService::class)->panels($viewer);

        $this->assertCount(6, $panels);
        foreach ($panels as $panel) {
            $this->assertSame(1, $panel['count'], $panel['key']);
            $text = json_encode($panel['rows'], JSON_UNESCAPED_UNICODE);
            $this->assertStringNotContainsString('Kənar', (string) $text, $panel['key']);
        }
    }

    public function test_a_viewer_without_structures_sees_nothing(): void
    {
        $viewer = $this->viewer(self::PERMISSIONS, structures: []);
        $this->seedAttention($this->insideStructure, 'Daxili', 1);

        foreach (app(HrCommandCenterReadService::class)->panels($viewer) as $panel) {
            $this->assertSame(0, $panel['count'], $panel['key']);
        }
    }

    public function test_panels_are_gated_by_their_own_permission(): void
    {
        $viewer = $this->viewer(['show-candidates'], all: true);
        $this->seedAttention($this->insideStructure, 'Daxili', 1);

        $this->assertSame(
            ['open_vacancies', 'requisition_aging'],
            array_column(app(HrCommandCenterReadService::class)->panels($viewer), 'key'),
        );

        $this->assertFalse(app(HrCommandCenterReadService::class)->visibleTo($this->viewer([], all: true)));
    }

    public function test_reads_stay_bounded_however_many_rows_exist(): void
    {
        $viewer = $this->viewer(self::PERMISSIONS, all: true);
        foreach (range(1, 8) as $index) {
            $this->seedAttention($this->insideStructure, 'Daxili'.$index, $index);
        }
        $viewer->getAllPermissions();
        InstalledTables::has('personnels');
        InstalledTables::hasColumn('job_requisitions', 'approval_status');
        app(StructureService::class)->scopeFor($viewer);

        $queries = $this->queriesDuring(fn () => app(HrCommandCenterReadService::class)->panels($viewer));
        $panels = app(HrCommandCenterReadService::class)->panels($viewer);

        // Panel başına iki sorğu (say + LIMIT-li sətirlər); tələbnamə sayı və köhnəlmiş sayı bir aqreqatdadır.
        $this->assertLessThanOrEqual(12, count($queries), implode("\n", $queries));
        foreach ($panels as $panel) {
            $this->assertSame(8, $panel['count'], $panel['key']);
            $this->assertCount((int) config('strategic_hr.command_center.rows'), $panel['rows'], $panel['key']);
        }
    }

    public function test_the_command_center_is_a_lazy_island_on_home(): void
    {
        $this->actingAs($this->viewer(self::PERMISSIONS, structures: [$this->insideStructure]));
        $this->seedAttention($this->insideStructure, 'Daxili', 1);
        $this->seedAttention($this->outsideStructure, 'Kənar', 2);

        $component = Livewire::test(Home::class)
            ->assertSeeHtml('name=home-command-center')
            ->assertDontSee(__('personnel::home.command.title'));

        $html = $this->loadIsland($component, 'home-command-center');

        $this->assertStringContainsString(__('personnel::home.command.title'), $html);
        $this->assertStringContainsString(__('personnel::home.command.panels.requisition_aging.label'), $html);
        $this->assertStringContainsString('Daxili', $html);
        $this->assertStringNotContainsString('Kənar', $html);
    }

    public function test_the_island_is_not_registered_without_any_panel_permission(): void
    {
        $this->actingAs($this->viewer(['show-orders'], all: true));

        $component = Livewire::test(Home::class)->assertDontSeeHtml('name=home-command-center');

        $this->assertSame('', $this->loadIsland($component, 'home-command-center'));
    }

    private function seedAttention(int $structureId, string $surname, int $index): void
    {
        $tabelNo = 'CC-'.$structureId.'-'.$index;
        $personnelId = DB::table('personnels')->insertGetId([
            'tabel_no' => $tabelNo,
            'surname' => $surname,
            'name' => 'Əli',
            'patronymic' => 'Vəli',
            'birthdate' => '1990-01-01',
            'mobile' => '0500000001',
            'nationality_id' => 1,
            'pin' => 'PIN'.$structureId.$index,
            'residental_address' => 'Bakı',
            'education_degree_id' => 1,
            'structure_id' => $structureId,
            'position_id' => 1,
            'join_work_date' => '2020-01-01',
            'added_by' => 1,
            'work_norm_id' => 1,
        ]);

        $eventId = DB::table('employee_lifecycle_events')->insertGetId([
            'personnel_id' => $personnelId, 'tabel_no' => $tabelNo, 'type' => 'onboarding', 'status' => 'in_progress',
            'title' => 'Uyğunlaşma', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('employee_lifecycle_tasks')->insert([
            ['event_id' => $eventId, 'title' => 'Gecikmiş '.$surname, 'due_at' => today()->subDays(3)->toDateString(), 'status' => 'open', 'created_at' => now(), 'updated_at' => now()],
            ['event_id' => $eventId, 'title' => 'Vaxtında', 'due_at' => today()->addDays(3)->toDateString(), 'status' => 'open', 'created_at' => now(), 'updated_at' => now()],
            ['event_id' => $eventId, 'title' => 'Bitmiş', 'due_at' => today()->subDays(3)->toDateString(), 'status' => 'completed', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $probationEvent = DB::table('employee_lifecycle_events')->insertGetId([
            'personnel_id' => $personnelId, 'tabel_no' => $tabelNo, 'type' => 'probation', 'status' => 'planned',
            'title' => 'Sınaq', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('employee_lifecycle_probation_reviews')->insert([
            ['event_id' => $probationEvent, 'personnel_id' => $personnelId, 'tabel_no' => $tabelNo, 'review_due_at' => today()->addDays(5)->toDateString(), 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()],
            ['event_id' => $probationEvent, 'personnel_id' => $personnelId, 'tabel_no' => $tabelNo, 'review_due_at' => today()->addDays(60)->toDateString(), 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('job_openings')->insert([
            ['title' => 'Vakansiya '.$surname, 'structure_id' => $structureId, 'status' => 'open', 'headcount' => 2, 'published_at' => now()->subDays(40), 'created_at' => now()->subDays(41), 'updated_at' => now()],
            ['title' => 'Bağlı '.$surname, 'structure_id' => $structureId, 'status' => 'closed', 'headcount' => 1, 'published_at' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('job_requisitions')->insert([
            ['title' => 'Tələbnamə '.$surname, 'structure_id' => $structureId, 'status' => 'draft', 'approval_status' => 'pending', 'headcount' => 1, 'created_at' => now()->subDays(20), 'updated_at' => now()],
            ['title' => 'Təsdiqlənmiş '.$surname, 'structure_id' => $structureId, 'status' => 'approved', 'approval_status' => 'approved', 'headcount' => 1, 'created_at' => now()->subDays(20), 'updated_at' => now()],
        ]);

        DB::table('attendance_exceptions')->insert([
            ['tabel_no' => $tabelNo, 'date' => today()->subDays(2)->toDateString(), 'type' => 'missing_in', 'status' => 'open', 'created_at' => now(), 'updated_at' => now()],
            ['tabel_no' => $tabelNo, 'date' => today()->subDays(2)->toDateString(), 'type' => 'missing_out', 'status' => 'resolved', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('personnel_cards')->insert([
            ['tabel_no' => $tabelNo, 'card_number' => 'C-'.$tabelNo, 'valid_date' => today()->addDays(10)->toDateString()],
            ['tabel_no' => $tabelNo, 'card_number' => 'C2-'.$tabelNo, 'valid_date' => today()->addYear()->toDateString()],
        ]);
    }

    /**
     * @param  list<string>  $permissions
     * @param  list<int>  $structures
     */
    private function viewer(array $permissions, bool $all = false, array $structures = []): User
    {
        $user = User::factory()->create();
        $all ? grantAllStructures($user) : grantStructures($user, $structures);

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $user->givePermissionTo($permissions);

        return $user->fresh();
    }

    private function loadIsland(Testable $component, string $island): string
    {
        $component->update(calls: [[
            'method' => '__lazyLoadIsland',
            'params' => [],
            'path' => '',
            'metadata' => ['island' => ['name' => $island, 'mode' => 'morph']],
        ]]);

        return implode('', $component->effects['islandFragments'] ?? []);
    }

    /**
     * @return list<string>
     */
    private function queriesDuring(callable $callback): array
    {
        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $callback();

        app('events')->forget(QueryExecuted::class);

        return $queries;
    }
}
