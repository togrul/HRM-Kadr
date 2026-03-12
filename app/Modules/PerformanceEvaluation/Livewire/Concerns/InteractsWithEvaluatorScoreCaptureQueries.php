<?php

namespace App\Modules\PerformanceEvaluation\Livewire\Concerns;

use App\Models\PerformanceForm;
use App\Models\PerformanceFormTemplateItem;

trait InteractsWithEvaluatorScoreCaptureQueries
{
    public function getFormOptionsProperty(): array
    {
        return array_map(
            static fn (array $form): array => [
                'id' => (int) data_get($form, 'id'),
                'label' => (string) data_get($form, 'label', '—'),
            ],
            $this->formCatalog
        );
    }

    public function formItemOptions(): array
    {
        $formId = (int) data_get($this->scoreForm, 'performance_form_id');
        if ($formId <= 0) {
            return [];
        }

        $templateId = $this->templateIdForForm($formId);
        if (! $templateId) {
            return [];
        }

        return $this->rememberRuntime('evaluatorScoreCapture.formItemOptions.'.$templateId, function () use ($templateId) {
            return PerformanceFormTemplateItem::query()
                ->join('performance_form_template_sections', 'performance_form_template_sections.id', '=', 'performance_form_template_items.performance_form_template_section_id')
                ->where('performance_form_template_sections.performance_form_template_id', $templateId)
                ->orderBy('performance_form_template_items.sort_order')
                ->orderBy('performance_form_template_items.name')
                ->get([
                    'performance_form_template_items.id',
                    'performance_form_template_items.name as label',
                ])
                ->map(fn ($row) => ['id' => $row->id, 'label' => $row->label])
                ->all();
        });
    }

    protected function templateIdForForm(int $formId): ?int
    {
        return (int) data_get($this->assignedFormContext($formId), 'template_id') ?: null;
    }

    protected function assignedFormContext(int $formId): ?array
    {
        return $this->rememberRuntime('evaluatorScoreCapture.formContext.'.$formId, function () use ($formId) {
            $catalogForm = collect($this->formCatalog)->firstWhere('id', $formId);
            if (is_array($catalogForm)) {
                return $catalogForm;
            }

            $userId = (int) auth()->id();
            $form = PerformanceForm::query()
                ->whereKey($formId)
                ->where(function ($query) use ($userId): void {
                    $query->where('manager_id', $userId)
                        ->orWhere('hr_reviewer_id', $userId);
                })
                ->first();

            if (! $form) {
                return null;
            }

            return [
                'id' => (int) $form->id,
                'label' => (string) $form->id,
                'template_id' => (int) $form->performance_form_template_id,
                'evaluator_type' => (int) $form->manager_id === $userId ? 'manager' : 'hr',
            ];
        });
    }
}
