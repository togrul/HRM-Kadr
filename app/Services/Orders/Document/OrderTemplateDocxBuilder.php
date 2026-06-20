<?php

namespace App\Services\Orders\Document;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Tab;

/**
 * Builds a clean, well-formed order (.docx) from a structured spec, with the dynamic
 * parts left as [bracket] placeholders for the Word engine to detect and normalize.
 *
 * Reproducing the customer's layout in code (rather than bracketing their hand-typed
 * files) guarantees every placeholder lands in a single text run — no run-splitting,
 * line-wrap spaces or cross-paragraph breaks to defeat the detector. The page geometry
 * and typography mirror the samples: US Letter, 1-inch margins, Times New Roman 14pt,
 * single spacing, centered organisation/title, a right-tabbed city/date row, justified
 * preamble and hanging-indented numbered clauses.
 */
class OrderTemplateDocxBuilder
{
    private const PAGE = [
        'pageSizeW' => 12240,
        'pageSizeH' => 15840,
        'marginTop' => 1440,
        'marginBottom' => 1440,
        'marginLeft' => 1440,
        'marginRight' => 1440,
    ];

    /** Right edge of the text column (Letter width − both 1-inch margins). */
    private const RIGHT_TAB = 9360;

    private const FONT = 'Times New Roman';

    private const SIZE = 14;

    /**
     * @param  array{
     *     organization:string,
     *     city:string,
     *     subject:string,
     *     preamble:array<int,string>|string,
     *     command_line?:string,
     *     numbered?:bool,
     *     clauses:array<int,string>,
     *     basis:string,
     *     signatory:array<int,string>,
     *     signatory_name:string
     * }  $spec
     * @return string Path to the generated .docx (caller owns / cleans it up).
     */
    public function build(array $spec): string
    {
        $word = new PhpWord;
        $word->setDefaultFontName(self::FONT);
        $word->setDefaultFontSize(self::SIZE);
        $word->getCompatibility()->setOoxmlVersion(15);

        $section = $word->addSection(self::PAGE);

        $center = ['alignment' => Jc::CENTER, 'spaceAfter' => 0];
        $justify = ['alignment' => Jc::BOTH, 'spaceAfter' => 0, 'lineHeight' => 1.0];
        $bold = ['bold' => true];

        // Organisation header + "ƏMR" + "№ <number>".
        $section->addText($spec['organization'], $bold, $center);
        $section->addText('ƏMR', $bold, $center);
        $section->addText('№ [Əmrin nömrəsi]', $bold, $center);

        // City … (right tab) … date.
        $section->addText(
            $spec['city']."\t[Tarix]",
            [],
            ['spaceAfter' => 0, 'tabs' => [new Tab('right', self::RIGHT_TAB)]],
        );
        $this->blank($section);

        // Subject ("… haqqında"), bold and centered like the samples.
        $section->addText($spec['subject'], $bold, $center);
        $this->blank($section);

        // Legal preamble (one or more justified paragraphs).
        foreach ((array) $spec['preamble'] as $paragraph) {
            $section->addText($paragraph, [], $justify);
        }

        // "Əmr edirəm:" — centered.
        $section->addText($spec['command_line'] ?? 'Əmr edirəm:', $bold, $center);

        // Clauses: optionally auto-numbered, justified, with a hanging indent so wrapped
        // lines align under the text (not the number).
        $numbered = $spec['numbered'] ?? true;
        $clauseStyle = $justify + ['indentation' => ['hanging' => 284, 'left' => 284]];
        foreach ($spec['clauses'] as $i => $clause) {
            $text = $numbered ? ($i + 1).'. '.$clause : $clause;
            $section->addText($text, [], $clauseStyle);
        }
        $this->blank($section);

        // "Əsas: …" basis line.
        $section->addText('Əsas: '.$spec['basis'], [], $justify);
        $this->blank($section);
        $this->blank($section);

        // Signatory block: title lines on the left, the name right-tabbed on the last.
        $lines = $spec['signatory'];
        $last = array_pop($lines);
        foreach ($lines as $line) {
            $section->addText($line, [], ['spaceAfter' => 0]);
        }
        $section->addText(
            $last."\t".$spec['signatory_name'],
            [],
            ['spaceAfter' => 0, 'tabs' => [new Tab('right', self::RIGHT_TAB)]],
        );

        $path = tempnam(sys_get_temp_dir(), 'ordertpl_').'.docx';
        $word->save($path, 'Word2007');

        return $path;
    }

    private function blank($section): void
    {
        $section->addTextBreak(1, null, ['spaceAfter' => 0]);
    }
}
