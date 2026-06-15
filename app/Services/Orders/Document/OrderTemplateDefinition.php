<?php

namespace App\Services\Orders\Document;

/**
 * A render-ready order template: the fixed org chrome plus the per-type body parts
 * (subject, preamble, clauses, basis, signatory) authored by the HR user. Body
 * fields may contain `{{ variable }}` placeholders.
 *
 * This is the in-memory shape the compiler consumes; persisting/loading it from the
 * template tables is wired in a later phase (strangler).
 */
final class OrderTemplateDefinition
{
    /**
     * @param  string[]  $clauses
     * @param  string[]  $signatoryTitleLines
     */
    public function __construct(
        public readonly string $organizationName,
        public readonly string $organizationCity,
        public readonly string $numberSuffix,
        public readonly string $subject,
        public readonly string $preamble,
        public readonly array $clauses,
        public readonly string $basis,
        public readonly array $signatoryTitleLines,
        public readonly string $signatoryName,
    ) {}
}
