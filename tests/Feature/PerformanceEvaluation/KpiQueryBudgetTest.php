<?php

namespace Tests\Feature\PerformanceEvaluation;

use App\Models\Position;
use App\Models\User;
use App\Modules\PerformanceEvaluation\Livewire\Kpi\AnalyticsWorkspace;
use App\Modules\PerformanceEvaluation\Livewire\Kpi\BonusWorkspace;
use App\Modules\PerformanceEvaluation\Livewire\Kpi\ScorecardsWorkspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Feature\PerformanceEvaluation\Concerns\BuildsKpiCards;
use Tests\TestCase;

/**
 * The KPI cards, analytics and bonus screens read a whole cycle; they must do it in a fixed,
 * small number of queries and never run the same query twice.
 */
class KpiQueryBudgetTest extends TestCase
{
    use BuildsKpiCards;
    use RefreshDatabase;

    private Position $position;

    private User $hr;

    protected function setUp(): void
    {
        parent::setUp();

        $this->position = Position::query()->create(['name' => 'Satış meneceri']);
        $this->hr = User::factory()->create();
        $this->hr->givePermissionTo(Permission::findOrCreate('manage-performance-evaluation', 'web'));
        $this->hr->givePermissionTo(Permission::findOrCreate('show-performance-evaluation', 'web'));
        $this->actingAs($this->hr);
    }

    public function test_kpi_screens_stay_within_budget_without_duplicates(): void
    {
        $card = $this->approvedSpecCard();

        foreach ([
            'analytics' => [AnalyticsWorkspace::class, fn ($screen) => $screen->set('structureId', $card->personnel->structure_id), 11],
            'scorecards' => [ScorecardsWorkspace::class, fn ($screen) => $screen->call('openCard', $card->id), 16],
            'bonus' => [BonusWorkspace::class, fn ($screen) => $screen->set('ruleForm.target_pct', 20), 9],
        ] as $name => [$component, $update, $budget]) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $screen = Livewire::test($component);
            $firstLoad = collect(DB::getQueryLog())->map(fn (array $query): string => $query['query'].' | '.json_encode($query['bindings']));
            $this->assertSame([], $firstLoad->countBy()->filter(fn (int $count): bool => $count > 1)->all(), "{$name}: duplicated queries on first load");

            DB::flushQueryLog();
            $update($screen);
            $queries = collect(DB::getQueryLog())->map(fn (array $query): string => $query['query'].' | '.json_encode($query['bindings']));
            DB::disableQueryLog();

            $this->assertSame([], $queries->countBy()->filter(fn (int $count): bool => $count > 1)->all(), "{$name}: duplicated queries");
            $this->assertLessThanOrEqual($budget, $queries->count(), "{$name}: over the query budget:\n".$queries->implode("\n"));
        }
    }
}
