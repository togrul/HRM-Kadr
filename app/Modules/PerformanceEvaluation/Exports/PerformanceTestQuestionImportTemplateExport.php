<?php

namespace App\Modules\PerformanceEvaluation\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PerformanceTestQuestionImportTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'bank_code',
            'bank_name',
            'bank_pass_score',
            'bank_duration_minutes',
            'bank_max_attempts',
            'competency_name',
            'question_type',
            'prompt',
            'description',
            'max_score',
            'sort_order',
            'is_active',
            'option_1_label',
            'option_1_correct',
            'option_1_score',
            'option_2_label',
            'option_2_correct',
            'option_2_score',
            'option_3_label',
            'option_3_correct',
            'option_3_score',
            'option_4_label',
            'option_4_correct',
            'option_4_score',
        ];
    }

    public function array(): array
    {
        return [
            [
                'BASIC-001',
                'İlkin imtahan',
                60,
                60,
                1,
                'Analitik düşüncə',
                'multiple_choice',
                'Sual nümunəsi',
                'Variantlardan birini seçin.',
                100,
                1,
                1,
                'Variant A',
                1,
                100,
                'Variant B',
                0,
                0,
                'Variant C',
                0,
                0,
                null,
                null,
                null,
            ],
            [
                'BASIC-001',
                'İlkin imtahan',
                60,
                60,
                1,
                'Analitik düşüncə',
                'open_answer',
                'Açıq sual nümunəsi',
                'Cavabı sərbəst formada yazın.',
                100,
                2,
                1,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
            ],
        ];
    }
}
