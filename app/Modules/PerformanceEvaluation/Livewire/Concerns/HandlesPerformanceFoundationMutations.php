<?php

namespace App\Modules\PerformanceEvaluation\Livewire\Concerns;

use App\Models\PerformanceCycle;
use App\Models\PerformanceFormTemplate;
use App\Models\PerformanceFormTemplateItem;
use App\Models\PerformanceFormTemplateSection;

trait HandlesPerformanceFoundationMutations
{
    public function storeCycle(): void
    {
        $this->authorizePerformanceEvaluationManage();
        $validated = $this->validate([
            'cycleForm.name' => 'required|string|min:2|max:160',
            'cycleForm.cycle_type' => 'required|in:annual,academic,quarterly',
            'cycleForm.period_start' => 'required|date',
            'cycleForm.period_end' => 'required|date|after_or_equal:cycleForm.period_start',
            'cycleForm.status' => 'required|in:draft,active,closed',
            'cycleForm.auto_generate_forms' => 'nullable|boolean',
            'cycleForm.description' => 'nullable|string|max:1000',
        ], attributes: [
            'cycleForm.name' => __('performance_evaluation::dashboard.fields.cycle_name'),
            'cycleForm.cycle_type' => __('performance_evaluation::dashboard.fields.cycle_type'),
            'cycleForm.period_start' => __('performance_evaluation::dashboard.fields.period_start'),
            'cycleForm.period_end' => __('performance_evaluation::dashboard.fields.period_end'),
            'cycleForm.status' => __('performance_evaluation::dashboard.fields.status'),
            'cycleForm.description' => __('performance_evaluation::dashboard.fields.description'),
        ]);

        $payload = [
            'name' => trim((string) data_get($validated, 'cycleForm.name')),
            'cycle_type' => (string) data_get($validated, 'cycleForm.cycle_type'),
            'period_start' => data_get($validated, 'cycleForm.period_start'),
            'period_end' => data_get($validated, 'cycleForm.period_end'),
            'status' => (string) data_get($validated, 'cycleForm.status'),
            'auto_generate_forms' => (bool) (data_get($validated, 'cycleForm.auto_generate_forms') ?? true),
            'description' => data_get($validated, 'cycleForm.description'),
        ];

        if ($this->editingCycleId) {
            PerformanceCycle::query()->findOrFail($this->editingCycleId)->update($payload);
        } else {
            PerformanceCycle::query()->create($payload);
        }

        $this->reset('cycleForm');
        $this->cycleForm = $this->cycleDefaults();
        $this->editingCycleId = null;
        $this->resetValidation();
        $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.cycle_saved'));
    }

    public function storeTemplate(): void
    {
        $this->authorizePerformanceEvaluationManage();
        $validated = $this->validate([
            'templateForm.name' => 'required|string|min:2|max:160',
            'templateForm.code' => 'nullable|string|max:40',
            'templateForm.description' => 'nullable|string|max:1000',
            'templateForm.is_active' => 'nullable|boolean',
        ], attributes: [
            'templateForm.name' => __('performance_evaluation::dashboard.fields.template_name'),
            'templateForm.code' => __('performance_evaluation::dashboard.fields.template_code'),
            'templateForm.description' => __('performance_evaluation::dashboard.fields.description'),
        ]);

        $payload = [
            'name' => trim((string) data_get($validated, 'templateForm.name')),
            'code' => blank(data_get($validated, 'templateForm.code')) ? null : trim((string) data_get($validated, 'templateForm.code')),
            'description' => data_get($validated, 'templateForm.description'),
            'is_active' => (bool) (data_get($validated, 'templateForm.is_active') ?? true),
        ];

        if ($this->editingTemplateId) {
            PerformanceFormTemplate::query()->findOrFail($this->editingTemplateId)->update($payload);
        } else {
            PerformanceFormTemplate::query()->create($payload);
        }

        $this->reset('templateForm', 'searchTemplate');
        $this->templateForm = $this->templateDefaults();
        $this->editingTemplateId = null;
        $this->resetValidation();
        $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.template_saved'));
    }

