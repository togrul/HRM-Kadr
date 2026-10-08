<?php

namespace App\Modules\Candidates\Application\Services;

use App\Enums\AttitudeMilitaryEnum;
use App\Enums\MilitaryStatusEnum;
use App\Enums\ResearchResultEnum;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Throwable;

class CandidateProfileFieldSchemaService
{
    /** Youngest and oldest age a candidate may have (Labor Code: work from 15). */
    public const MIN_AGE = 15;

    public const MAX_AGE = 100;

    /** Earliest plausible recruitment-process date (appeal/application/requisition). */
    public const EARLIEST_PROCESS_DATE = '2000-01-01';

    /** A phone is 9–15 digits, optionally led by "+", once separators are stripped. */
    public const PHONE_PATTERN = '/^\+?\d{9,15}$/';

    /**
     * Recruitment-process dates in their chronological order: the job requisition
     * (Tələbnamə) comes first, then the candidate's appeal/first contact (Müraciət), then
     * the formal written application (Ərizə). Each date present must not precede the
     * one before it.
     */
    public const PROCESS_DATE_ORDER = ['requisition_date', 'appeal_date', 'application_date'];

    protected const CORE_FIELDS = [
        'name' => ['type' => 'text', 'rule' => ['required', 'string', 'min:2']],
        'surname' => ['type' => 'text', 'rule' => ['required', 'string', 'min:2']],
        'patronymic' => ['type' => 'text', 'rule' => ['required', 'string', 'min:2']],
        'structure_id' => ['type' => 'select', 'rule' => ['required', 'int', 'exists:structures,id']],
        'birthdate' => ['type' => 'date', 'rule' => ['required', 'date']],
        'gender' => ['type' => 'radio', 'rule' => ['required', 'int']],
        'phone' => ['type' => 'text', 'rule' => ['nullable', 'string', 'max:32']],
        'status_id' => ['type' => 'select', 'rule' => ['required', 'int', 'exists:appeal_statuses,id']],
        'application_date' => ['type' => 'date', 'rule' => ['nullable', 'date']],
        'appeal_date' => ['type' => 'date', 'rule' => ['nullable', 'date']],
    ];

    protected const PACK_FIELDS = [
        'private' => [
            [
                ['key' => 'requisition_date', 'type' => 'date', 'cols' => 1],
                ['key' => 'presented_by', 'type' => 'text', 'cols' => 1],
            ],
            [
                ['key' => 'initial_documents', 'type' => 'text', 'cols' => 1],
                ['key' => 'documents_completeness', 'type' => 'text', 'cols' => 1],
                ['key' => 'characteristics', 'type' => 'text', 'cols' => 1],
            ],
            [
                ['key' => 'note', 'type' => 'textarea', 'cols' => 2],
            ],
        ],
        'public' => [
            [
                ['key' => 'requisition_date', 'type' => 'date', 'cols' => 1],
            ],
            [
                ['key' => 'initial_documents', 'type' => 'text', 'cols' => 1],
                ['key' => 'documents_completeness', 'type' => 'text', 'cols' => 1],
                ['key' => 'presented_by', 'type' => 'text', 'cols' => 1],
            ],
            [
                ['key' => 'characteristics', 'type' => 'text', 'cols' => 1],
                ['key' => 'note', 'type' => 'textarea', 'cols' => 1],
            ],
        ],
        'military' => [
            [
                ['key' => 'height', 'type' => 'number', 'required' => true, 'cols' => 1],
                ['key' => 'military_service', 'type' => 'text', 'cols' => 1],
                ['key' => 'knowledge_test', 'type' => 'number', 'required' => true, 'cols' => 1],
                ['key' => 'physical_fitness_exam', 'type' => 'number', 'required' => true, 'cols' => 1],
            ],
            [
                ['key' => 'research_date', 'type' => 'date', 'cols' => 1],
                ['key' => 'research_result', 'type' => 'radio', 'options' => 'research_result', 'cols' => 2],
                ['key' => 'examination_date', 'type' => 'date', 'cols' => 1],
            ],
            [
                ['key' => 'requisition_date', 'type' => 'date', 'cols' => 1],
                ['key' => 'initial_documents', 'type' => 'text', 'cols' => 1],
                ['key' => 'documents_completeness', 'type' => 'text', 'cols' => 1],
                ['key' => 'hhk_date', 'type' => 'date', 'cols' => 1],
            ],
            [
                ['key' => 'hhk_result', 'type' => 'radio', 'options' => 'military_status', 'cols' => 1],
                ['key' => 'attitude_to_military', 'type' => 'radio', 'options' => 'attitude_military', 'required' => true, 'cols' => 1],
                ['key' => 'useless_info', 'type' => 'text', 'cols' => 1, 'show_when' => ['field' => 'hhk_result', 'equals' => 'yararsız']],
            ],
            [
                ['key' => 'characteristics', 'type' => 'text', 'cols' => 1],
                ['key' => 'discrediting_information', 'type' => 'textarea', 'cols' => 1],
                ['key' => 'presented_by', 'type' => 'textarea', 'cols' => 1],
            ],
            [
                ['key' => 'note', 'type' => 'textarea', 'cols' => 1],
            ],
        ],
    ];

