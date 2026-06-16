<?php

namespace App\Services\Orders\Document;

/**
 * Converts between TemplateBlock objects and the flat, editable rows the designer UI
 * binds to (kind + a single text area + alignment/bold), preserving formatting on
 * round-trip. Clause lists are edited one item per line; signature title lines the
 * same with the signer's name on the last line.
 */
class DesignerBlockCodec
{
    /**
     * @param  TemplateBlock[]  $blocks
     * @return array<int,array<string,mixed>>
     */
    public function toEditable(array $blocks): array
    {
        return array_map(fn (TemplateBlock $b) => $this->rowFor($b), $blocks);
    }

    /**
     * @param  array<int,array<string,mixed>>  $rows
     * @return TemplateBlock[]
     */
    public function toBlocks(array $rows): array
    {
        $blocks = [];
        foreach ($rows as $row) {
            $blocks[] = $this->blockFor($row);
        }

        return $blocks;
    }

    public function blankRow(string $kind = TemplateBlock::PARAGRAPH): array
    {
        return ['kind' => $kind, 'content' => '', 'align' => 'left', 'bold' => false, 'numbered' => true];
    }

    private function rowFor(TemplateBlock $block): array
    {
        $row = ['kind' => $block->kind, 'content' => '', 'align' => 'left', 'bold' => false, 'numbered' => true];

        switch ($block->kind) {
            case TemplateBlock::HEADING:
            case TemplateBlock::PARAGRAPH:
                $row['content'] = (string) ($block->data['text'] ?? '');
                $row['align'] = (string) ($block->data['align'] ?? 'center');
                $row['bold'] = (bool) ($block->data['bold'] ?? false);
                break;
            case TemplateBlock::CLAUSES:
                $row['content'] = implode("\n", (array) ($block->data['items'] ?? []));
                $row['numbered'] = (bool) ($block->data['numbered'] ?? true);
                break;
            case TemplateBlock::SPLIT:
                $row['content'] = ($block->data['left'] ?? '')."\n".($block->data['right'] ?? '');
                break;
            case TemplateBlock::SIGNATURE:
                $row['content'] = implode("\n", array_merge(
                    (array) ($block->data['titleLines'] ?? []),
                    [(string) ($block->data['name'] ?? '')],
                ));
                break;
            case TemplateBlock::SPACER:
                $row['content'] = (string) ($block->data['lines'] ?? 1);
                break;
        }

        return $row;
    }

    private function blockFor(array $row): TemplateBlock
    {
        $kind = $row['kind'] ?? TemplateBlock::PARAGRAPH;
        $content = (string) ($row['content'] ?? '');
        $lines = array_values(array_filter(array_map('trim', explode("\n", $content)), fn ($l) => $l !== ''));

        return match ($kind) {
            TemplateBlock::HEADING => TemplateBlock::heading($content, (bool) ($row['bold'] ?? true)),
            TemplateBlock::CLAUSES => TemplateBlock::clauses($lines, (bool) ($row['numbered'] ?? true)),
            TemplateBlock::SPLIT => TemplateBlock::split($lines[0] ?? '', $lines[1] ?? ''),
            TemplateBlock::SIGNATURE => TemplateBlock::signature(
                array_slice($lines, 0, -1) ?: $lines,
                count($lines) > 1 ? end($lines) : '',
            ),
            TemplateBlock::SPACER => TemplateBlock::spacer(max(1, (int) $content)),
            default => TemplateBlock::paragraph($content, (string) ($row['align'] ?? 'left'), (bool) ($row['bold'] ?? false)),
        };
    }
}
