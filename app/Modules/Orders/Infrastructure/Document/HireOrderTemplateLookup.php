<?php

namespace App\Modules\Orders\Infrastructure\Document;

use App\Models\OrderWordTemplate;
use App\Modules\Orders\Contracts\HireOrderTemplates;

class HireOrderTemplateLookup implements HireOrderTemplates
{
    public function hireTemplateCode(): ?string
    {
        $code = OrderWordTemplate::query()
            ->where('is_active', true)
            ->where('effect', 'hire')
            ->orderByRaw('CASE WHEN code = ? THEN 0 ELSE 1 END', [self::DEFAULT_CODE])
            ->orderBy('label')
            ->value('code');

        return $code !== null ? (string) $code : null;
    }
}
