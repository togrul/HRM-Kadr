<?php

namespace App\Services\Orders\Document;

use App\Services\Orders\Document\Nodes\NumberedList;
use App\Services\Orders\Document\Nodes\Paragraph;
use App\Services\Orders\Document\Nodes\SignatureBlock;
use App\Services\Orders\Document\Nodes\Spacer;
use App\Services\Orders\Document\Nodes\SplitLine;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Tab;

/**
 * Renders an OrderDocument to a Word2007 (.docx) file from the SAME AST the HTML
 * preview uses. Formatting mirrors the customer's sample orders (A4, ~Times New
 * Roman 12, centered header/title, left city / right date, signatory block).
 */
class OrderDocumentDocxRenderer
{
    private const FONT = 'Times New Roman';

    private const SIZE = 14;

    /** Space after a body paragraph/clause, in twips (~8pt) — the gap between clauses. */
    private const SPACE_AFTER = 160;

    /** Near-single line spacing inside a paragraph, like the sample (tight lines, gaps between). */
    private const LINE_HEIGHT = 1.08;

    public function renderToFile(OrderDocument $document, ?string $path = null): string
    {
        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName(self::FONT);
        $phpWord->setDefaultFontSize(self::SIZE);

        $section = $phpWord->addSection([
            'paperSize' => 'A4',
            'marginTop' => Converter::cmToTwip(1.0),
            'marginBottom' => Converter::cmToTwip(1.0),
            'marginLeft' => Converter::cmToTwip(1.5),
            'marginRight' => Converter::cmToTwip(1.5),
        ]);

        foreach ($document->nodes() as $node) {
            match (true) {
                $node instanceof Paragraph => $this->paragraph($section, $node),
                $node instanceof SplitLine => $this->splitLine($section, $node),
                $node instanceof NumberedList => $this->numberedList($section, $node),
                $node instanceof SignatureBlock => $this->signature($section, $node),
                $node instanceof Spacer => $this->spacer($section, $node),
                default => null,
            };
        }

        $path ??= $this->tmpPath();
        File::ensureDirectoryExists(dirname($path));
        IOFactory::createWriter($phpWord, 'Word2007')->save($path);

        return $path;
    }

    private function paragraph(Section $section, Paragraph $node): void
    {
        $section->addText(
            $node->text,
            ['bold' => $node->bold],
            ['alignment' => $this->alignment($node->align), 'spaceAfter' => self::SPACE_AFTER, 'lineHeight' => self::LINE_HEIGHT],
        );
    }

    /**
     * City pinned left, date pinned right — rendered with a right tab stop (not a
     * table) so the .docx has no visible borders. Both are bold in the sample.
     */
    private function splitLine(Section $section, SplitLine $node): void
    {
        $run = $section->addTextRun($this->tabbed());
        $run->addText($node->left, ['bold' => true]);
        $run->addText("\t");
        $run->addText($node->right, ['bold' => true]);
    }

    private function numberedList(Section $section, NumberedList $node): void
    {
        $i = 1;
        foreach ($node->items as $item) {
            $section->addText(
                $i.'. '.$item,
                [],
                [
                    'alignment' => Jc::BOTH,
                    'spaceAfter' => self::SPACE_AFTER,
                    'lineHeight' => self::LINE_HEIGHT,
                    // Hanging indent: the number sits at the margin, wrapped lines align
                    // under the clause text (like the sample orders).
                    'indentation' => ['left' => Converter::cmToTwip(0.75), 'hanging' => Converter::cmToTwip(0.75)],
                ],
            );
            $i++;
        }
    }

    /**
     * Signatory title pinned left with the name on the first line pinned right (via a
     * right tab stop, no table → no borders). Title + name are bold in the sample.
     */
    private function signature(Section $section, SignatureBlock $node): void
    {
        $lines = $node->titleLines;
        $first = array_shift($lines) ?? '';

        $run = $section->addTextRun($this->tabbed());
        $run->addText($first, ['bold' => true]);
        $run->addText("\t");
        $run->addText($node->name, ['bold' => true]);

        foreach ($lines as $line) {
            $section->addText($line, ['bold' => true], ['alignment' => Jc::START, 'spaceAfter' => 0]);
        }
    }

    /**
     * Paragraph style with a right tab stop at the right text margin (A4 minus the
     * 1.5 cm side margins = 18 cm), used for the city/date and signatory rows.
     *
     * @return array<string,mixed>
     */
    private function tabbed(): array
    {
        return [
            'tabs' => [new Tab('right', Converter::cmToTwip(18.0))],
            'spaceAfter' => 0,
        ];
    }

    /**
     * Render a vertical gap. Each "line" is a tall empty paragraph so the header
     * (org → ƏMR → city/date → subject) gets the airy spacing of the sample orders.
     */
    private function spacer(Section $section, Spacer $node): void
    {
        for ($i = 0; $i < max(1, $node->lines); $i++) {
            $section->addText('', [], ['lineHeight' => 2.0, 'spaceAfter' => 0]);
        }
    }

    private function alignment(string $align): string
    {
        return match ($align) {
            Paragraph::ALIGN_CENTER => Jc::CENTER,
            Paragraph::ALIGN_RIGHT => Jc::END,
            Paragraph::ALIGN_JUSTIFY => Jc::BOTH,
            default => Jc::START,
        };
    }

    private function tmpPath(): string
    {
        return storage_path('app/tmp/order_'.Str::uuid()->toString().'.docx');
    }
}
