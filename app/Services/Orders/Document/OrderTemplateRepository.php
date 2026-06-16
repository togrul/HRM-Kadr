<?php

namespace App\Services\Orders\Document;

use App\Models\OrderBlockTemplate;

/**
 * Persists and loads block-based order templates, bridging the OrderBlockTemplate
 * rows and the in-memory TemplateBlock[] the compiler consumes. The composer reads
 * templates through here; the designer writes through here.
 */
class OrderTemplateRepository
{
    /**
     * @return array<string,string> active code => label
     */
    public function available(): array
    {
        return OrderBlockTemplate::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->pluck('label', 'code')
            ->all();
    }

    public function exists(string $code): bool
    {
        return OrderBlockTemplate::query()->where('code', $code)->exists();
    }

    /**
     * @return TemplateBlock[]
     */
    public function blocks(string $code): array
    {
        $template = OrderBlockTemplate::query()->where('code', $code)->first();

        return $template ? $this->hydrate($template->blocks ?? []) : [];
    }

    /**
     * @param  TemplateBlock[]  $blocks
     */
    public function save(string $code, string $label, array $blocks, ?int $createdBy = null): OrderBlockTemplate
    {
        return OrderBlockTemplate::query()->updateOrCreate(
            ['code' => $code],
            [
                'label' => $label,
                'blocks' => $this->serialize($blocks),
                'fields' => app(TemplateFieldSchema::class)->for($blocks),
                'is_active' => true,
                'created_by' => $createdBy,
            ],
        );
    }

    /**
     * @param  TemplateBlock[]  $blocks
     * @return array<int,array{kind:string,data:array<string,mixed>}>
     */
    private function serialize(array $blocks): array
    {
        return array_map(
            static fn (TemplateBlock $block) => ['kind' => $block->kind, 'data' => $block->data],
            $blocks,
        );
    }

    /**
     * @param  array<int,array{kind?:string,data?:array<string,mixed>}>  $rows
     * @return TemplateBlock[]
     */
    private function hydrate(array $rows): array
    {
        $blocks = [];
        foreach ($rows as $row) {
            if (isset($row['kind'])) {
                $blocks[] = new TemplateBlock($row['kind'], $row['data'] ?? []);
            }
        }

        return $blocks;
    }
}
