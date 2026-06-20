<?php

namespace App\Services\Orders\Document;

use App\Models\OrderWordTemplate;
use App\Models\Personnel;
use App\Services\NumberToWordsService;
use App\Services\Orders\Variables\OrderEmployeeVariableResolver;
use App\Support\Language\AzerbaijaniDateFormatter;

/**
 * Turns a Word template's variable mapping into the concrete token => value map that
 * DocxTemplateRenderer feeds to PhpWord. Automatic variables resolve from the selected
 * personnel (declension-aware, via OrderEmployeeVariableResolver) and the per-order
 * system context; manual variables take the author's form input, resolving
 * structure/position ids to their display names.
 */
class DocxVariableResolver
{
    public function __construct(
        private readonly OrderEmployeeVariableResolver $employee,
        private readonly OrderLookupFieldRegistry $lookups,
        private readonly AzerbaijaniDateFormatter $dates,
        private readonly NumberToWordsService $numbers,
    ) {}

    /**
     * @param  array<string,mixed>  $manualInputs  field.key => value entered by the author
     * @param  array<string,string>  $system  system.* key => value for this order
     * @return array<string,string>  token => value
     */
    public function resolve(OrderWordTemplate $template, ?Personnel $personnel, array $manualInputs, array $system): array
    {
        // The pool of automatically-resolvable values: employee.* (with Azerbaijani
        // case variants) plus the order's system.* context.
        $auto = array_merge($this->employee->resolve($personnel), $system);

        $values = [];
        foreach ($template->variables ?? [] as $variable) {
            $token = $variable['token'] ?? null;
            if (! $token) {
                continue;
            }

            $values[$token] = ($variable['source'] ?? 'manual') === 'auto'
                ? (string) ($auto[$variable['auto_key'] ?? ''] ?? '')
                : $this->manualValue($variable['field'] ?? null, $manualInputs);
        }

        return $values;
    }

    /**
     * @param  array{key:string,type:string}|null  $field
     * @param  array<string,mixed>  $manualInputs
     */
    private function manualValue(?array $field, array $manualInputs): string
    {
        if (! is_array($field) || empty($field['key'])) {
            return '';
        }

        $raw = $manualInputs[$field['key']] ?? '';
        if ($raw === '' || $raw === null) {
            return '';
        }

        $type = $field['type'] ?? 'text';

        // Labour-year span: a start date becomes "26.11.2025-25.11.2026-cı il".
        if ($type === 'work_year') {
            $start = $this->dates->parse((string) $raw);

            return $start ? $this->dates->workYearSpan($start) : (string) $raw;
        }

        // A number written out in words ("əlli dörd").
        if ($type === 'number_words') {
            return is_numeric($raw) ? $this->numbers->convert((int) $raw) : (string) $raw;
        }

        // A calendar date: render the Azerbaijani long form "19.05.2026-cı il" (the
        // HTML date input submits ISO; documents read in the local long form). Falls
        // back to the raw value when it cannot be parsed.
        if ($type === 'date') {
            $parsed = $this->dates->parse((string) $raw);

            return $parsed ? $this->dates->longDate($parsed) : (string) $raw;
        }

        // List-bound fields submit a record id; write its name into the document.
        return $this->lookups->isLookup($type)
            ? $this->lookups->resolve($type, $raw)
            : (string) $raw;
    }
}
