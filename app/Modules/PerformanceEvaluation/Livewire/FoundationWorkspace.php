<?php

namespace App\Modules\PerformanceEvaluation\Livewire;

use App\Livewire\Traits\SideModalAction;
use App\Models\PerformanceCycle;
use App\Models\PerformanceFormTemplate;
use App\Models\PerformanceFormTemplateItem;
use App\Models\PerformanceFormTemplateSection;
use App\Modules\PerformanceEvaluation\Livewire\Concerns\HandlesPerformanceFoundationMutations;
use App\Services\HrPolicies\HrPolicyPackService;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Isolate;

/**
 * @property-read Collection<int, PerformanceFormTemplate> $builderTemplates
 * @property-read PerformanceFormTemplate|null $builderTemplate
 */
#[Isolate]
class FoundationWorkspace extends AbstractPerformanceWorkspace
{
    use HandlesPerformanceFoundationMutations;
    use SideModalAction;

    /** The form template open in the builder (templates tab). */
    public ?int $builderTemplateId = null;

    protected function allowedTabs(): array
    {
        return app(HrPolicyPackService::class)->workflowTabs('performance_evaluation', ['cycles', 'templates']);
    }

    /**
     * Every cycle, newest first, with how many evaluation forms and KPI cards it holds.
     *
     * @return Collection<int, PerformanceCycle>
     */
    #[Computed]
    public function cycleCards(): Collection
    {
        return PerformanceCycle::query()
            ->withCount([
                'forms',
                'forms as scored_forms_count' => fn ($query) => $query->whereNotNull('final_score'),
                'scorecards',
            ])
            ->orderByRaw("case status when 'active' then 0 when 'draft' then 1 else 2 end")
            ->orderByDesc('period_start')
            ->get();
    }

    public function newCycle(): void
    {
        $this->authorizePerformanceEvaluationManage();
        $this->cancelCycleEdit();
        $this->openSideMenu('form-cycle');
    }

    public function openCycleEditor(int $id): void
    {
        $this->editCycle($id);
        $this->openSideMenu('form-cycle');
    }

    public function saveBuilderCycle(): void
    {
        $this->storeCycle();
        $this->closeSideMenu();
        unset($this->cycleCards);
    }

    /**
     * Every form template with its sections and criteria — the builder's list and tree.
     *
     * @return Collection<int, PerformanceFormTemplate>
     */
    #[Computed]
    public function builderTemplates(): Collection
    {
        return PerformanceFormTemplate::query()
            ->with([
                'sections' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                'sections.items' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                'sections.items.competency:id,name',
            ])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function builderTemplate(): ?PerformanceFormTemplate
    {
        $templates = $this->builderTemplates;

        return $templates->firstWhere('id', $this->builderTemplateId) ?? $templates->first();
    }

    public function selectTemplate(int $id): void
    {
        $this->builderTemplateId = $id;
        unset($this->builderTemplate);
    }

    public function newTemplate(): void
    {
        $this->authorizePerformanceEvaluationManage();
        $this->cancelTemplateEdit();
        $this->openSideMenu('form-template');
    }

    public function openTemplateEditor(int $id): void
    {
        $this->editTemplate($id);
        $this->openSideMenu('form-template');
    }

    public function newSection(int $templateId): void
    {
        $this->authorizePerformanceEvaluationManage();
        $this->cancelSectionEdit();
        $template = $this->builderTemplates->firstWhere('id', $templateId);
        $this->sectionForm['performance_form_template_id'] = $templateId;
        $this->sectionForm['sort_order'] = (int) ($template?->sections->max('sort_order') ?? 0) + 1;
        $this->openSideMenu('form-section');
    }

    public function openSectionEditor(int $id): void
    {
        $this->editSection($id);
        $this->openSideMenu('form-section');
    }

    public function newItem(int $sectionId): void
    {
        $this->authorizePerformanceEvaluationManage();
        $this->cancelItemEdit();
        $section = $this->builderTemplate?->sections->firstWhere('id', $sectionId);
        $this->itemForm['performance_form_template_section_id'] = $sectionId;
        $this->itemForm['sort_order'] = (int) ($section?->items->max('sort_order') ?? 0) + 1;
        $this->openSideMenu('form-item');
    }

    public function openItemEditor(int $id): void
    {
        $this->editItem($id);
        $this->openSideMenu('form-item');
    }

    public function saveBuilderTemplate(): void
    {
        $isNew = $this->editingTemplateId === null;
        $this->storeTemplate();

        if ($isNew) {
            $this->builderTemplateId = (int) PerformanceFormTemplate::query()->latest('id')->value('id');
        }

        $this->closeBuilderPanel();
    }

    public function saveBuilderSection(): void
    {
        $this->storeSection();
        $this->closeBuilderPanel();
    }

    public function saveBuilderItem(): void
    {
        $this->storeItem();
        $this->closeBuilderPanel();
    }

    public function closeBuilderPanel(): void
    {
        $this->closeSideMenu();
        unset($this->builderTemplates, $this->builderTemplate);
    }

    public function confirmDeleteCycle(int $id): void
    {
        $cycle = PerformanceCycle::query()->findOrFail($id);

        $this->confirmDeletion(
            action: 'deleteCycle',
            parameters: [$id],
            message: __('performance_evaluation::dashboard.confirmations.delete_cycle'),
            description: (string) $cycle->name,
            confirmLabel: __('performance_evaluation::dashboard.actions.delete'),
        );
    }

    public function confirmDeleteTemplate(int $id): void
    {
        $template = PerformanceFormTemplate::query()->findOrFail($id);

        $this->confirmDeletion(
            action: 'deleteTemplate',
            parameters: [$id],
            message: __('performance_evaluation::dashboard.confirmations.delete_template'),
            description: (string) $template->name,
            confirmLabel: __('performance_evaluation::dashboard.actions.delete'),
        );
    }

    public function confirmDeleteSection(int $id): void
    {
        $section = PerformanceFormTemplateSection::query()->findOrFail($id);

        $this->confirmDeletion(
            action: 'deleteSection',
            parameters: [$id],
            message: __('performance_evaluation::dashboard.confirmations.delete_section'),
            description: (string) $section->name,
            confirmLabel: __('performance_evaluation::dashboard.actions.delete'),
        );
    }

    public function confirmDeleteItem(int $id): void
    {
        $item = PerformanceFormTemplateItem::query()->findOrFail($id);

        $this->confirmDeletion(
            action: 'deleteItem',
            parameters: [$id],
            message: __('performance_evaluation::dashboard.confirmations.delete_item'),
            description: (string) $item->name,
            confirmLabel: __('performance_evaluation::dashboard.actions.delete'),
        );
    }

    public function render()
    {
        return view('performance-evaluation::livewire.performance-evaluation.foundation-workspace');
    }
}
