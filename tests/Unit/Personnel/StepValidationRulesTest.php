<?php

namespace Tests\Unit\Personnel;

use App\Modules\Personnel\Support\Traits\Personnel\HandlesPersonnelStepValidation;
use Illuminate\Support\Arr;
use Tests\TestCase;

/**
 * Bir addımda iki və daha çox siyahı bölməsi doludursa (məs. xidməti vəsiqə və pasport),
 * onların qaralama qaydaları çıxarılır. Qaydalar arasında massiv qaydası (valid_date)
 * olanda əvvəlki array_intersect_assoc "Array to string conversion" atırdı və addım
 * keçidi 500 ilə donurdu.
 */
class StepValidationRulesTest extends TestCase
{
    public function test_two_filled_list_sections_drop_both_draft_rule_sets_without_error(): void
    {
        $step = new class
        {
            use HandlesPersonnelStepValidation;

            public int $step = 2;

            public function documentPayload(): array
            {
                return [
                    'document' => [],
                    'service_cards' => ['list' => [['card_number' => 'A1']]],
                    'passports' => ['list' => [['passport_number' => 'P1']]],
                ];
            }

            public function validationRules(): array
            {
                return [2 => [
                    'documentForm.document.pin' => 'required',
                    'documentForm.serviceCards.card_number' => 'required',
                    'documentForm.serviceCards.given_date' => ['required', 'date', 'before_or_equal:today'],
                    'documentForm.passports.passport_number' => 'required',
                    'documentForm.passports.given_date' => ['required', 'date'],
                    'documentForm.note' => ['nullable', 'string'],
                ]];
            }

            public function exceptArray(string $arrayKey, int $step): array
            {
                $rules = $this->validationRules()[$step];

                return Arr::except($rules, array_values(array_filter(
                    array_keys($rules),
                    fn (string $key): bool => str_starts_with($key, $arrayKey),
                )));
            }

            protected function payloadHasValues(mixed $payload): bool
            {
                return array_filter((array) $payload) !== [];
            }

            public function rules(): array
            {
                return $this->getValidationRulesForStep(2);
            }
        };

        // Boş şəxsiyyət vəsiqəsi və dolu iki siyahı: üçünün də qaralama qaydaları düşür.
        $this->assertSame(['documentForm.note' => ['nullable', 'string']], $step->rules());
    }
}
