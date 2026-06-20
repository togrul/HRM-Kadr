<?php

namespace App\Modules\PerformanceEvaluation\Livewire\Concerns;

trait HasPerformanceEvaluationFormDefaults
{
    protected function cycleDefaults(): array
    {
        return [
            'name' => '',
            'cycle_type' => 'annual',
            'period_start' => now()->startOfYear()->toDateString(),
            'period_end' => now()->endOfYear()->toDateString(),
            'status' => 'draft',
            'auto_generate_forms' => true,
            'description' => '',
        ];
    }

    protected function templateDefaults(): array
    {
        return [
            'name' => '',
            'code' => '',
            'description' => '',
            'is_active' => true,
        ];
    }

    protected function sectionDefaults(): array
    {
        return [
            'performance_form_template_id' => null,
            'name' => '',
            'weight_percent' => 0,
            'sort_order' => 0,
        ];
    }

    protected function itemDefaults(): array
    {
        return [
            'performance_form_template_section_id' => null,
            'training_competency_id' => null,
            'name' => '',
            'description' => '',
            'weight_percent' => 0,
            'low_score_threshold' => 60,
            'requires_comment' => false,
            'sort_order' => 0,
        ];
    }

    protected function evaluationDefaults(): array
    {
        return [
            'performance_cycle_id' => null,
            'performance_form_template_id' => null,
            'personnel_id' => null,
            'manager_id' => null,
            'hr_reviewer_id' => null,
        ];
    }

    protected function scoreDefaults(): array
    {
        return [
            'performance_form_id' => null,
            'performance_form_template_item_id' => null,
            'evaluator_type' => 'manager',
            'score' => null,
            'comment' => '',
        ];
    }

    protected function bankDefaults(): array
    {
        return [
            'name' => '',
            'code' => '',
            'description' => '',
            'pass_score' => 60,
            'duration_minutes' => 30,
            'max_attempts' => 1,
            'is_active' => true,
        ];
    }

    protected function questionDefaults(): array
    {
        return [
            'performance_test_bank_id' => null,
            'training_competency_id' => null,
            'question_type' => 'multiple_choice',
            'prompt' => '',
            'description' => '',
            'max_score' => 100,
            'sort_order' => 0,
            'is_active' => true,
            'options_text' => '',
        ];
    }

    protected function sessionDefaults(): array
    {
        return [
            'performance_cycle_id' => null,
            'performance_test_bank_id' => null,
            'personnel_id' => null,
            'reviewer_id' => null,
            'scheduled_at' => now()->toDateString(),
            'available_until' => now()->addWeek()->toDateString(),
            'pass_score' => null,
            'duration_minutes' => null,
            'max_attempts' => null,
            'status' => 'assigned',
        ];
    }

    protected function attemptAnswerDefaults(): array
    {
        return [
            'performance_test_session_id' => null,
            'performance_test_question_id' => null,
            'attempt_no' => 1,
            'selected_option_id' => null,
            'answer_text' => '',
        ];
    }

    protected function attemptSubmitDefaults(): array
    {
        return [
            'performance_test_attempt_id' => null,
        ];
    }

    protected function reviewDefaults(): array
    {
        return [
            'performance_test_attempt_answer_id' => null,
            'score' => null,
            'feedback' => '',
        ];
    }

    protected function testQuestionImportDefaults(): array
    {
        return [
            'performance_test_bank_id' => null,
        ];
    }
}
