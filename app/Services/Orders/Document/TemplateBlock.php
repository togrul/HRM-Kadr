<?php

namespace App\Services\Orders\Document;

use App\Services\Orders\Document\Nodes\Paragraph;

/**
 * One authored block of an order template — the flexible, pre-interpolation unit a
 * template is composed of. A template is simply an ordered list of these, which the
 * compiler interpolates ({{ variables }}) and maps to AST nodes.
 *
 * Unlike a fixed subject/preamble/clauses schema, a block list handles every real
 * order shape: many free-form preamble paragraphs (Maddə ilə xitam), unnumbered
 * clauses (Soyadın dəyişdirilməsi), an address line in the header (Xitam), etc.
 */
final class TemplateBlock
{
    public const HEADING = 'heading';

    public const PARAGRAPH = 'paragraph';

    public const CLAUSES = 'clauses';

    public const SPLIT = 'split';

    public const SIGNATURE = 'signature';

    public const SPACER = 'spacer';

    /**
     * @param  array<string,mixed>  $data
     */
    public function __construct(
        public readonly string $kind,
        public readonly array $data = [],
    ) {}

    public static function heading(string $text, bool $bold = true): self
    {
        return new self(self::HEADING, ['text' => $text, 'bold' => $bold]);
    }

    public static function paragraph(string $text, string $align = Paragraph::ALIGN_JUSTIFY, bool $bold = false): self
    {
        return new self(self::PARAGRAPH, ['text' => $text, 'align' => $align, 'bold' => $bold]);
    }

    /**
     * @param  string[]  $items
     */
    public static function clauses(array $items, bool $numbered = true): self
    {
        return new self(self::CLAUSES, ['items' => array_values($items), 'numbered' => $numbered]);
    }

    public static function split(string $left, string $right): self
    {
        return new self(self::SPLIT, ['left' => $left, 'right' => $right]);
    }

    /**
     * @param  string[]  $titleLines
     */
    public static function signature(array $titleLines, string $name): self
    {
        return new self(self::SIGNATURE, ['titleLines' => array_values($titleLines), 'name' => $name]);
    }

    public static function spacer(int $lines = 1): self
    {
        return new self(self::SPACER, ['lines' => $lines]);
    }
}
