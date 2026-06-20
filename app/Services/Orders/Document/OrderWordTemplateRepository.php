<?php

namespace App\Services\Orders\Document;

use App\Models\OrderWordTemplate;

/**
 * Persists and loads Word-upload order templates. The designer writes through here
 * (master .docx path + variable mapping); the composer reads through here to list the
 * available types and fetch the template it fills.
 */
class OrderWordTemplateRepository
{
    /**
     * @return array<string,string> active code => label
     */
    public function available(): array
    {
        return OrderWordTemplate::query()
            ->where('is_active', true)
            ->orderBy('label')
            ->pluck('label', 'code')
            ->all();
    }

    public function exists(string $code): bool
    {
        return OrderWordTemplate::query()->where('code', $code)->exists();
    }

    public function find(string $code): ?OrderWordTemplate
    {
        return OrderWordTemplate::query()->where('code', $code)->first();
    }

    /**
     * @param  array<int,array<string,mixed>>  $variables
     */
    public function save(string $code, string $label, string $effect, string $docxPath, array $variables, ?int $createdBy = null): OrderWordTemplate
    {
        return OrderWordTemplate::query()->updateOrCreate(
            ['code' => $code],
            [
                'label' => $label,
                'effect' => $effect,
                'docx_path' => $docxPath,
                'variables' => array_values($variables),
                'is_active' => true,
                'created_by' => $createdBy,
            ],
        );
    }
}
