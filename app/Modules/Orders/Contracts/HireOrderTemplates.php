<?php

namespace App\Modules\Orders\Contracts;

/**
 * Sanctioned cross-module surface for finding the order type that hires a candidate
 * ("İşə qəbul"). Other modules (e.g. Candidates) use it to open the order composer with
 * the hire preset already chosen, without knowing how Orders stores its templates.
 *
 * @see \App\Modules\Orders\Infrastructure\Document\HireOrderTemplateLookup
 */
interface HireOrderTemplates
{
    /** The conventional code of the seeded hire template. */
    public const DEFAULT_CODE = 'ise_qebul';

    /**
     * The code of the active hire template to preset in the composer: the seeded
     * `ise_qebul` when it is active, otherwise the first active hire template; null when
     * the company has none.
     */
    public function hireTemplateCode(): ?string;
}
