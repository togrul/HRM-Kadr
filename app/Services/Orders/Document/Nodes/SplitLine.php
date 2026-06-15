<?php

namespace App\Services\Orders\Document\Nodes;

/**
 * One line with text pinned left and text pinned right — the "Bakı şəhəri … 06 iyun
 * 2026-cı il" city/date row.
 */
final class SplitLine implements DocumentNode
{
    public function __construct(
        public readonly string $left,
        public readonly string $right,
    ) {}
}
