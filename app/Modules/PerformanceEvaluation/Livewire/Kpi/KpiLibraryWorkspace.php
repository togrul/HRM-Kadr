<?php

namespace App\Modules\PerformanceEvaluation\Livewire\Kpi;

use App\Livewire\Traits\SideModalAction;
use App\Models\PerformanceFormTemplate;
use App\Models\PerformanceKpi;
use App\Models\PerformanceKpiTemplate;
use App\Models\Position;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\KpiLibraryService;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\KpiTemplateService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * KPI library and position templates (spec §4.1–4.2). Scoring rules live in the
 * services; this component only collects input.
 */
class KpiLibraryWorkspace extends Component
{
    use AuthorizesRequests;
    use SideModalAction;

    public string $section = 'kpis';

    public ?int $editingKpiId = null;

    /** @var array<string, mixed> */
    public array $kpiForm = [];

    public ?int $editingTemplateId = null;

    /** @var array<string, mixed> */
    public array $templateForm = [];

    /** @var array<int, array<string, mixed>> */
    public array $templateItems = [];

    /** @var array<int, int|string> */
    public array $templatePositionIds = [];

    /** @var array<int, string> */
    public array $warnings = [];

    public function mount(): void
    {
        $this->authorize('show-performance-evaluation');
        $this->kpiForm = $this->kpiDefaults();
        $this->templateForm = $this->templateDefaults();
    }

    /**
     * @return Collection<int, PerformanceKpi>
     */
    #[Computed]
    public function kpis(): Collection
    {
        return PerformanceKpi::query()->orderByRaw("case status when 'active' then 0 when 'draft' then 1 else 2 end")->orderBy('code')->get();
    }

    /**
     * @return Collection<int, PerformanceKpiTemplate>
     */
    #[Computed]
    public function templates(): Collection
    {
        return PerformanceKpiTemplate::query()
            ->with(['positions:id,name', 'items.kpi:id,code,name'])
            ->withCount('items')
            ->withSum('items', 'weight')
            ->orderBy('name')
            ->get();
    }

