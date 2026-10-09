<?php

namespace App\Modules\Orders\Application\Document;

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

    /**
     * @param  string|array<int, string>|null  $effect  one effect or a list of effects
     * @return array<string, string>
     */
    public function availableForPersonnel(string|array|null $effect = null): array
    {
        return OrderWordTemplate::query()
            ->where('is_active', true)
            ->where('effect', '!=', 'hire')
            ->when($effect !== null, fn ($query) => $query->whereIn('effect', (array) $effect))
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
    public function save(string $code, string $label, string $effect, string $docxPath, array $variables, ?int $createdBy = null, bool $multiParticipant = false): OrderWordTemplate
    {
        return OrderWordTemplate::query()->updateOrCreate(
            ['code' => $code],
            [
                'label' => $label,
                'effect' => $effect,
                'docx_path' => $docxPath,
                'variables' => array_values($variables),
                'multi_participant' => $multiParticipant && $effect !== 'hire',
                'is_active' => true,
                'created_by' => $createdBy,
            ],
        );
    }
}
