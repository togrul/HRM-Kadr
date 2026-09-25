<?php

namespace App\Modules\PerformanceEvaluation\Livewire\Kpi;

use App\Livewire\Traits\SideModalAction;
use App\Models\PerformanceFormTemplate;
use App\Models\PerformanceKpi;
use App\Models\PerformanceKpiTemplate;
use App\Models\PerformanceNotificationTemplate;
use App\Models\Personnel;
use App\Models\Position;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\InternalKpiMetrics;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\KpiFormula;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\KpiLibraryService;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\KpiNotificationDelivery;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\KpiTemplateService;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\RestKpiConnector;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use RuntimeException;

/**
 * KPI library and position templates (spec §4.1–4.2). Scoring rules live in the
 * services; this component only collects input.
 *
 * @property-read SupportCollection<string, PerformanceNotificationTemplate> $notificationTemplates
 */
class KpiLibraryWorkspace extends Component
{
    use AuthorizesRequests;
    use SideModalAction;

    public string $section = 'kpis';

    #[Locked]
    public ?int $editingKpiId = null;

    /** @var array<string, mixed> */
    public array $kpiForm = [];

    #[Locked]
    public ?int $editingTemplateId = null;

    /** @var array<string, mixed> */
    public array $templateForm = [];

    /** @var array<int, array<string, mixed>> */
    public array $templateItems = [];

    /** @var array<int, int|string> */
    public array $templatePositionIds = [];

    /** @var array<int, string> */
    public array $warnings = [];

    public string $templateLocale = 'az';

    public ?string $notificationKey = null;

    /** @var array{subject: string, body: string} */
    public array $notificationForm = ['subject' => '', 'body' => ''];

