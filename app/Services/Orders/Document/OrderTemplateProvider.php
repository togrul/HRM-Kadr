<?php

namespace App\Services\Orders\Document;

use App\Models\OrderWordTemplate;

/**
 * The composer's template source. The engine is now Word-upload: order types are
 * authored as .docx masters with mapped [bracket] variables (see OrderWordTemplate).
 * Legacy block templates/presets are no longer offered for new composition — already
 * issued block orders keep printing from their frozen snapshots.
 */
class OrderTemplateProvider
{
    public function __construct(
        private readonly OrderWordTemplateRepository $words,
    ) {}

    /**
     * @return array<string,string> code => label
     */
    public function available(): array
    {
        return $this->words->available();
    }

    public function find(string $code): ?OrderWordTemplate
    {
        return $this->words->find($code);
    }

    public function exists(string $code): bool
    {
        return $this->words->exists($code);
    }
}
