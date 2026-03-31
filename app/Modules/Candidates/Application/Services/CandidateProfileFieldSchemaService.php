<?php

namespace App\Modules\Candidates\Application\Services;

use App\Enums\AttitudeMilitaryEnum;
use App\Enums\MilitaryStatusEnum;
use App\Enums\ResearchResultEnum;
use Illuminate\Validation\Rule;

class CandidateProfileFieldSchemaService
{
    protected const CORE_FIELDS = [
        'name' => ['type' => 'text', 'rule' => ['required', 'string', 'min:2']],
        'surname' => ['type' => 'text', 'rule' => ['required', 'string', 'min:2']],
        'patronymic' => ['type' => 'text', 'rule' => ['required', 'string', 'min:2']],
        'structure_id' => ['type' => 'select', 'rule' => ['required', 'int', 'exists:structures,id']],
        'birthdate' => ['type' => 'date', 'rule' => ['required', 'date']],
        'gender' => ['type' => 'radio', 'rule' => ['required', 'int']],
        'phone' => ['type' => 'text', 'rule' => ['nullable', 'string', 'max:255']],
        'status_id' => ['type' => 'select', 'rule' => ['required', 'int', 'exists:appeal_statuses,id']],
    ];

    protected const PACK_FIELDS = [
        'private' => [
            [
                ['key' => 'application_date', 'type' => 'date', 'cols' => 1],
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
                ['key' => 'appeal_date', 'type' => 'date', 'cols' => 1],
                ['key' => 'application_date', 'type' => 'date', 'cols' => 1],
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
            $attributes['candidate.'.$key] = __('candidates::common.labels.'.$key);
        }

        foreach ($this->fieldsForPack($pack) as $field) {
            $attributes['candidate.'.$field['key']] = __('candidates::common.labels.'.$field['key']);
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
}