    public function storeSection(): void
    {
        $this->authorizePerformanceEvaluationManage();
        $validated = $this->validate([
            'sectionForm.performance_form_template_id' => 'required|exists:performance_form_templates,id',
            'sectionForm.name' => 'required|string|min:2|max:160',
            'sectionForm.weight_percent' => 'nullable|numeric|min:0|max:100',
            'sectionForm.sort_order' => 'nullable|integer|min:0',
        ], attributes: [
            'sectionForm.performance_form_template_id' => __('performance_evaluation::dashboard.fields.template'),
            'sectionForm.name' => __('performance_evaluation::dashboard.fields.section_name'),
            'sectionForm.weight_percent' => __('performance_evaluation::dashboard.fields.weight_percent'),
            'sectionForm.sort_order' => __('performance_evaluation::dashboard.fields.sort_order'),
        ]);

        $payload = [
            'performance_form_template_id' => (int) data_get($validated, 'sectionForm.performance_form_template_id'),
            'name' => trim((string) data_get($validated, 'sectionForm.name')),
            'weight_percent' => (float) (data_get($validated, 'sectionForm.weight_percent') ?? 0),
            'sort_order' => (int) (data_get($validated, 'sectionForm.sort_order') ?? 0),
        ];

        if ($this->editingSectionId) {
            PerformanceFormTemplateSection::query()->findOrFail($this->editingSectionId)->update($payload);
        } else {
            PerformanceFormTemplateSection::query()->create($payload);
        }

        $this->reset('sectionForm', 'searchTemplate');
        $this->sectionForm = $this->sectionDefaults();
        $this->editingSectionId = null;
        $this->resetValidation();
        $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.section_saved'));
    }

    public function storeItem(): void
    {
        $this->authorizePerformanceEvaluationManage();
        $validated = $this->validate([
            'itemForm.performance_form_template_section_id' => 'required|exists:performance_form_template_sections,id',
            'itemForm.training_competency_id' => 'required|exists:training_competencies,id',
            'itemForm.name' => 'required|string|min:2|max:160',
            'itemForm.description' => 'nullable|string|max:1000',
            'itemForm.weight_percent' => 'nullable|numeric|min:0|max:100',
            'itemForm.low_score_threshold' => 'nullable|numeric|min:0|max:100',
            'itemForm.requires_comment' => 'nullable|boolean',
            'itemForm.sort_order' => 'nullable|integer|min:0',
        ], attributes: [
            'itemForm.performance_form_template_section_id' => __('performance_evaluation::dashboard.fields.section'),
            'itemForm.training_competency_id' => __('performance_evaluation::dashboard.fields.competency'),
            'itemForm.name' => __('performance_evaluation::dashboard.fields.item_name'),
            'itemForm.description' => __('performance_evaluation::dashboard.fields.description'),
            'itemForm.weight_percent' => __('performance_evaluation::dashboard.fields.weight_percent'),
            'itemForm.low_score_threshold' => __('performance_evaluation::dashboard.fields.low_score_threshold'),
            'itemForm.sort_order' => __('performance_evaluation::dashboard.fields.sort_order'),
        ]);

        $payload = [
            'performance_form_template_section_id' => (int) data_get($validated, 'itemForm.performance_form_template_section_id'),
            'training_competency_id' => data_get($validated, 'itemForm.training_competency_id'),
            'name' => trim((string) data_get($validated, 'itemForm.name')),
            'description' => data_get($validated, 'itemForm.description'),
            'weight_percent' => (float) (data_get($validated, 'itemForm.weight_percent') ?? 0),
            'low_score_threshold' => (float) (data_get($validated, 'itemForm.low_score_threshold') ?? 60),
            'requires_comment' => (bool) (data_get($validated, 'itemForm.requires_comment') ?? false),
            'sort_order' => (int) (data_get($validated, 'itemForm.sort_order') ?? 0),
        ];

        if ($this->editingItemId) {
            PerformanceFormTemplateItem::query()->findOrFail($this->editingItemId)->update($payload);
        } else {
            PerformanceFormTemplateItem::query()->create($payload);
        }

        $this->reset('itemForm', 'searchSection', 'searchCompetency');
        $this->itemForm = $this->itemDefaults();
        $this->editingItemId = null;
        $this->resetValidation();
        $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.item_saved'));
    }

