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

/**
 * Renders an OrderDocument to a Word2007 (.docx) file from the SAME AST the HTML
 * preview uses. Formatting mirrors the customer's sample orders (A4, ~Times New
 * Roman 12, centered header/title, left city / right date, signatory block).
 */
class OrderDocumentDocxRenderer
{
    private const FONT = 'Times New Roman';

    private const SIZE = 12;

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
                $node instanceof Spacer => $section->addTextBreak(max(1, $node->lines)),
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
            ['alignment' => $this->alignment($node->align)],
        );
    }

    private function splitLine(Section $section, SplitLine $node): void
    {
        $table = $section->addTable(['borderSize' => 0, 'cellMargin' => 0]);
        $table->addRow();
        $half = Converter::cmToTwip(9.0);
        $table->addCell($half)->addText($node->left, [], ['alignment' => Jc::START]);
        $table->addCell($half)->addText($node->right, [], ['alignment' => Jc::END]);
    }

    private function numberedList(Section $section, NumberedList $node): void
    {
        $i = 1;
        foreach ($node->items as $item) {
            $section->addText(
                $i.'. '.$item,
                [],
                ['alignment' => Jc::BOTH, 'spaceAfter' => 120],
            );
            $i++;
        }
    }

    private function signature(Section $section, SignatureBlock $node): void
    {
        $table = $section->addTable(['borderSize' => 0, 'cellMargin' => 0]);
        $table->addRow();

        $titleCell = $table->addCell(Converter::cmToTwip(11.0));
        foreach ($node->titleLines as $idx => $line) {
            $titleCell->addText($line, [], ['alignment' => Jc::START, 'spaceAfter' => 0]);
        }

        $table->addCell(Converter::cmToTwip(7.0))->addText($node->name, [], ['alignment' => Jc::END]);
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
