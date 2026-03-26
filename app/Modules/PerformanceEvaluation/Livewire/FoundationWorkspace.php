<?php

namespace App\Modules\PerformanceEvaluation\Livewire;

use App\Models\PerformanceCycle;
use App\Models\PerformanceFormTemplate;
use App\Models\PerformanceFormTemplateItem;
use App\Models\PerformanceFormTemplateSection;
use App\Modules\PerformanceEvaluation\Livewire\Concerns\HandlesPerformanceFoundationMutations;
use App\Services\HrPolicies\HrPolicyPackService;
use Livewire\Attributes\Isolate;

#[Isolate]
class FoundationWorkspace extends AbstractPerformanceWorkspace
{
    use HandlesPerformanceFoundationMutations;

    protected function allowedTabs(): array
    {
        return app(HrPolicyPackService::class)->workflowTabs('performance_evaluation', ['cycles', 'templates']);
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
