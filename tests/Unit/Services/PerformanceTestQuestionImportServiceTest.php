<?php

namespace Tests\Unit\Services;

use App\Models\TrainingCompetency;
use App\Models\TrainingCompetencyGroup;
use App\Modules\PerformanceEvaluation\Application\Services\PerformanceTestQuestionImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceTestQuestionImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_bank_questions_and_options_from_import_rows(): void
    {
        $group = TrainingCompetencyGroup::query()->create([
            'name' => 'Core',
            'slug' => 'core',
        ]);

        TrainingCompetency::query()->create([
            'training_competency_group_id' => $group->id,
            'name' => 'Analitik düşüncə',
            'slug' => 'analitik-dusunce',
            'is_active' => true,
        ]);

        $result = app(PerformanceTestQuestionImportService::class)->import([
            [
                'bank_code' => 'IMP-001',
                'bank_name' => 'Import bank',
                'bank_pass_score' => 60,
                'bank_duration_minutes' => 45,
                'bank_max_attempts' => 2,
                'competency_name' => 'Analitik düşüncə',
                'question_type' => 'multiple_choice',
                'prompt' => 'İlk sual',
                'description' => 'Açıqlama',
                'max_score' => 100,
                'sort_order' => 1,
                'is_active' => 1,
                'option_1_label' => 'Variant A',
                'option_1_correct' => 1,
                'option_1_score' => 100,
                'option_2_label' => 'Variant B',
                'option_2_correct' => 0,
                'option_2_score' => 0,
            ],
            [
                'bank_code' => 'IMP-001',
                'bank_name' => 'Import bank',
                'competency_name' => 'Analitik düşüncə',
                'question_type' => 'open_answer',
                'prompt' => 'İkinci sual',
                'description' => 'Mətn cavabı',
                'max_score' => 50,
                'sort_order' => 2,
                'is_active' => 1,
            ],
        ]);

        $this->assertSame([
            'banks' => 1,
            'questions' => 2,
            'updated_questions' => 0,
        ], $result);

        $this->assertDatabaseHas('performance_test_banks', [
            'code' => 'IMP-001',
            'name' => 'Import bank',
        ]);
        $this->assertDatabaseHas('performance_test_questions', [
            'prompt' => 'İlk sual',
            'question_type' => 'multiple_choice',
        ]);
        $this->assertDatabaseHas('performance_test_question_options', [
            'label' => 'Variant A',
            'is_correct' => true,
        ]);
    }
}
