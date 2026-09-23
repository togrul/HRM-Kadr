<?php

namespace App\Modules\PerformanceEvaluation\Application\Services\Kpi;

use App\Models\PerformanceBonusCalculation;
use App\Models\PerformanceBonusRule;
use App\Models\PerformanceCycle;
use App\Models\PerformanceScorecard;
use App\Models\Personnel;
use App\Models\Structure;
use App\Models\User;
use App\Modules\Compensation\Domain\Contracts\CompensationReadRepository;
use App\Modules\Integration\Domain\Contracts\IntegrationOutbox;
use App\Modules\Orders\Contracts\OrderDrafter;
use App\Modules\Payroll\Domain\Contracts\PayrollOneOffEarnings;
use App\Services\Profiles\ProfileState;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Bonus engine (spec §7), chosen by the service area:
 *  - `company` (civil profiles): base salary × months × target % (per position, else
 *    the rule's) × payout(score) × company multiplier × unit multiplier × pro-rata,
 *    capped, optionally scaled down to the fund, then handed to payroll (one-off
 *    earnings when payroll runs here, an integration event, and an Excel sheet).
 *  - `order` (military profile): payout(score) × monthly salary × reward months ×
 *    pro-rata, paid as a monetary award through a pending order (əmr) per person.
 *
 * Only approved or closed cards earn a bonus. Exported and ordered lines are final:
 * recalculating leaves them alone, so a second export never pays twice.
 */
class BonusService
{
    public const ORDER_TEMPLATE = 'pul_mukafati';

    /**
     * Manual fields of the award order template, keyed by their Azerbaijani label.
     * Data keys, not UI text: the OrderDrafter matches them against the template's
     * variable labels, so they must stay byte-identical to the Word template.
     */
    private const FIELD_REASON = 'Mükafatın səbəbi';

    private const FIELD_AMOUNT = 'Məbləğ';

    private const FIELD_BASIS = 'Əsas mətni';

    public function __construct(
        private readonly ProfileState $profile,
        private readonly OrderDrafter $orders,
    ) {}

    /** Military regime pays by order; every other service area uses the company model. */
    public function mode(): string
    {
        return $this->profile->active() === 'military' ? 'order' : 'company';
    }

    public function rule(PerformanceCycle $cycle): PerformanceBonusRule
    {
        $rule = PerformanceBonusRule::query()->firstOrNew(['performance_cycle_id' => $cycle->id], [
            'target_pct' => 15,
            'reward_months' => 1,
            'payout_bands' => PerformanceBonusRule::DEFAULT_PAYOUT_BANDS,
            'company_gate' => 85,
            'gate_floor_pct' => 0,
            'company_multipliers' => PerformanceBonusRule::DEFAULT_COMPANY_MULTIPLIERS,
            'unit_results' => [],
            'position_targets' => [],
            'cap_pct' => 150,
            'scale_to_fund' => false,
            'currency' => 'AZN',
        ]);
        $rule->mode = $this->mode();

        return $rule;
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function saveRule(PerformanceCycle $cycle, array $data, User $user): PerformanceBonusRule
    {
        $rule = $this->rule($cycle)->fill($this->validRule($data));
        $rule->updated_by = $user->id;
        $rule->save();

        return $rule;
    }

    /**
     * What the rule pays, without writing anything — also the what-if simulation when
     * given an unsaved rule.
     *
     * @return array{rows: Collection<int, array<string, mixed>>, total: float, fund: ?float, over_fund: bool, scale: float, missing_salary: int, mode: string, currency: string}
     */
    public function preview(PerformanceCycle $cycle, PerformanceBonusRule $rule): array
    {
        $cards = PerformanceScorecard::query()
            ->where('performance_cycle_id', $cycle->id)
            ->whereIn('status', ['approved', 'closed'])
            ->with(['personnel:id,surname,name,patronymic,tabel_no,structure_id,join_work_date,probation_unit,probation_amount', 'bonus'])
            ->orderBy('id')
            ->get();

        $months = $this->periodMonths($cycle);
        $bases = $this->baseSalaries($cards, $cycle);
        $companyMult = $rule->mode === 'order' ? 1.0 : $this->companyMultiplier($rule);
        $positionTargets = collect($rule->position_targets ?? [])->mapWithKeys(fn (array $pair): array => [(int) $pair[0] => (float) $pair[1]]);
        $unitResult = $this->unitResultResolver($rule);

        $cycleEnd = Carbon::parse($cycle->period_end)->endOfDay();

        $rows = $cards->map(function (PerformanceScorecard $card) use ($rule, $months, $bases, $companyMult, $positionTargets, $unitResult, $cycleEnd): array {
            if ($card->bonus?->isFinal()) {
                return $this->storedRow($card);
            }

            $score = $card->effectiveScore();
            $base = $bases->get((string) $card->personnel?->tabel_no);
            $targetPct = (float) $positionTargets->get((int) $card->position_id, $rule->target_pct);
            $unitMult = $rule->mode === 'order' ? 1.0 : $this->multiplierFor($unitResult($card->personnel?->structure_id), $rule);
            $target = $base === null ? 0.0 : ($rule->mode === 'order'
                ? $base * $rule->reward_months
                : $base * $months * $targetPct / 100);
            $payout = $this->payoutPct($score, $rule->payout_bands);
            $inProbation = ! $rule->pay_in_probation && $this->inProbationOn($card->personnel, $cycleEnd);
            $amount = $inProbation ? 0.0 : min($target * $payout / 100 * $companyMult * $unitMult * (float) $card->prorata_factor * (float) $card->fte, $target * $rule->cap_pct / 100);

            return [
                'scorecard_id' => $card->id,
                'personnel_id' => $card->personnel_id,
                'personnel' => $card->personnel,
                'score' => $score,
                'base_salary' => $base,
                'period_months' => $rule->mode === 'order' ? $rule->reward_months : $months,
                'target_pct' => $rule->mode === 'order' ? 100.0 : $targetPct,
                'payout_pct' => $payout,
                'company_mult' => $companyMult,
                'unit_mult' => $unitMult,
                'prorata' => (float) $card->prorata_factor,
                'scale_factor' => 1.0,
                'amount' => round($amount, 2),
                'status' => $card->bonus?->status,
                'order_log_id' => null,
                'probation' => $inProbation,
                'fte' => (float) $card->fte,
                'final' => false,
            ];
        });

        $open = $rows->where('final', false);
        $total = (float) $rows->sum('amount');
        $overFund = $rule->fund !== null && $total > $rule->fund + 0.005;
        $scale = 1.0;

        if ($overFund && $rule->scale_to_fund && $open->sum('amount') > 0) {
            $room = max(0, $rule->fund - $rows->where('final', true)->sum('amount'));
            $scale = min(1, $room / $open->sum('amount'));
            $rows = $rows->map(fn (array $row): array => $row['final'] ? $row : [
                ...$row,
                'scale_factor' => round($scale, 4),
                'amount' => round($row['amount'] * $scale, 2),
            ]);
            $total = (float) $rows->sum('amount');
        }

        return [
            'rows' => $rows->values(),
            'total' => round($total, 2),
            'fund' => $rule->fund,
            'over_fund' => $overFund,
            'scale' => $scale,
            'missing_salary' => $rows->where('final', false)->whereNull('base_salary')->count(),
            'mode' => $rule->mode,
            'currency' => $rule->currency ?: 'AZN',
        ];
    }

    /**
     * What one card would pay at a given score under the cycle's rule — the employee's
     * "expected bonus" (spec §9). Fund scale-down is left out: it is only known once
     * everyone is calculated.
     */
    public function estimate(PerformanceScorecard $card, ?float $score): ?float
    {
        $cycle = $card->cycle;
        $card->loadMissing('personnel:id,tabel_no,structure_id,join_work_date,probation_unit,probation_amount');
        $rule = $this->rule($cycle);
        $base = $this->baseSalaries(collect([$card]), $cycle)->get((string) $card->personnel?->tabel_no);

        if ($base === null || $score === null) {
            return null;
        }

        if (! $rule->pay_in_probation && $this->inProbationOn($card->personnel, Carbon::parse($cycle->period_end)->endOfDay())) {
            return 0.0;
        }

        $targetPct = (float) (collect($rule->position_targets ?? [])->first(fn (array $pair): bool => (int) $pair[0] === (int) $card->position_id)[1] ?? $rule->target_pct);
        $target = $rule->mode === 'order' ? $base * $rule->reward_months : $base * $this->periodMonths($cycle) * $targetPct / 100;
        $multipliers = $rule->mode === 'order' ? 1.0 : $this->companyMultiplier($rule) * $this->multiplierFor(($this->unitResultResolver($rule))($card->personnel?->structure_id), $rule);

        return round(min($target * $this->payoutPct($score, $rule->payout_bands) / 100 * $multipliers * (float) $card->prorata_factor * (float) $card->fte, $target * $rule->cap_pct / 100), 2);
    }

    /**
     * Stores the preview as the cycle's bonus lines (final lines stay untouched).
     */
    public function calculate(PerformanceCycle $cycle): int
    {
        $rule = $this->rule($cycle);
        $preview = $this->preview($cycle, $rule);

        DB::transaction(function () use ($cycle, $preview): void {
            $finalIds = PerformanceBonusCalculation::query()
                ->where('performance_cycle_id', $cycle->id)
                ->whereIn('status', ['exported', 'ordered'])
                ->lockForUpdate()
                ->pluck('performance_scorecard_id')
                ->all();

            foreach ($preview['rows']->where('final', false)->whereNotIn('scorecard_id', $finalIds) as $row) {
                PerformanceBonusCalculation::query()->updateOrCreate(
                    ['performance_scorecard_id' => $row['scorecard_id']],
                    [
                        'performance_cycle_id' => $cycle->id,
                        'personnel_id' => $row['personnel_id'],
                        'mode' => $preview['mode'],
                        'score' => $row['score'],
                        'base_salary' => $row['base_salary'],
                        'period_months' => $row['period_months'],
                        'target_pct' => $row['target_pct'],
                        'payout_pct' => $row['payout_pct'],
                        'company_mult' => $row['company_mult'],
                        'unit_mult' => $row['unit_mult'],
                        'prorata' => $row['prorata'],
                        'scale_factor' => $row['scale_factor'],
                        'amount' => $row['amount'],
                        'currency' => $preview['currency'],
                        'status' => 'calculated',
                    ],
                );
            }
        });

        if ($preview['over_fund']) {
            app(ScorecardNotifier::class)->fundExceeded($cycle, $preview['total'], (float) $preview['fund'], $preview['currency']);
        }

        return $preview['rows']->where('final', false)->count();
    }

    /**
     * Company model: marks calculated lines as exported and emits one integration event
     * per line. Returns every exported line of the cycle for the payroll sheet, so a
     * repeated export yields the same file and no new events.
     *
     * @return array<int, array<string, mixed>>
     */
    public function exportToPayroll(PerformanceCycle $cycle): array
    {
        $this->ensureMode('company');
        $batch = (string) Str::ulid();
        $payMonth = now()->format('Y-m');
        $outbox = app()->bound(IntegrationOutbox::class) ? app(IntegrationOutbox::class) : null;
        $payroll = app()->bound(PayrollOneOffEarnings::class) ? app(PayrollOneOffEarnings::class) : null;

        DB::transaction(function () use ($batch, $payMonth, $cycle, $outbox, $payroll): void {
            foreach ($this->payableLines($cycle, 'personnel:id,tabel_no') as $line) {
                $line->update(['status' => 'exported', 'exported_at' => now(), 'export_batch' => $batch]);
                $payroll?->record(
                    (string) $line->personnel?->tabel_no, 'kpi_bonus', __('performance_evaluation::kpi.bonus.payroll_line', ['cycle' => $cycle->name]),
                    $line->amount, (int) now()->year, (int) now()->month, 'performance_bonus:'.$line->id,
                );
                $outbox?->record('performance_bonus', 'performance_bonus:'.$line->id, [
                    'tabel_no' => $line->personnel?->tabel_no,
                    'cycle' => $cycle->name,
                    'amount' => $line->amount,
                    'currency' => $line->currency,
                    'pay_month' => $payMonth,
                ]);
            }
        });

        return PerformanceBonusCalculation::query()
            ->where('performance_cycle_id', $cycle->id)
            ->where('status', 'exported')
            ->with('personnel:id,surname,name,patronymic,tabel_no')
            ->orderBy('id')
            ->get()
            ->map(fn (PerformanceBonusCalculation $line): array => [
                'tabel_no' => $line->personnel?->tabel_no,
                'personnel' => $line->personnel?->fullname,
                'cycle' => $cycle->name,
                'amount' => $line->amount,
                'currency' => $line->currency,
                'pay_month' => $line->exported_at?->format('Y-m'),
            ])
            ->all();
    }

    /**
     * Military regime: drafts one pending monetary-award order per calculated line.
     *
     * @throws ValidationException
     */
    public function issueOrders(PerformanceCycle $cycle): int
    {
        $this->ensureMode('order');

        if (! $this->orders->hasTemplate(self::ORDER_TEMPLATE)) {
            throw ValidationException::withMessages(['bonus' => __('performance_evaluation::kpi.bonus.errors.order_template_missing')]);
        }

        return DB::transaction(function () use ($cycle): int {
            $lines = $this->payableLines($cycle, 'personnel');

            foreach ($lines as $line) {
                $order = $this->orders->draft(self::ORDER_TEMPLATE, $line->personnel, [
                    self::FIELD_REASON => __('performance_evaluation::kpi.bonus.order_reason', [
                        'cycle' => $cycle->name,
                        'score' => number_format((float) $line->score, 1, ',', ''),
                    ]),
                    self::FIELD_AMOUNT => number_format($line->amount, 2, '.', ''),
                    self::FIELD_BASIS => __('performance_evaluation::kpi.bonus.order_basis', ['cycle' => $cycle->name]),
                ], 'KPI-'.$cycle->id.'-'.$line->id);

                $line->update(['status' => 'ordered', 'order_log_id' => $order->id]);
            }

            return $lines->count();
        });
    }

    /**
     * Calculated, non-zero lines whose card is still approved or closed, row-locked so a
     * double click cannot pay or order the same line twice. Call inside a transaction.
     *
     * @return Collection<int, PerformanceBonusCalculation>
     */
    private function payableLines(PerformanceCycle $cycle, string $personnel): Collection
    {
        return PerformanceBonusCalculation::query()
            ->where('performance_cycle_id', $cycle->id)
            ->where('status', 'calculated')
            ->whereHas('scorecard', fn ($query) => $query->whereIn('status', ['approved', 'closed']))
            ->with($personnel)
            ->lockForUpdate()
            ->get()
            ->filter(fn (PerformanceBonusCalculation $line): bool => $line->amount > 0); // encrypted: filtered after decrypting
    }

    /**
     * Spec §7 payout matrix: the band whose lower bound the score reaches.
     *
     * @param  array<int, array{0: float|int, 1: float|int}>  $bands
     */
    public function payoutPct(?float $score, array $bands): float
    {
        if ($score === null) {
            return 0.0;
        }

        $pct = 0.0;
        foreach (collect($bands)->sortBy('0') as [$from, $payout]) {
            if ($score + 1e-9 >= (float) $from) {
                $pct = (float) $payout;
            }
        }

        return $pct;
    }

    /**
     * Below the gate the company result cuts bonuses to the floor %; above it the
     * multiplier of the reached band applies. No company result entered → 1.
     */
    public function companyMultiplier(PerformanceBonusRule $rule): float
    {
        return $this->multiplierFor($rule->company_result, $rule);
    }

    /**
     * The same gate and multiplier table turn a company or a unit result into its
     * multiplier. No result entered → 1.
     */
    public function multiplierFor(?float $result, PerformanceBonusRule $rule): float
    {
        if ($result === null) {
            return 1.0;
        }

        if ($result < $rule->company_gate) {
            return round($rule->gate_floor_pct / 100, 4);
        }

        $multiplier = 1.0;
        foreach (collect($rule->company_multipliers)->sortBy('0') as [$from, $value]) {
            if ($result + 1e-9 >= (float) $from) {
                $multiplier = (float) $value;
            }
        }

        return $multiplier;
    }

    /**
     * A unit's result applies to everyone below it: a person takes the result of their
     * own unit, else of the nearest parent unit that has one.
     *
     * @return callable(?int): ?float
     */
    private function unitResultResolver(PerformanceBonusRule $rule): callable
    {
        $results = collect($rule->unit_results ?? [])->mapWithKeys(fn (array $pair): array => [(int) $pair[0] => (float) $pair[1]]);

        if ($results->isEmpty()) {
            return fn (?int $structureId): ?float => null;
        }

        // ponytail: one flat read of the org chart per preview; the chart is small.
        $parents = Structure::query()->pluck('parent_id', 'id');

        return function (?int $structureId) use ($results, $parents): ?float {
            for ($id = $structureId, $guard = 0; $id !== null && $guard < 50; $id = $parents->get($id), $guard++) {
                if ($results->has((int) $id)) {
                    return $results->get((int) $id);
                }
            }

            return null;
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function validRule(array $data): array
    {
        $bands = $this->pairs($data['payout_bands'] ?? []);
        $multipliers = $this->pairs($data['company_multipliers'] ?? []);
        $units = $this->pairs($data['unit_results'] ?? []);
        $positions = $this->pairs($data['position_targets'] ?? []);
        $number = fn (string $key): ?float => is_numeric($data[$key] ?? null) ? (float) $data[$key] : null;
        $errors = [];

        if ($bands === [] || collect($bands)->contains(fn (array $band): bool => $band[1] < 0)) {
            $errors['payout_bands'] = __('performance_evaluation::kpi.bonus.errors.bands_invalid');
        }

        if (collect($multipliers)->contains(fn (array $pair): bool => $pair[1] < 0)) {
            $errors['company_multipliers'] = __('performance_evaluation::kpi.bonus.errors.bands_invalid');
        }

        if (collect($units)->contains(fn (array $pair): bool => $pair[1] < 0) || count(array_unique(array_column($units, 0))) !== count($units)) {
            $errors['unit_results'] = __('performance_evaluation::kpi.bonus.errors.pairs_invalid');
        }

        if (collect($positions)->contains(fn (array $pair): bool => $pair[1] < 0 || $pair[1] > 100) || count(array_unique(array_column($positions, 0))) !== count($positions)) {
            $errors['position_targets'] = __('performance_evaluation::kpi.bonus.errors.pairs_invalid');
        }

        foreach (['target_pct' => [0, 100], 'reward_months' => [0.01, 24], 'company_gate' => [0, 200], 'gate_floor_pct' => [0, 100], 'cap_pct' => [100, 500]] as $key => [$min, $max]) {
            $value = $number($key);
            if ($value === null || $value < $min || $value > $max) {
                $errors[$key] = __('performance_evaluation::kpi.bonus.errors.out_of_range', ['min' => $min, 'max' => $max]);
            }
        }

        foreach (['company_result', 'fund'] as $key) {
            if (filled($data[$key] ?? null) && ($number($key) === null || $number($key) < 0)) {
                $errors[$key] = __('performance_evaluation::kpi.bonus.errors.not_a_number');
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'target_pct' => $number('target_pct'),
            'reward_months' => $number('reward_months'),
            'payout_bands' => $bands,
            'company_result' => $number('company_result'),
            'company_gate' => $number('company_gate'),
            'gate_floor_pct' => $number('gate_floor_pct'),
            'company_multipliers' => $multipliers,
            'unit_results' => $units,
            'position_targets' => $positions,
            'cap_pct' => $number('cap_pct'),
            'fund' => $number('fund'),
            'scale_to_fund' => (bool) ($data['scale_to_fund'] ?? false),
            'pay_in_probation' => (bool) ($data['pay_in_probation'] ?? false),
        ];
    }

    /**
     * Keeps the filled [from, value] rows, sorted by their lower bound.
     *
     * @param  array<int, mixed>  $rows
     * @return array<int, array{0: float, 1: float}>
     */
    private function pairs(array $rows): array
    {
        return collect($rows)
            ->filter(fn ($row): bool => is_array($row) && is_numeric($row[0] ?? null) && is_numeric($row[1] ?? null))
            ->map(fn (array $row): array => [(float) $row[0], (float) $row[1]])
            ->sortBy('0')
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function storedRow(PerformanceScorecard $card): array
    {
        $line = $card->bonus;

        return [
            ...$line->only(['personnel_id', 'score', 'base_salary', 'period_months', 'target_pct', 'payout_pct', 'company_mult', 'unit_mult', 'prorata', 'scale_factor', 'amount', 'status', 'order_log_id']),
            'scorecard_id' => $card->id,
            'personnel' => $card->personnel,
            'final' => true,
        ];
    }

    /**
     * Spec §5.1: no bonus while the person is still on probation when the cycle ends.
     */
    private function inProbationOn(?Personnel $personnel, Carbon $date): bool
    {
        $joined = $personnel?->getRawOriginal('join_work_date');
        $amount = (int) ($personnel->probation_amount ?? 0);

        if ($joined === null || $amount <= 0) {
            return false;
        }

        $end = match ($personnel->probation_unit) {
            'day' => Carbon::parse($joined)->addDays($amount),
            'week' => Carbon::parse($joined)->addWeeks($amount),
            default => Carbon::parse($joined)->addMonths($amount),
        };

        return $end->gt($date);
    }

    /**
     * Monthly base pay per staff number, as of the cycle's last day. Without the
     * compensation module every salary is unknown and the line pays nothing.
     *
     * @param  Collection<int, PerformanceScorecard>  $cards
     * @return Collection<string, float>
     */
    private function baseSalaries(Collection $cards, PerformanceCycle $cycle): Collection
    {
        $tabelNos = $cards->pluck('personnel.tabel_no')->filter()->map(fn ($tabel): string => (string) $tabel)->unique()->values()->all();

        if ($tabelNos === [] || ! app()->bound(CompensationReadRepository::class)) {
            return collect();
        }

        return app(CompensationReadRepository::class)
            ->baseAmountsFor($tabelNos, Carbon::parse($cycle->period_end)->toDateString())
            ->mapWithKeys(fn ($amount, $tabel): array => [(string) $tabel => (float) $amount]);
    }

    private function periodMonths(PerformanceCycle $cycle): float
    {
        $days = Carbon::parse($cycle->period_start)->diffInDays(Carbon::parse($cycle->period_end)) + 1;

        return max(1, round($days / 30.4375));
    }

    /**
     * @throws ValidationException
     */
    private function ensureMode(string $mode): void
    {
        if ($this->mode() !== $mode) {
            throw ValidationException::withMessages(['bonus' => __('performance_evaluation::kpi.bonus.errors.wrong_mode')]);
        }
    }
}