    public function coreRules(): array
    {
        return collect(self::CORE_FIELDS)
            ->mapWithKeys(fn (array $field, string $key) => ['candidate.'.$key => $field['rule']])
            ->all();
    }

    /**
     * Every rule for the add/edit candidate form: the core and pack field rules plus the
     * plausibility checks — age, phone format and the recruitment date order.
     *
     * @param  array<string, mixed>  $candidate  the form state (for cross-field checks)
     * @return array<string, array<int, mixed>>
     */
    public function formRules(string $pack, array $candidate): array
    {
        $rules = array_merge($this->coreRules(), $this->packRules($pack));

        $rules['candidate.birthdate'][] = $this->birthdateRule();
        $rules['candidate.phone'][] = $this->phoneRule();

        foreach (self::PROCESS_DATE_ORDER as $index => $key) {
            $ruleKey = 'candidate.'.$key;

            if (! array_key_exists($ruleKey, $rules)) {
                continue;
            }

            $rules[$ruleKey][] = $this->processDateRule($key, $this->previousProcessDate($candidate, $index));
        }

        return $rules;
    }

    /** Whether a core form field must be filled (drives the `*` marker on its label). */
    public function isRequired(string $key): bool
    {
        $rule = self::CORE_FIELDS[$key]['rule'] ?? [];

        return in_array('required', $rule, true);
    }

    /**
     * The phone as it is stored: separators (spaces, dashes, dots, brackets) stripped,
     * a leading "+" kept. Blank becomes null.
     */
    public function normalizePhone(mixed $phone): ?string
    {
        if ($phone === null || ! is_scalar($phone)) {
            return null;
        }

        $phone = trim((string) $phone);

        if ($phone === '') {
            return null;
        }

        $normalized = preg_replace('/[\s\-\.\(\)]/u', '', $phone) ?? $phone;

        return $normalized;
    }

    public function packRules(string $pack): array
    {
        $rules = [];

        foreach ($this->fieldsForPack($pack) as $field) {
            $rules['candidate.'.$field['key']] = $this->ruleForField($field);
        }

        return $rules;
    }

    public function allCandidateAttributeKeys(): array
    {
        return collect(self::PACK_FIELDS)
            ->flatMap(fn (array $rows) => collect($rows)->flatten(1)->pluck('key'))
            ->unique()
            ->values()
            ->all();
    }

    public function rowsForPack(string $pack): array
    {
        return self::PACK_FIELDS[strtolower($pack)] ?? self::PACK_FIELDS['military'];
    }

    public function fieldsForPack(string $pack): array
    {
        return collect($this->rowsForPack($pack))->flatten(1)->values()->all();
    }

    public function validationAttributeLabels(string $pack): array
    {
        $attributes = [];

        foreach (array_keys(self::CORE_FIELDS) as $key) {
            $attributes['candidate.'.$key] = $this->labelForField($key);
        }

        foreach ($this->fieldsForPack($pack) as $field) {
            $attributes['candidate.'.$field['key']] = $this->labelForField($field['key']);
        }

        return $attributes;
    }

