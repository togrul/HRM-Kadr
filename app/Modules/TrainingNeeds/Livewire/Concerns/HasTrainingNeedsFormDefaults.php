<?php

namespace App\Modules\TrainingNeeds\Livewire\Concerns;

trait HasTrainingNeedsFormDefaults
{
    protected function groupDefaults(): array
    {
        return [
            'name' => '',
            'description' => '',
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    protected function levelDefaults(): array
    {
        return [
            'name' => '',
            'score' => null,
            'description' => '',
            'sort_order' => 0,
            'is_default' => false,
        ];
    }

    protected function competencyDefaults(): array
    {
        return [
            'training_competency_group_id' => null,
            'name' => '',
            'description' => '',
            'is_mandatory' => false,
            'is_active' => true,
        ];
    }

    protected function programDefaults(): array
    {
        return [
            'title' => '',
            'code' => '',
            'delivery_type' => 'internal',
            'duration_hours' => null,
            'description' => '',
            'is_active' => true,
        ];
    }

    protected function programMapDefaults(): array
    {
        return [
            'training_program_id' => null,
            'training_competency_id' => null,
            'target_level_id' => null,
        ];
    }

    protected function requirementDefaults(): array
    {
        return [
            'position_id' => null,
            'training_competency_id' => null,
            'required_level_id' => null,
            'priority' => 'medium',
            'is_mandatory' => true,
        ];
    }

    protected function profileDefaults(): array
    {
        return [
            'personnel_id' => null,
            'training_competency_id' => null,
            'current_level_id' => null,
            'source' => 'manual',
            'last_assessed_at' => now()->format('Y-m-d'),
        ];
    }

    protected function needDefaults(): array
    {
        return [
            'personnel_id' => null,
            'training_competency_id' => null,
            'recommended_program_id' => null,
            'target_level_id' => null,
            'priority' => 'medium',
            'source' => 'manual',
            'status' => 'draft',
            'reason' => '',
            'plan_note' => '',
            'target_completion_date' => null,
        ];
    }

    protected function planDefaults(): array
    {
        return [
            'title' => now()->year.' '.__('training_needs::dashboard.labels.default_plan_title'),
            'plan_year' => now()->year,
            'plan_quarter' => null,
            'status' => 'draft',
            'notes' => '',
            'auto_generate' => true,
        ];
    }

    protected function planItemReviewDefaults(): array
    {
        return [
            'participant_count' => 1,
            'estimated_budget' => null,
            'priority' => 'medium',
            'review_note' => '',
        ];
    }

    protected function sessionDefaults(): array
    {
        return [
            'training_annual_plan_id' => null,
            'training_program_id' => null,
            'title' => '',
            'scheduled_start_at' => now()->addWeek()->format('Y-m-d\TH:i'),
            'scheduled_end_at' => now()->addWeek()->addHours(2)->format('Y-m-d\TH:i'),
            'location' => '',
            'trainer_name' => '',
            'capacity' => 20,
            'planned_budget' => null,
            'auto_fill_participants' => true,
            'status' => 'scheduled',
            'notes' => '',
        ];
    }

    protected function participantDefaults(): array
    {
        return [
            'training_session_id' => null,
            'personnel_id' => null,
            'training_need_item_id' => null,
            'attendance_status' => 'confirmed',
        ];
    }

    protected function feedbackFormDefaults(): array
    {
        return [
            'training_session_id' => null,
            'title' => '',
            'status' => 'draft',
            'default_question_type' => 'rating',
            'questions_text' => '',
        ];
    }

    protected function feedbackResponseDefaults(): array
    {
        return [
            'training_feedback_form_id' => null,
            'personnel_id' => null,
            'overall_score' => null,
            'comments' => '',
            'answers_text' => '',
        ];
    }

    protected function deliveryDocumentDefaults(): array
    {
        return [
            'training_delivery_record_id' => null,
            'certificate_file' => null,
        ];
    }
}
