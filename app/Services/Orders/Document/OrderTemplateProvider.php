<?php

namespace App\Services\Orders\Document;

/**
 * The composer's template source: persisted (designer-authored) templates from the
 * repository, with the built-in presets as a fallback for any code not yet saved to
 * the database. Lets the new engine work before anything is seeded and lets
 * designer edits transparently override a preset.
 */
class OrderTemplateProvider
{
    public function __construct(
        private readonly OrderTemplateRepository $repository,
        private readonly OrderTemplatePresets $presets,
    ) {}

    /**
     * @return array<string,string> code => label
     */
    public function available(): array
    {
        // Presets first, DB second so a saved template overrides its preset label.
        return array_merge($this->presets->available(), $this->repository->available());
    }

    /**
     * @return TemplateBlock[]
     */
    public function blocks(string $code): array
    {
        if ($this->repository->exists($code)) {
            return $this->repository->blocks($code);
        }

        return $this->presets->blocks($code);
    }
}
