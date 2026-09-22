<?php

namespace App\Modules\PerformanceEvaluation\Livewire\Kpi;

use App\Models\PerformanceBonusRule;
use App\Models\PerformanceCycle;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\BonusService;
use App\Support\Livewire\DownloadsReportsTable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The cycle's bonus: HR tunes the rule, sees what it would pay while editing (the
 * what-if simulation), stores the lines and hands them over — to payroll in the
 * company model, as award orders in the military regime.
 */
class BonusWorkspace extends Component
{
    use AuthorizesRequests;
    use DownloadsReportsTable;

    public ?int $cycleId = null;

    /** @var array<string, mixed> */
    public array $ruleForm = [];

    public function mount(): void
    {
        $this->authorize('manage-performance-evaluation');
        $this->cycleId = PerformanceCycle::query()
            ->orderByRaw("case status when 'active' then 0 when 'closed' then 1 else 2 end")
            ->orderByDesc('period_start')
            ->value('id');
        $this->loadRule();
    }

    public function updatedCycleId(): void
    {
        $this->loadRule();
    }

    #[Computed]
    public function cycles(): Collection
    {
        return PerformanceCycle::query()->orderByDesc('period_start')->get(['id', 'name', 'status']);
    }

    #[Computed]
    public function mode(): string
    {
        return app(BonusService::class)->mode();
    }

    /**
     * Live result of the rule as it stands in the form — saved or not.
     *
     * @return array<string, mixed>|null
     */
    #[Computed]
    public function preview(): ?array
    {
        $cycle = $this->cycle();
        if ($cycle === null) {
            return null;
        }

        $rule = app(BonusService::class)->rule($cycle);
        $rule->fill($this->formToRule());

        return app(BonusService::class)->preview($cycle, $rule);
    }

    public function isDirty(): bool
    {
        $cycle = $this->cycle();

        return $cycle !== null && $this->ruleToForm(app(BonusService::class)->rule($cycle)) != $this->ruleForm;
    }

    public function addBand(string $list): void
    {
        abort_unless(in_array($list, ['payout_bands', 'company_multipliers'], true), 404);
        $this->ruleForm[$list][] = ['from' => null, 'value' => null];
    }

    public function removeBand(string $list, int $index): void
    {
        abort_unless(in_array($list, ['payout_bands', 'company_multipliers'], true), 404);
        unset($this->ruleForm[$list][$index]);
        $this->ruleForm[$list] = array_values($this->ruleForm[$list]);
    }

    public function resetRule(): void
    {
        $this->loadRule();
    }

    public function saveRule(): void
    {
        $this->authorize('manage-performance-evaluation');
        app(BonusService::class)->saveRule($this->requireCycle(), $this->formToRule(), auth()->user());
        $this->loadRule();
        $this->dispatch('notify', type: 'success', message: __('performance_evaluation::kpi.bonus.messages.rule_saved'));
    }

    public function calculate(): void
    {
        $this->authorize('manage-performance-evaluation');
        $cycle = $this->requireCycle();
        app(BonusService::class)->saveRule($cycle, $this->formToRule(), auth()->user());
        $count = app(BonusService::class)->calculate($cycle);

        $this->loadRule();
        $this->dispatch('notify', type: 'success', message: __('performance_evaluation::kpi.bonus.messages.calculated', ['count' => $count]));
    }

    public function exportPayroll(): BinaryFileResponse
    {
        $this->authorize('manage-performance-evaluation');
        $cycle = $this->requireCycle();
        $rows = app(BonusService::class)->exportToPayroll($cycle);
        unset($this->preview);

        $columns = collect(['tabel_no', 'personnel', 'cycle', 'amount', 'currency', 'pay_month'])
            ->map(fn (string $key): array => ['key' => $key, 'label' => __('performance_evaluation::kpi.bonus.export_columns.'.$key)])
            ->all();

        return $this->downloadReportTable($rows, $columns, 'kpi-bonus-'.$cycle->id.'.xlsx');
    }

    public function issueOrders(): void
    {
        $this->authorize('manage-performance-evaluation');
        $count = app(BonusService::class)->issueOrders($this->requireCycle());
        unset($this->preview);

        $this->dispatch('notify', type: $count > 0 ? 'success' : 'info', message: __('performance_evaluation::kpi.bonus.messages.orders_issued', ['count' => $count]));
    }

    public function render(): View
    {
        return view('performance-evaluation::livewire.performance-evaluation.kpi.bonus-workspace');
    }

    private function cycle(): ?PerformanceCycle
    {
        return $this->cycleId ? PerformanceCycle::query()->find($this->cycleId) : null;
    }

    private function requireCycle(): PerformanceCycle
    {
        return $this->cycle() ?? abort(404);
    }

    private function loadRule(): void
    {
        $cycle = $this->cycle();
        $this->ruleForm = $cycle ? $this->ruleToForm(app(BonusService::class)->rule($cycle)) : [];
        $this->resetValidation();
        unset($this->preview);
    }

    /**
     * @return array<string, mixed>
     */
    private function ruleToForm(PerformanceBonusRule $rule): array
    {
        $pairs = fn (?array $rows): array => collect($rows ?? [])->map(fn (array $row): array => ['from' => (float) $row[0], 'value' => (float) $row[1]])->all();

        return [
            'target_pct' => $rule->target_pct,
            'reward_months' => $rule->reward_months,
            'payout_bands' => $pairs($rule->payout_bands),
            'company_result' => $rule->company_result,
            'company_gate' => $rule->company_gate,
            'gate_floor_pct' => $rule->gate_floor_pct,
            'company_multipliers' => $pairs($rule->company_multipliers),
            'cap_pct' => $rule->cap_pct,
            'fund' => $rule->fund,
            'scale_to_fund' => (bool) $rule->scale_to_fund,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formToRule(): array
    {
        $pairs = fn (string $list): array => collect($this->ruleForm[$list] ?? [])
            ->filter(fn ($row): bool => is_numeric($row['from'] ?? null) && is_numeric($row['value'] ?? null))
            ->map(fn (array $row): array => [(float) $row['from'], (float) $row['value']])
            ->sortBy(0)
            ->values()
            ->all();
        $number = fn (string $key): ?float => is_numeric($this->ruleForm[$key] ?? null) ? (float) $this->ruleForm[$key] : null;

        return [
            'target_pct' => $number('target_pct'),
            'reward_months' => $number('reward_months'),
            'payout_bands' => $pairs('payout_bands'),
            'company_result' => $number('company_result'),
            'company_gate' => $number('company_gate'),
            'gate_floor_pct' => $number('gate_floor_pct'),
            'company_multipliers' => $pairs('company_multipliers'),
            'cap_pct' => $number('cap_pct'),
            'fund' => $number('fund'),
            'scale_to_fund' => (bool) ($this->ruleForm['scale_to_fund'] ?? false),
        ];
    }
}