    /**
     * External source of the KPI being edited. Secrets are never sent back: a blank
     * token or password keeps the stored one.
     *
     * @var array<string, string>
     */
    public array $connectorForm = [];

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
        $kpi = $id ? PerformanceKpi::query()->findOrFail($id) : null;
        $this->kpiForm = $kpi
            ? [...$this->kpiDefaults(), ...$kpi->only(array_keys($this->kpiDefaults()))]
            : $this->kpiDefaults();
        $this->kpiForm['source_metric'] = $kpi?->data_source === 'calculated' ? 'formula' : ($this->kpiForm['source_metric'] ?? '');
        $this->kpiForm['formula'] ??= '';
        $this->connectorForm = [
            ...$this->connectorDefaults(),
            ...collect($kpi->integration_config ?? [])->only(['url', 'auth', 'username', 'value_path'])->all(),
        ];
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
            'kpiForm.source_metric' => ['nullable', Rule::in([...array_keys(InternalKpiMetrics::METRICS), 'formula'])],
            'kpiForm.formula' => ['required_if:kpiForm.source_metric,formula', 'nullable', 'string', 'max:2000'],
            'kpiForm.status' => ['required', Rule::in(PerformanceKpi::STATUSES)],
            ...$this->connectorRules(),
        ])['kpiForm'];

        $isFormula = $data['source_metric'] === 'formula';
        if ($isFormula) {
            $this->checkFormula($data['code'], (string) $data['formula'], $data['type']);
        }

        app(KpiLibraryService::class)->save([
            'integration_config' => $data['source_metric'] === 'rest' ? $this->connectorConfig() : null,
            ...$data,
            'indicator_kind' => $data['indicator_kind'] ?: null,
            'formula' => $isFormula ? trim((string) $data['formula']) : null,
            'source_metric' => $isFormula ? null : ($data['source_metric'] ?: null),
            'data_source' => match ($data['source_metric'] ?: null) {
                null => 'manual',
                'formula' => 'calculated',
                'rest' => 'integration',
                default => 'hrm',
            },
        ], $this->editingKpiId ? PerformanceKpi::query()->findOrFail($this->editingKpiId) : null);

        $this->closeSideMenu();
        unset($this->kpis);
        $this->dispatch('notify', type: 'success', message: __('performance_evaluation::kpi.messages.kpi_saved'));
    }

    /**
     * HR's rewordings for the chosen language, by event.
     *
     * @return SupportCollection<string, PerformanceNotificationTemplate>
     */
    #[Computed]
    public function notificationTemplates(): SupportCollection
    {
        return app(KpiNotificationDelivery::class)->templates($this->templateLocale);
    }

    public function updatedTemplateLocale(): void
    {
        abort_unless(in_array($this->templateLocale, config('app.locales', ['az']), true), 404);
        unset($this->notificationTemplates);
    }

    public function openNotificationTemplate(string $key): void
    {
        $this->authorize('manage-performance-evaluation');
        abort_unless(in_array($key, KpiNotificationDelivery::EVENTS, true), 404);

        $template = $this->notificationTemplates->get($key);
        $this->notificationKey = $key;
        $this->notificationForm = $template
            ? ['subject' => $template->subject, 'body' => $template->body]
            : app(KpiNotificationDelivery::class)->defaultTemplate($key, $this->templateLocale);
        $this->resetValidation();
        $this->openSideMenu('notification-template');
    }

    public function saveNotificationTemplate(): void
    {
        $this->authorize('manage-performance-evaluation');
        abort_unless(in_array($this->notificationKey, KpiNotificationDelivery::EVENTS, true), 404);
        $data = $this->validate([
            'notificationForm.subject' => ['required', 'string', 'max:255'],
            'notificationForm.body' => ['required', 'string', 'max:2000'],
        ])['notificationForm'];

        PerformanceNotificationTemplate::query()->updateOrCreate(
            ['key' => $this->notificationKey, 'locale' => $this->templateLocale],
            [...$data, 'updated_by' => auth()->id()],
        );

        unset($this->notificationTemplates);
        $this->closeSideMenu();
        $this->dispatch('notify', type: 'success', message: __('performance_evaluation::kpi.notification_templates.saved'));
    }

    public function resetNotificationTemplate(string $key): void
    {
        $this->authorize('manage-performance-evaluation');
        PerformanceNotificationTemplate::query()->where('key', $key)->where('locale', $this->templateLocale)->delete();

        unset($this->notificationTemplates);
        $this->closeSideMenu();
        $this->dispatch('notify', type: 'success', message: __('performance_evaluation::kpi.notification_templates.reset_done'));
    }

    /**
     * Checks the formula and shows a test result with every referenced KPI set to 100.
     */
    public function testFormula(): void
    {
        $this->authorize('manage-performance-evaluation');
        $this->checkFormula((string) ($this->kpiForm['code'] ?? ''), (string) ($this->kpiForm['formula'] ?? ''), (string) ($this->kpiForm['type'] ?? 'quantitative'));

        $formula = app(KpiFormula::class);
        $refs = $formula->references((string) $this->kpiForm['formula']);
        $value = $formula->evaluate((string) $this->kpiForm['formula'], array_fill_keys($refs, 100.0));

        $this->dispatch('notify', type: 'success', message: __('performance_evaluation::kpi.formula.test_ok', [
            'refs' => $refs === [] ? '—' : implode(', ', $refs),
            'value' => $value ?? '—',
        ]));
    }

    /**
     * Syntax, known KPI codes and no circular reference (spec §4.1).
     *
     * @throws ValidationException
     */
    private function checkFormula(string $code, string $expression, string $type): void
    {
        $formula = app(KpiFormula::class);
        $fail = fn (string $message) => throw ValidationException::withMessages(['kpiForm.formula' => $message]);

        if ($type !== 'quantitative') {
            $fail(__('performance_evaluation::kpi.formula.errors.quantitative_only'));
        }

        try {
            $refs = $formula->references($expression);
        } catch (InvalidArgumentException $exception) {
            $fail($exception->getMessage());
        }

        $library = PerformanceKpi::query()->where('code', '!=', $code)->pluck('formula', 'code');
        $unknown = collect($refs)->reject(fn (string $ref): bool => $library->has($ref) || $ref === $code);
        if ($unknown->isNotEmpty()) {
            $fail(__('performance_evaluation::kpi.formula.errors.unknown_kpi', ['codes' => $unknown->implode(', ')]));
        }

        // Walk the references: reaching this KPI again means a cycle.
        $graph = $library->map(fn (?string $expression): array => filled($expression) ? rescue(fn () => $formula->references($expression), [], false) : [])->all();
        $graph[$code] = $refs;
        $stack = $refs;
        $seen = [];
        while ($stack !== []) {
            $next = array_pop($stack);
            if ($next === $code) {
                $fail(__('performance_evaluation::kpi.formula.errors.circular'));
            }
            if (! isset($seen[$next])) {
                $seen[$next] = true;
                array_push($stack, ...($graph[$next] ?? []));
            }
        }
    }

    /**
     * Tries the source for one employee over the current quarter and reports the value.
     */
    public function testConnector(): void
    {
        $this->authorize('manage-performance-evaluation');
        $this->validate($this->connectorRules(true));

        $personnel = Personnel::query()->whereNotNull('tabel_no')->where('is_pending', false)->orderBy('id')->first();
        if ($personnel === null) {
            $this->dispatch('notify', type: 'error', message: __('performance_evaluation::kpi.connector.errors.no_person'));

            return;
        }

        try {
            $value = app(RestKpiConnector::class)->fetch($this->connectorConfig(), $personnel, now()->firstOfQuarter(), today());
            $this->dispatch('notify', type: 'success', message: __('performance_evaluation::kpi.connector.test_ok', ['value' => $value, 'employee' => $personnel->fullname]));
        } catch (RuntimeException $exception) {
            $this->dispatch('notify', type: 'error', message: $exception->getMessage());
        }
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
     * @return array<string, array<int, mixed>>
     */
    private function connectorRules(bool $always = false): array
    {
        $required = $always ? 'required' : 'required_if:kpiForm.source_metric,rest';

        return [
            'connectorForm.url' => [$required, 'nullable', 'string', 'max:1000', 'regex:#^https?://#i'],
            'connectorForm.auth' => [$required, 'nullable', Rule::in(RestKpiConnector::AUTH_TYPES)],
            'connectorForm.username' => ['nullable', 'string', 'max:255'],
            'connectorForm.secret' => ['nullable', 'string', 'max:2000'],
            'connectorForm.value_path' => [$required, 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * The form's connector with the stored secret kept when none was typed.
     *
     * @return array<string, string>
     */
    private function connectorConfig(): array
    {
        $stored = $this->editingKpiId ? (PerformanceKpi::query()->find($this->editingKpiId)->integration_config ?? []) : [];
        $secret = trim((string) ($this->connectorForm['secret'] ?? ''));
        $auth = (string) ($this->connectorForm['auth'] ?? 'none');
        $url = trim((string) ($this->connectorForm['url'] ?? ''));

        // A saved credential only ever goes back to the host it was saved for.
        if (parse_url($url, PHP_URL_HOST) !== parse_url((string) ($stored['url'] ?? ''), PHP_URL_HOST)) {
            $stored = [];
        }

        return [
            'url' => $url,
            'auth' => $auth,
            'username' => trim((string) ($this->connectorForm['username'] ?? '')),
            'token' => $auth === 'bearer' ? ($secret !== '' ? $secret : (string) ($stored['token'] ?? '')) : '',
            'password' => $auth === 'basic' ? ($secret !== '' ? $secret : (string) ($stored['password'] ?? '')) : '',
            'value_path' => trim((string) ($this->connectorForm['value_path'] ?? '')),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function connectorDefaults(): array
    {
        return ['url' => '', 'auth' => 'none', 'username' => '', 'secret' => '', 'value_path' => ''];
    }

    /**
     * @return array<string, mixed>
     */
    private function kpiDefaults(): array
    {
        return [
            'code' => '', 'name' => '', 'description' => '', 'type' => 'quantitative', 'direction' => 'higher_better',
            'unit' => 'percent', 'frequency' => 'quarterly', 'aggregation' => 'last', 'perspective' => 'process',
            'indicator_kind' => '', 'evidence_required' => false, 'source_metric' => '', 'formula' => '', 'status' => 'active',
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