    public function optionsForField(array $field): array
    {
        return match ($field['options'] ?? null) {
            'research_result' => ResearchResultEnum::values(),
            'military_status' => MilitaryStatusEnum::values(),
            'attitude_military' => AttitudeMilitaryEnum::values(),
            default => $field['options'] ?? [],
        };
    }

    protected function ruleForField(array $field): array
    {
        $required = (bool) ($field['required'] ?? false);
        $type = $field['type'] ?? 'text';

        return match ($type) {
            'number' => [$required ? 'required' : 'nullable', 'numeric'],
            'date' => [$required ? 'required' : 'nullable', 'date'],
            'radio' => array_values(array_filter([
                $required ? 'required' : 'nullable',
                ($field['options'] ?? null) ? Rule::in($this->optionsForField($field)) : null,
            ])),
            default => [$required ? 'required' : 'nullable', 'string'],
        };
    }

    private function birthdateRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $birthdate = $this->parseDate($value);

            if (! $birthdate instanceof Carbon) {
                return;
            }

            if ($birthdate->greaterThanOrEqualTo(today())) {
                $fail(__('candidates::common.validation.birthdate_past'));

                return;
            }

            $tooYoung = $birthdate->greaterThan(today()->subYears(self::MIN_AGE));
            $tooOld = $birthdate->lessThanOrEqualTo(today()->subYears(self::MAX_AGE + 1));

            if ($tooYoung || $tooOld) {
                $fail(__('candidates::common.validation.age_range', ['min' => self::MIN_AGE, 'max' => self::MAX_AGE]));
            }
        };
    }

    private function phoneRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $phone = $this->normalizePhone($value);

            if ($phone !== null && preg_match(self::PHONE_PATTERN, $phone) !== 1) {
                $fail(__('candidates::common.validation.phone_format'));
            }
        };
    }

    /**
     * Not before the earliest plausible date, not after today, and not before the
     * previous date in PROCESS_DATE_ORDER (when that one is filled).
     *
     * @param  array{key:string,date:Carbon}|null  $previous
     */
    private function processDateRule(string $key, ?array $previous): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($key, $previous): void {
            $date = $this->parseDate($value);

            if (! $date instanceof Carbon) {
                return;
            }

            $label = $this->labelForField($key);

            if ($date->greaterThan(today())) {
                $fail(__('candidates::common.validation.date_not_future', ['attribute' => $label]));

                return;
            }

            $earliest = Carbon::parse(self::EARLIEST_PROCESS_DATE);

            if ($date->lessThan($earliest)) {
                $fail(__('candidates::common.validation.date_too_early', [
                    'attribute' => $label,
                    'date' => $earliest->format('d.m.Y'),
                ]));

                return;
            }

            if ($previous !== null && $date->lessThan($previous['date'])) {
                $fail(__('candidates::common.validation.date_order', [
                    'attribute' => $label,
                    'other' => mb_strtolower($this->labelForField($previous['key'])),
                ]));
            }
        };
    }

    /**
     * The nearest filled, parseable date before PROCESS_DATE_ORDER[$index].
     *
     * @param  array<string, mixed>  $candidate
     * @return array{key:string,date:Carbon}|null
     */
    private function previousProcessDate(array $candidate, int $index): ?array
    {
        for ($i = $index - 1; $i >= 0; $i--) {
            $key = self::PROCESS_DATE_ORDER[$i];
            $date = $this->parseDate($candidate[$key] ?? null);

            if ($date instanceof Carbon) {
                return ['key' => $key, 'date' => $date];
            }
        }

        return null;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '' || ! is_scalar($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }

    private function labelForField(string $key): string
    {
        $translationKey = 'candidates::common.labels.'.$key;
        $label = __($translationKey);

        if ($label !== $translationKey) {
            return $label;
        }

        if (str_ends_with($key, '_id')) {
            $fallbackKey = substr($key, 0, -3);
            $fallbackTranslationKey = 'candidates::common.labels.'.$fallbackKey;
            $fallbackLabel = __($fallbackTranslationKey);

            if ($fallbackLabel !== $fallbackTranslationKey) {
                return $fallbackLabel;
            }
        }

        return $label;
    }
}