    /**
     * Every position for the template checklist; `taken` names the other template a
     * position already belongs to, so it can be shown but not picked.
     *
     * @return array<int, array{id: int, name: string, taken: string|null}>
     */
    #[Computed]
    public function positionOptions(): array
    {
        $takenBy = DB::table('performance_kpi_template_positions')
            ->join('performance_kpi_templates', 'performance_kpi_templates.id', '=', 'performance_kpi_template_positions.performance_kpi_template_id')
            ->whereNull('performance_kpi_templates.deleted_at')
            ->when($this->editingTemplateId, fn ($query) => $query->where('performance_kpi_templates.id', '!=', $this->editingTemplateId))
            ->pluck('performance_kpi_templates.name', 'performance_kpi_template_positions.position_id');

        return Position::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Position $position): array => [
                'id' => (int) $position->id,
                'name' => (string) $position->name,
                'taken' => $takenBy->get($position->id),
            ])
            ->all();
    }

    /**
     * @return array<int, string> active evaluation form templates that can serve as the competency block
     */
    #[Computed]
    public function formTemplateOptions(): array
    {
        return PerformanceFormTemplate::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all();
    }

    public function openKpiForm(?int $id = null): void
    {
        $this->authorize('manage-performance-evaluation');
        $this->resetValidation();
        $this->editingKpiId = $id;
        $this->kpiForm = $id
            ? [...$this->kpiDefaults(), ...PerformanceKpi::query()->findOrFail($id)->only(array_keys($this->kpiDefaults()))]
            : $this->kpiDefaults();
        $this->openSideMenu('kpi-form');
    }

    public function saveKpi(): void
    {
        $this->authorize('manage-performance-evaluation');

        $data = $this->validate([
            'kpiForm.code' => ['required', 'string', 'max:32', 'regex:/^[A-Z0-9_]+$/', Rule::unique('performance_kpis', 'code')->ignore($this->editingKpiId)],
            'kpiForm.name' => ['required', 'string', 'max:255'],
            'kpiForm.description' => ['nullable', 'string', 'max:2000'],
            'kpiForm.type' => ['required', Rule::in(PerformanceKpi::TYPES)],
            'kpiForm.direction' => ['required', Rule::in(PerformanceKpi::DIRECTIONS)],
            'kpiForm.unit' => ['required', Rule::in(PerformanceKpi::UNITS)],
            'kpiForm.frequency' => ['required', Rule::in(PerformanceKpi::FREQUENCIES)],
            'kpiForm.aggregation' => ['required', Rule::in(PerformanceKpi::AGGREGATIONS)],
            'kpiForm.perspective' => ['required', Rule::in(PerformanceKpi::PERSPECTIVES)],
            'kpiForm.indicator_kind' => ['nullable', 'in:lead,lag'],
            'kpiForm.evidence_required' => ['boolean'],
            'kpiForm.status' => ['required', Rule::in(PerformanceKpi::STATUSES)],
        ])['kpiForm'];

        app(KpiLibraryService::class)->save([...$data, 'indicator_kind' => $data['indicator_kind'] ?: null], $this->editingKpiId ? PerformanceKpi::query()->findOrFail($this->editingKpiId) : null);

        $this->closeSideMenu();
        unset($this->kpis);
        $this->dispatch('notify', type: 'success', message: __('performance_evaluation::kpi.messages.kpi_saved'));
    }

    public function archiveKpi(int $id): void
    {
        $this->authorize('manage-performance-evaluation');
        app(KpiLibraryService::class)->archive(PerformanceKpi::query()->findOrFail($id));
        unset($this->kpis);
    }

    public function deleteKpi(int $id): void
    {
        $this->authorize('manage-performance-evaluation');
        app(KpiLibraryService::class)->delete(PerformanceKpi::query()->findOrFail($id));
        unset($this->kpis);
        $this->dispatch('notify', type: 'success', message: __('performance_evaluation::kpi.messages.kpi_deleted'));
    }

    public function openTemplateForm(?int $id = null): void
    {
        $this->authorize('manage-performance-evaluation');
        $this->resetValidation();
        $this->warnings = [];
        $this->editingTemplateId = $id;

        if ($id === null) {
            $this->templateForm = $this->templateDefaults();
            $this->templateItems = [$this->blankItem()];
            $this->templatePositionIds = [];
        } else {
            $template = PerformanceKpiTemplate::query()->with(['items', 'positions:id'])->findOrFail($id);
            $this->templateForm = $template->only(array_keys($this->templateDefaults()));
            $this->templateItems = $template->items
                ->map(fn ($item): array => $item->only(array_keys($this->blankItem())))
                ->all();
            $this->templatePositionIds = $template->positions->pluck('id')->map(fn ($id): int => (int) $id)->all();
        }

        unset($this->positionOptions);
        $this->openSideMenu('template-form');
    }

    public function addTemplateItem(): void
    {
        $this->templateItems[] = $this->blankItem();
    }

    public function removeTemplateItem(int $index): void
    {
        unset($this->templateItems[$index]);
        $this->templateItems = array_values($this->templateItems);
    }

    public function saveTemplate(): void
    {
        $this->authorize('manage-performance-evaluation');

        $validated = $this->validate([
            'templateForm.name' => ['required', 'string', 'max:255'],
            'templateForm.code' => ['nullable', 'string', 'max:40'],
            'templateForm.period_type' => ['required', Rule::in(PerformanceKpiTemplate::PERIOD_TYPES)],
            'templateForm.kpi_weight_share' => ['required', 'numeric', 'min:0', 'max:100'],
            'templateForm.competency_weight_share' => ['required', 'numeric', 'min:0', 'max:100'],
            'templateForm.performance_form_template_id' => ['nullable', 'integer', 'exists:performance_form_templates,id'],
            'templateForm.status' => ['required', 'in:active,archived'],
            'templateItems.*.performance_kpi_id' => ['required', 'integer', 'exists:performance_kpis,id'],
            'templateItems.*.weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'templateItems.*.target' => ['nullable', 'numeric'],
            'templateItems.*.range_min' => ['nullable', 'numeric'],
            'templateItems.*.range_max' => ['nullable', 'numeric'],
            'templateItems.*.threshold' => ['nullable', 'numeric', 'min:0'],
            'templateItems.*.stretch' => ['nullable', 'numeric', 'min:0'],
            'templateItems.*.cap' => ['nullable', 'numeric', 'min:0'],
            'templateItems.*.target_editable' => ['boolean'],
            'templatePositionIds.*' => ['integer', 'exists:positions,id'],
        ]);

        $items = array_map(
            fn (array $item): array => array_map(fn ($value) => $value === '' ? null : $value, $item),
            $validated['templateItems'] ?? []
        );

        $result = app(KpiTemplateService::class)->save(
            [...$validated['templateForm'], 'performance_form_template_id' => ($validated['templateForm']['performance_form_template_id'] ?? null) ?: null],
            $items,
            array_map('intval', $this->templatePositionIds),
            $this->editingTemplateId ? PerformanceKpiTemplate::query()->findOrFail($this->editingTemplateId) : null,
        );

        $this->warnings = $result['warnings'];
        $this->editingTemplateId = $result['template']->id;
        unset($this->templates);
        $this->dispatch('notify', type: 'success', message: __('performance_evaluation::kpi.messages.template_saved'));

        if ($this->warnings === []) {
            $this->closeSideMenu();
        }
    }

    public function deleteTemplate(int $id): void
    {
        $this->authorize('manage-performance-evaluation');
        PerformanceKpiTemplate::query()->findOrFail($id)->delete();
        unset($this->templates);
        $this->dispatch('notify', type: 'success', message: __('performance_evaluation::kpi.messages.template_deleted'));
    }

    public function render(): View
    {
        return view('performance-evaluation::livewire.performance-evaluation.kpi.library-workspace');
    }

    /**
     * @return array<string, mixed>
     */
    private function kpiDefaults(): array
    {
        return [
            'code' => '', 'name' => '', 'description' => '', 'type' => 'quantitative', 'direction' => 'higher_better',
            'unit' => 'percent', 'frequency' => 'quarterly', 'aggregation' => 'last', 'perspective' => 'process',
            'indicator_kind' => '', 'evidence_required' => false, 'status' => 'active',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function templateDefaults(): array
    {
        return ['name' => '', 'code' => '', 'period_type' => 'quarterly', 'kpi_weight_share' => 100, 'competency_weight_share' => 0, 'performance_form_template_id' => null, 'status' => 'active'];
    }

    /**
     * @return array<string, mixed>
     */
    private function blankItem(): array
    {
        return [
            'performance_kpi_id' => '', 'weight' => '', 'target' => '', 'range_min' => '', 'range_max' => '',
            'threshold' => 80, 'stretch' => '', 'cap' => 120, 'target_editable' => false,
        ];
    }
}
