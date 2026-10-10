<?php

namespace Tests\Feature\Security;

use App\Models\PerformanceBonusCalculation;
use App\Models\PerformanceScorecard;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Modules\PerformanceEvaluation\Livewire\Kpi\ScorecardsWorkspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Feature\PerformanceEvaluation\Concerns\BuildsKpiCards;
use Tests\TestCase;

/**
 * KPI kartının bonus bloku maaş bazasını və bonus məbləğini yalnız view-compensation-amounts
 * icazəsi ilə göstərir; HR kartları yalnız öz strukturları üzrə görür.
 */
class ScopeKpiSalaryMaskTest extends TestCase
{
    use BuildsKpiCards;
    use RefreshDatabase;

    private Position $position;

    private User $hr;

    protected function setUp(): void
    {
        parent::setUp();

        $this->position = Position::query()->create(['name' => 'Satış meneceri']);
        $this->hr = $this->hrUser(all: true);
        $this->actingAs($this->hr);
    }

    public function test_bonus_salary_is_masked_without_the_amounts_permission(): void
    {
        $card = $this->cardWithBonus();

        $this->get(route('performance-evaluation.scorecard-print', $card->id))
            ->assertOk()
            ->assertDontSee('4 321.5')
            ->assertDontSee('987.25')
            ->assertSee('•••');

        Livewire::test(ScorecardsWorkspace::class)
            ->set('cycleId', $card->performance_cycle_id)
            ->call('openCard', $card->id)
            ->assertDontSee('4 321.5')
            ->assertDontSee('987.25');

        $this->hr->givePermissionTo(Permission::findOrCreate('view-compensation-amounts', 'web'));

        $this->get(route('performance-evaluation.scorecard-print', $card->id))
            ->assertOk()
            ->assertSee('4 321.5')
            ->assertSee('987.25');

        Livewire::test(ScorecardsWorkspace::class)
            ->set('cycleId', $card->performance_cycle_id)
            ->call('openCard', $card->id)
            ->assertSee('4 321.5');
    }

    public function test_hr_sees_only_cards_of_their_structures(): void
    {
        $card = $this->cardWithBonus();
        $other = Structure::query()->create(['name' => 'Başqa şöbə', 'shortname' => 'BŞ']);

        $limited = $this->hrUser(all: false);
        grantStructures($limited, [$other->id]);

        $this->actingAs($limited)
            ->get(route('performance-evaluation.scorecard-print', $card->id))
            ->assertNotFound();

        $cards = Livewire::actingAs($limited)
            ->test(ScorecardsWorkspace::class)
            ->set('cycleId', $card->performance_cycle_id)
            ->instance()
            ->cards();

        $this->assertSame([], $cards->pluck('id')->all());

        grantStructures($limited, [(int) $card->personnel->structure_id]);

        $this->actingAs($limited)
            ->get(route('performance-evaluation.scorecard-print', $card->id))
            ->assertOk();
    }

    private function hrUser(bool $all): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('manage-performance-evaluation', 'web'));
        $user->givePermissionTo(Permission::findOrCreate('show-performance-evaluation', 'web'));

        return $all ? grantAllStructures($user) : $user;
    }

    private function cardWithBonus(): PerformanceScorecard
    {
        $card = $this->approvedSpecCard();

        PerformanceBonusCalculation::query()->create([
            'performance_scorecard_id' => $card->id,
            'performance_cycle_id' => $card->performance_cycle_id,
            'personnel_id' => $card->personnel_id,
            'mode' => 'formula',
            'score' => 81.5,
            'base_salary' => 4321.5,
            'period_months' => 3,
            'target_pct' => 10,
            'payout_pct' => 100,
            'company_mult' => 1,
            'unit_mult' => 1,
            'prorata' => 1,
            'scale_factor' => 1,
            'amount' => 987.25,
            'currency' => 'AZN',
            'status' => 'calculated',
        ]);

        return $card->refresh();
    }
}
