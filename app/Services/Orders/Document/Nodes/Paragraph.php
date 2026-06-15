<?php

namespace App\Services\Orders\Document\Nodes;

/**
 * A single text paragraph with alignment/emphasis (header, subject, preamble, "Əmr
 * edirəm:", basis line…).
 */
final class Paragraph implements DocumentNode
{
    public const ALIGN_LEFT = 'left';

    public const ALIGN_CENTER = 'center';

    public const ALIGN_RIGHT = 'right';

    public const ALIGN_JUSTIFY = 'justify';

    public function __construct(
        public readonly string $text,
        public readonly string $align = self::ALIGN_LEFT,
        public readonly bool $bold = false,
    ) {}
}
