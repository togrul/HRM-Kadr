<?php

namespace App\Services\Orders\Document;

use App\Services\Orders\Document\Nodes\NumberedList;
use App\Services\Orders\Document\Nodes\Paragraph;
use App\Services\Orders\Document\Nodes\SignatureBlock;
use App\Services\Orders\Document\Nodes\Spacer;
use App\Services\Orders\Document\Nodes\SplitLine;

/**
 * Renders an OrderDocument to HTML for the order preview. The markup is plain and
 * semantic so it can be made contenteditable for inline correction before the order
 * is approved; the (possibly edited) HTML is then frozen as the order snapshot.
 */
class OrderDocumentHtmlRenderer
{
    public function render(OrderDocument $document): string
    {
        $html = '<div class="order-document">';

        foreach ($document->nodes() as $node) {
            $html .= match (true) {
                $node instanceof Paragraph => $this->paragraph($node),
                $node instanceof SplitLine => $this->splitLine($node),
                $node instanceof NumberedList => $this->numberedList($node),
                $node instanceof SignatureBlock => $this->signature($node),
                $node instanceof Spacer => str_repeat('<p class="order-spacer">&nbsp;</p>', max(1, $node->lines)),
                default => '',
            };
        }

        return $html.'</div>';
    }

    private function paragraph(Paragraph $node): string
    {
        $style = 'text-align:'.$node->align.';'.($node->bold ? 'font-weight:600;' : '');

        return '<p class="order-paragraph" style="'.$style.'">'.$this->e($node->text).'</p>';
    }

    private function splitLine(SplitLine $node): string
    {
        return '<div class="order-split-line" style="display:flex;justify-content:space-between;">'
            .'<span>'.$this->e($node->left).'</span>'
            .'<span>'.$this->e($node->right).'</span>'
            .'</div>';
    }

    private function numberedList(NumberedList $node): string
    {
        $items = '';
        foreach ($node->items as $item) {
            $items .= '<li class="order-clause">'.$this->e($item).'</li>';
        }

        return '<ol class="order-clauses">'.$items.'</ol>';
    }

    private function signature(SignatureBlock $node): string
    {
        $title = '';
        foreach ($node->titleLines as $line) {
            $title .= $this->e($line).'<br>';
        }

        return '<div class="order-signature" style="display:flex;justify-content:space-between;align-items:flex-end;">'
            .'<div class="order-signature-title">'.$title.'</div>'
            .'<div class="order-signature-name">'.$this->e($node->name).'</div>'
            .'</div>';
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