    public function editCycle(int $id): void
    {
        $this->authorizePerformanceEvaluationManage();

        $cycle = PerformanceCycle::query()->findOrFail($id);
        $this->editingCycleId = $cycle->id;
        $this->cycleForm = [
            'name' => (string) $cycle->name,
            'cycle_type' => (string) $cycle->cycle_type,
            'period_start' => optional($cycle->period_start)->toDateString(),
            'period_end' => optional($cycle->period_end)->toDateString(),
            'status' => (string) $cycle->status,
            'auto_generate_forms' => (bool) $cycle->auto_generate_forms,
            'description' => (string) ($cycle->description ?? ''),
        ];
        $this->resetValidation();
    }

    public function deleteCycle(int $id): void
    {
        $this->authorizePerformanceEvaluationManage();
        PerformanceCycle::query()->findOrFail($id)->delete();
        if ($this->editingCycleId === $id) {
            $this->cancelCycleEdit();
        }

        $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.cycle_deleted'));
    }

    public function editTemplate(int $id): void
    {
        $this->authorizePerformanceEvaluationManage();

        $template = PerformanceFormTemplate::query()->findOrFail($id);
        $this->editingTemplateId = $template->id;
        $this->templateForm = [
            'name' => (string) $template->name,
            'code' => (string) ($template->code ?? ''),
            'description' => (string) ($template->description ?? ''),
            'is_active' => (bool) $template->is_active,
        ];
        $this->resetValidation();
    }

    public function deleteTemplate(int $id): void
    {
        $this->authorizePerformanceEvaluationManage();
        PerformanceFormTemplate::query()->findOrFail($id)->delete();
        if ($this->editingTemplateId === $id) {
            $this->cancelTemplateEdit();
        }

        $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.template_deleted'));
    }

    public function editSection(int $id): void
    {
        $this->authorizePerformanceEvaluationManage();

        $section = PerformanceFormTemplateSection::query()->findOrFail($id);
        $this->editingSectionId = $section->id;
        $this->sectionForm = [
            'performance_form_template_id' => $section->performance_form_template_id,
            'name' => (string) $section->name,
            'weight_percent' => (float) $section->weight_percent,
            'sort_order' => (int) $section->sort_order,
        ];
        $this->resetValidation();
    }

    public function deleteSection(int $id): void
    {
        $this->authorizePerformanceEvaluationManage();
        PerformanceFormTemplateSection::query()->findOrFail($id)->delete();
        if ($this->editingSectionId === $id) {
            $this->cancelSectionEdit();
        }

        $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.section_deleted'));
    }

    public function editItem(int $id): void
    {
        $this->authorizePerformanceEvaluationManage();

        $item = PerformanceFormTemplateItem::query()->findOrFail($id);
        $this->editingItemId = $item->id;
        $this->itemForm = [
            'performance_form_template_section_id' => $item->performance_form_template_section_id,
            'training_competency_id' => $item->training_competency_id,
            'name' => (string) $item->name,
            'description' => (string) ($item->description ?? ''),
            'weight_percent' => (float) $item->weight_percent,
            'low_score_threshold' => (float) $item->low_score_threshold,
            'requires_comment' => (bool) $item->requires_comment,
            'sort_order' => (int) $item->sort_order,
        ];
        $this->resetValidation();
    }

    public function deleteItem(int $id): void
    {
        $this->authorizePerformanceEvaluationManage();
        PerformanceFormTemplateItem::query()->findOrFail($id)->delete();
        if ($this->editingItemId === $id) {
            $this->cancelItemEdit();
        }

        $this->dispatch('performanceEvaluationSaved', __('performance_evaluation::dashboard.messages.item_deleted'));
    }

    public function cancelCycleEdit(): void
    {
        $this->editingCycleId = null;
        $this->cycleForm = $this->cycleDefaults();
        $this->resetValidation();
    }

    public function cancelTemplateEdit(): void
    {
        $this->editingTemplateId = null;
        $this->templateForm = $this->templateDefaults();
        $this->resetValidation();
    }

    public function cancelSectionEdit(): void
    {
        $this->editingSectionId = null;
        $this->sectionForm = $this->sectionDefaults();
        $this->resetValidation();
    }

    public function cancelItemEdit(): void
    {
        $this->editingItemId = null;
        $this->itemForm = $this->itemDefaults();
        $this->resetValidation();
    }
}
