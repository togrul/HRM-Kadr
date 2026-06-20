<?php

namespace App\Modules\PerformanceEvaluation\Livewire\Concerns;

use App\Models\PerformanceFormScore;
use App\Models\PerformanceFormTemplateItem;

trait InteractsWithEvaluatorWorkspaceScoreForm
{
    public function updatedScoreFormPerformanceFormId($value): void
    {
        $this->hydrateAssignedScoreForm((int) $value, true);
    }

    public function updatedScoreFormPerformanceFormTemplateItemId($value): void
    {
        $itemId = (int) $value;

        if ($itemId <= 0) {
            $this->scoreForm['score'] = null;
            $this->scoreForm['comment'] = '';

            return;
        }

        $this->hydrateExistingAssignedScore($itemId);
    }

    public function startScoreForm(int $formId): void
    {
        abort_if($this->assignedFormContext($formId) === null, 403);

        $this->hydrateAssignedScoreForm($formId, true);
    }

    protected function hydrateAssignedScoreForm(int $formId, bool $prefillFirstItem): void
    {
        if ($formId <= 0) {
            $this->scoreForm = [
                'performance_form_id' => null,
                'performance_form_template_item_id' => null,
                'evaluator_type' => 'manager',
                'score' => null,
                'comment' => '',
            ];

            return;
        }

        $formContext = $this->assignedFormContext($formId);
        abort_if($formContext === null, 403);

        $evaluatorType = (string) data_get($formContext, 'evaluator_type', 'manager');
        $templateId = (int) data_get($formContext, 'template_id');

        $this->scoreForm['performance_form_id'] = $formId;
        $this->scoreForm['evaluator_type'] = $evaluatorType;

        $itemId = (int) data_get($this->scoreForm, 'performance_form_template_item_id');

        if ($prefillFirstItem || $itemId <= 0 || ! $this->formBelongsToTemplate($itemId, $templateId)) {
            $itemId = (int) data_get($this->formItemOptions(), '0.id', 0);
            $this->scoreForm['performance_form_template_item_id'] = $itemId > 0 ? $itemId : null;
        }

        if ($itemId > 0) {
            $this->hydrateExistingAssignedScore($itemId);

            return;
        }

        $this->scoreForm['score'] = null;
        $this->scoreForm['comment'] = '';
    }

    protected function hydrateExistingAssignedScore(int $itemId): void
    {
        $formId = (int) data_get($this->scoreForm, 'performance_form_id');
        $evaluatorType = (string) data_get($this->scoreForm, 'evaluator_type', 'manager');
        $cacheKey = sprintf('evaluatorWorkspace.existingScore.%d.%d.%s', $formId, $itemId, $evaluatorType);

        $existingScore = $this->rememberRuntime($cacheKey, function () use ($evaluatorType, $formId, $itemId) {
            return PerformanceFormScore::query()
                ->where('performance_form_id', $formId)
                ->where('performance_form_template_item_id', $itemId)
                ->where('evaluator_type', $evaluatorType)
                ->first();
        });

        $this->scoreForm['score'] = $existingScore?->score;
        $this->scoreForm['comment'] = (string) ($existingScore?->comment ?? '');
    }

    protected function formBelongsToTemplate(int $itemId, int $templateId): bool
    {
        return $this->rememberRuntime(sprintf('evaluatorWorkspace.templateItemMembership.%d.%d', $templateId, $itemId), function () use ($itemId, $templateId) {
            return PerformanceFormTemplateItem::query()
                ->join('performance_form_template_sections', 'performance_form_template_sections.id', '=', 'performance_form_template_items.performance_form_template_section_id')
                ->where('performance_form_template_items.id', $itemId)
                ->where('performance_form_template_sections.performance_form_template_id', $templateId)
                ->exists();
        });
    }
}
