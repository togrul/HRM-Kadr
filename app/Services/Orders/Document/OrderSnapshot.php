<?php

namespace App\Services\Orders\Document;

/**
 * The frozen, immutable content of an issued order: the final HTML (exactly what the
 * HR user approved, edits included) and the path to the generated .docx. Persisting
 * this guarantees a re-print reproduces the issued document even if the template
 * later changes.
 */
final class OrderSnapshot
{
    public function __construct(
        public readonly string $html,
        public readonly string $docxPath,
    ) {}
}
