<?php

namespace Tests\Feature\Orders;

use App\Services\Orders\Document\DocxPlaceholderParser;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\TemplateProcessor;
use Tests\TestCase;
use ZipArchive;

/**
 * The risky core of the Word engine: detecting [bracket] placeholders even when Word
 * has split them across runs, and rewriting them to clean ${token} markers a
 * TemplateProcessor can fill.
 */
class DocxPlaceholderParserTest extends TestCase
{
    private string $source = '';

    private string $dest = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->source = tempnam(sys_get_temp_dir(), 'src_').'.docx';
        $this->dest = tempnam(sys_get_temp_dir(), 'dst_').'.docx';
        $this->makeRunSplitDocx($this->source);
    }

    protected function tearDown(): void
    {
        @unlink($this->source);
        @unlink($this->dest);
        parent::tearDown();
    }

    public function test_it_extracts_run_split_placeholders_in_order(): void
    {
        $labels = app(DocxPlaceholderParser::class)->extract($this->source);

        // "[Başlama tarixi]" is split across four runs in the source, yet detected once.
        $this->assertSame(['Başlama tarixi', 'Tam ad'], $labels);
    }

    public function test_it_normalizes_brackets_to_tokens_that_template_processor_fills(): void
    {
        $parser = app(DocxPlaceholderParser::class);

        $parser->normalize($this->source, [
            'Başlama tarixi' => 'var_1',
            'Tam ad' => 'var_2',
        ], $this->dest);

        // The normalized master exposes exactly the two clean tokens.
        $processor = new TemplateProcessor($this->dest);
        $this->assertEqualsCanonicalizing(['var_1', 'var_2'], $processor->getVariables());

        $processor->setValue('var_1', '01.01.2026-cı il');
        $processor->setValue('var_2', 'Bayramov Ruslan');
        $filled = tempnam(sys_get_temp_dir(), 'fil_').'.docx';
        $processor->saveAs($filled);

        $text = $this->documentText($filled);
        @unlink($filled);

        $this->assertStringContainsString('01.01.2026-cı il', $text);
        $this->assertStringContainsString('Bayramov Ruslan', $text);
        // No stray markers survive.
        $this->assertStringNotContainsString('[', $text);
        $this->assertStringNotContainsString('${', $text);
    }

    public function test_it_rewrites_a_run_split_literal_into_a_bracket(): void
    {
        $parser = app(DocxPlaceholderParser::class);

        // "Başlama tarixi" is split across runs in the source; rewriting it (preparing a
        // template from a real order) must still match across the split.
        $parser->rewriteLiterals($this->source, ['Başlama tarixi' => 'Məzuniyyət başlanğıcı'], $this->dest);

        $labels = $parser->extract($this->dest);
        $this->assertContains('Məzuniyyət başlanğıcı', $labels);
        $this->assertNotContains('Başlama tarixi', $labels);
    }

    /**
     * Build a valid .docx (via PhpWord) and overwrite its body with a document.xml that
     * deliberately splits "[Başlama tarixi]" across several <w:r> runs — exactly the
     * fragmentation Word produces and a naive single-run replace would miss.
     */
    private function makeRunSplitDocx(string $path): void
    {
        $phpWord = new PhpWord;
        $phpWord->addSection()->addText('placeholder');
        IOFactory::createWriter($phpWord, 'Word2007')->save($path);

        $body = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>
<w:p><w:r><w:t xml:space="preserve">Tarix: </w:t></w:r><w:r><w:t>[</w:t></w:r><w:r><w:t>Başlama</w:t></w:r><w:r><w:t xml:space="preserve"> tarixi</w:t></w:r><w:r><w:t>]</w:t></w:r><w:r><w:t xml:space="preserve"> qeyd olunsun</w:t></w:r></w:p>
<w:p><w:r><w:rPr><w:b/></w:rPr><w:t>İşçi: [Tam ad]</w:t></w:r></w:p>
</w:body></w:document>
XML;

        $zip = new ZipArchive;
        $zip->open($path);
        $zip->deleteName('word/document.xml');
        $zip->addFromString('word/document.xml', $body);
        $zip->close();
    }

    private function documentText(string $docxPath): string
    {
        $zip = new ZipArchive;
        $zip->open($docxPath);
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();

        return html_entity_decode(strip_tags(str_replace('<', ' <', $xml)), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
