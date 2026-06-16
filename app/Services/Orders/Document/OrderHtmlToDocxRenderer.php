<?php

namespace App\Services\Orders\Document;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;

/**
 * Renders the order's (possibly HR-edited) preview HTML into the final Word2007
 * document. Because the preview HTML is the canonical content, manual inline edits
 * made before approval are carried verbatim into the saved .docx — the renderer
 * imports the same markup, wrapped in the US Letter / Times New Roman page the
 * samples use.
 */
class OrderHtmlToDocxRenderer
{
    private const FONT = 'Times New Roman';

    private const SIZE = 14;

    public function renderToFile(string $html, ?string $path = null): string
    {
        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName(self::FONT);
        $phpWord->setDefaultFontSize(self::SIZE);

        // US Letter, 1-inch margins — matching the customer's templates.
        $section = $phpWord->addSection([
            'paperSize' => 'Letter',
            'marginTop' => 1440,
            'marginBottom' => 1440,
            'marginLeft' => 1440,
            'marginRight' => 1440,
        ]);

        Html::addHtml($section, $this->normalize($html), false, false);

        $path ??= storage_path('app/tmp/order_'.Str::uuid()->toString().'.docx');
        File::ensureDirectoryExists(dirname($path));
        IOFactory::createWriter($phpWord, 'Word2007')->save($path);

        return $path;
    }

    /**
     * PHPWord's HTML importer expects reasonably well-formed markup; ensure the
     * fragment is wrapped so a bare list of blocks parses cleanly.
     */
    private function normalize(string $html): string
    {
        $html = trim($html);

        if (! str_starts_with($html, '<div') && ! str_starts_with($html, '<body')) {
            $html = '<div>'.$html.'</div>';
        }

        return $html;
    }
}
