<?php

namespace App\Services\Orders\Document;

use App\Services\Orders\Document\Nodes\DocumentNode;
use App\Services\Orders\Document\Nodes\NumberedList;
use App\Services\Orders\Document\Nodes\Paragraph;
use App\Services\Orders\Document\Nodes\SignatureBlock;
use App\Services\Orders\Document\Nodes\Spacer;
use App\Services\Orders\Document\Nodes\SplitLine;

/**
 * The structured, render-target-agnostic representation of a single rendered order.
 * Built once (from a template + resolved variables), then handed to either the HTML
 * renderer (preview/inline-edit) or the DOCX renderer (final document).
 *
 * Fluent helpers keep construction readable; nodes can also be passed directly.
 */
final class OrderDocument
{
    /** @var DocumentNode[] */
    private array $nodes = [];

    public function add(DocumentNode $node): self
    {
        $this->nodes[] = $node;

        return $this;
    }

    public function paragraph(string $text, string $align = Paragraph::ALIGN_LEFT, bool $bold = false): self
    {
        return $this->add(new Paragraph($text, $align, $bold));
    }

    public function centered(string $text, bool $bold = false): self
    {
        return $this->paragraph($text, Paragraph::ALIGN_CENTER, $bold);
    }

    public function splitLine(string $left, string $right): self
    {
        return $this->add(new SplitLine($left, $right));
    }

    /**
     * @param  string[]  $items
     */
    public function numberedList(array $items): self
    {
        return $this->add(new NumberedList(array_values($items)));
    }

    /**
     * @param  string[]  $titleLines
     */
    public function signature(array $titleLines, string $name): self
    {
        return $this->add(new SignatureBlock(array_values($titleLines), $name));
    }

    public function spacer(int $lines = 1): self
    {
        return $this->add(new Spacer($lines));
    }

    /**
     * @return DocumentNode[]
     */
    public function nodes(): array
    {
        return $this->nodes;
    }
}
