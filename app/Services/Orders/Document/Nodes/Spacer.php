<?php

namespace App\Services\Orders\Document\Nodes;

/**
 * Vertical blank space (empty paragraphs) between sections.
 */
final class Spacer implements DocumentNode
{
    public function __construct(
        public readonly int $lines = 1,
    ) {}
}
