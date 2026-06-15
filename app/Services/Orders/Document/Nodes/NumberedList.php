<?php

namespace App\Services\Orders\Document\Nodes;

/**
 * The auto-numbered clause list ("1. … 2. … 3. …") of the order body.
 */
final class NumberedList implements DocumentNode
{
    /**
     * @param  string[]  $items
     */
    public function __construct(
        public readonly array $items,
    ) {}
}
