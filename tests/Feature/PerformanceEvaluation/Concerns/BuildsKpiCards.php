<?php

namespace Tests\Feature\PerformanceEvaluation\Concerns;

use App\Models\PerformanceCycle;
use App\Models\PerformanceKpi;
use App\Models\PerformanceScorecard;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Models\UserPersonnelLink;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\BonusService;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\KpiLibraryService;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\KpiTemplateService;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\ScorecardService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Builds the spec §6.4 example card and the people around it. Expects the test to set
 * `$this->position` and `$this->hr`.
 */
trait BuildsKpiCards
{
    protected function approvedSpecCard(): PerformanceScorecard
    {
        $card = $this->specExampleCard();
        $scorecards = app(ScorecardService::class);
        $scorecards->transition($card, 'activate', $this->hr);
        foreach ([110000, 18, 71, 100] as $index => $value) {
            $scorecards->recordActual($card->items[$index], $value, $this->hr);
        }
        foreach (['start_self_review', 'submit_self_review', 'submit_manager_review', 'approve'] as $action) {
            $scorecards->transition($card->refresh(), $action, $this->hr);
        }

        return $card->refresh()->load('personnel', 'cycle');
    }

    /**
     * @return array<string, mixed>
     */
    protected function ruleData(BonusService $bonus, PerformanceCycle $cycle): array
    {
        return $bonus->rule($cycle)->only(['target_pct', 'reward_months', 'payout_bands', 'company_result', 'company_gate', 'gate_floor_pct', 'company_multipliers', 'cap_pct', 'fund', 'scale_to_fund']);
    }

    protected function employeeAndManager(PerformanceScorecard $card): array
    {
        $employee = $this->userFor($card->personnel);
        $manager = $this->userFor($this->person('Rəhbər'));
        $card->update(['manager_personnel_id' => UserPersonnelLink::query()->where('user_id', $manager->id)->value('personnel_id')]);
        $card->refresh();

        return [$employee, $manager];
    }

    protected function specExampleCard(?int $competencyFormTemplateId = null): PerformanceScorecard
    {
        $rows = [['SALES', 40, 100000], ['CLIENTS', 25, 20], ['DEBT', 20, 95], ['CRM', 15, 100]];
        $items = array_map(fn (array $row): array => $this->item($this->kpi($row[0]), $row[1], target: $row[2]), $rows);

        $data = $competencyFormTemplateId === null
            ? $this->templateData()
            : [...$this->templateData(70, 30), 'performance_form_template_id' => $competencyFormTemplateId];
        app(KpiTemplateService::class)->save($data, $items, [$this->position->id]);

        $person = $this->person('Əliyev');
        $cycle = PerformanceCycle::query()->create([
            'name' => '2026 Q1', 'cycle_type' => 'quarterly', 'period_start' => '2026-01-01', 'period_end' => '2026-03-31', 'status' => 'active',
        ]);

        $this->assertSame(1, app(ScorecardService::class)->generateForCycle($cycle));
        $this->assertSame(0, app(ScorecardService::class)->generateForCycle($cycle));

        return PerformanceScorecard::query()->where('personnel_id', $person->id)->with('items.kpi')->firstOrFail();
    }

    protected function kpi(string $code): PerformanceKpi
    {
        return app(KpiLibraryService::class)->save([
            'code' => $code, 'name' => $code, 'type' => 'quantitative', 'direction' => 'higher_better',
            'unit' => 'count', 'frequency' => 'quarterly', 'aggregation' => 'last', 'perspective' => 'financial', 'status' => 'active',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function templateData(float $kpiShare = 100, float $competencyShare = 0): array
    {
        return ['name' => 'Satış', 'period_type' => 'quarterly', 'kpi_weight_share' => $kpiShare, 'competency_weight_share' => $competencyShare, 'status' => 'active'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function item(PerformanceKpi $kpi, float $weight, float $target = 100, float $threshold = 80): array
    {
        return ['performance_kpi_id' => $kpi->id, 'weight' => $weight, 'target' => $target, 'threshold' => $threshold, 'cap' => 120, 'target_editable' => false];
    }

    protected function person(string $surname): Personnel
    {
        $structure = Structure::query()->create(['name' => 'Şöbə '.Str::random(4), 'shortname' => 'S'.Str::upper(Str::random(3))]);

        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'TB'.Str::upper(Str::random(6)),
            'surname' => $surname, 'name' => 'Ad', 'patronymic' => 'Ata',
            'birthdate' => '1985-01-01', 'gender' => 1,
            'email' => Str::lower(Str::random(8)).'@example.com', 'mobile' => '994500000000', 'nationality_id' => 1,
            'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
            'residental_address' => 'X', 'education_degree_id' => 1, 'work_norm_id' => 1,
            'structure_id' => $structure->id, 'position_id' => $this->position->id,
            'join_work_date' => '2015-01-01', 'added_by' => 1, 'is_pending' => false,
        ]));
    }

    protected function userFor(Personnel $personnel): User
    {
        $user = User::factory()->create();
        UserPersonnelLink::query()->create(['user_id' => $user->id, 'personnel_id' => $personnel->id]);

        return $user;
    }

    /**
     * @param  array<int, string>  $keys
     */
    protected function assertValidationKeys(array $keys, callable $callback): void
    {
        try {
            $callback();
        } catch (ValidationException $exception) {
            $this->assertSame($keys, array_keys($exception->errors()));

            return;
        }

        $this->fail('Expected a validation error on '.implode(', ', $keys));
    }
}
