<?php

namespace App\Modules\PerformanceEvaluation\Application\Services;

use App\Models\PerformanceTestBank;
use App\Models\PerformanceTestQuestion;
use App\Models\TrainingCompetency;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PerformanceTestQuestionImportService
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{banks:int,questions:int,updated_questions:int}
     */
    public function import(array $rows, ?int $targetBankId = null): array
    {
        $createdBanks = 0;
        $createdQuestions = 0;
        $updatedQuestions = 0;

        foreach (collect($rows)->filter(fn ($row) => filled(data_get($row, 'prompt'))) as $index => $row) {
            $bank = $this->resolveBank($row, $targetBankId, $createdBanks);
            $competencyId = $this->resolveCompetencyId($row, $index + 2);
            $questionType = (string) data_get($row, 'question_type', 'multiple_choice');

            if (! in_array($questionType, ['multiple_choice', 'open_answer', 'case_study', 'behavioral'], true)) {
                throw ValidationException::withMessages([
                    'testQuestionImportFile' => __('performance_evaluation::dashboard.validation.invalid_import_question_type', ['row' => $index + 2]),
                ]);
            }

            $question = PerformanceTestQuestion::query()->firstOrNew([
                'performance_test_bank_id' => $bank->id,
                'prompt' => trim((string) data_get($row, 'prompt')),
            ]);

            $wasExisting = $question->exists;

            $question->fill([
                'training_competency_id' => $competencyId,
                'question_type' => $questionType,
                'description' => data_get($row, 'description'),
                'max_score' => (float) data_get($row, 'max_score', 100),
                'sort_order' => (int) data_get($row, 'sort_order', 0),
                'is_active' => filter_var(data_get($row, 'is_active', true), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? true,
            ]);
            $question->save();

            app(PerformanceSkillMeasurementService::class)->syncQuestionOptions(
                $question,
                $this->buildOptionsText($row)
            );

            if ($wasExisting) {
                $updatedQuestions++;
            } else {
                $createdQuestions++;
            }
        }

        return [
            'banks' => $createdBanks,
            'questions' => $createdQuestions,
            'updated_questions' => $updatedQuestions,
        ];
    }

    /**
     * @param  array<string,mixed>  $row
     */
    private function resolveBank(array $row, ?int $targetBankId, int &$createdBanks): PerformanceTestBank
    {
        if ($targetBankId) {
            return PerformanceTestBank::query()->findOrFail($targetBankId);
        }

        $bankCode = trim((string) data_get($row, 'bank_code', ''));
        $bankName = trim((string) data_get($row, 'bank_name', ''));

        if ($bankCode === '' && $bankName === '') {
            throw ValidationException::withMessages([
                'testQuestionImportFile' => __('performance_evaluation::dashboard.validation.import_bank_required'),
            ]);
        }

        $bank = PerformanceTestBank::query()
            ->when($bankCode !== '', fn ($query) => $query->where('code', $bankCode))
            ->when($bankCode === '' && $bankName !== '', fn ($query) => $query->where('name', $bankName))
            ->first();

        if ($bank) {
            return $bank;
        }

        $createdBanks++;

        return PerformanceTestBank::query()->create([
            'name' => $bankName !== '' ? $bankName : $bankCode,
            'code' => $bankCode !== '' ? $bankCode : null,
            'pass_score' => (float) data_get($row, 'bank_pass_score', 60),
            'duration_minutes' => (int) data_get($row, 'bank_duration_minutes', 30),
            'max_attempts' => (int) data_get($row, 'bank_max_attempts', 1),
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string,mixed>  $row
     */
    private function resolveCompetencyId(array $row, int $displayRow): ?int
    {
        $name = trim((string) data_get($row, 'competency_name', ''));
        if ($name === '') {
            return null;
        }

        $competencyId = TrainingCompetency::query()
            ->where('name', $name)
            ->value('id');

        if ($competencyId === null) {
            throw ValidationException::withMessages([
                'testQuestionImportFile' => __('performance_evaluation::dashboard.validation.import_competency_not_found', [
                    'row' => $displayRow,
                    'competency' => $name,
                ]),
            ]);
        }

        return (int) $competencyId;
    }

    /**
     * @param  array<string,mixed>  $row
     */
    private function buildOptionsText(array $row): string
    {
        $lines = [];

        foreach (range(1, 4) as $index) {
            $label = trim((string) data_get($row, "option_{$index}_label", ''));
            if ($label === '') {
                continue;
            }

            $correct = filter_var(data_get($row, "option_{$index}_correct", false), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;
            $score = data_get($row, "option_{$index}_score");

            $lines[] = implode('|', [
                $label,
                $correct ? '1' : '0',
                $score === null || $score === '' ? '' : (string) $score,
            ]);
        }

        return implode(PHP_EOL, $lines);
    }
}
