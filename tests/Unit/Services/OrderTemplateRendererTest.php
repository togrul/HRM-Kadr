<?php

namespace Tests\Unit\Services;

use App\Services\Orders\OrderTemplateRenderer;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;
use ZipArchive;

class OrderTemplateRendererTest extends TestCase
{
    public function test_it_renders_valid_docx_xml_for_content_block_with_newline_tokens(): void
    {
        $templatePath = $this->buildTemplateFixture();

        $renderer = new OrderTemplateRenderer;
        $outputPath = $renderer->render(
            storedTemplatePath: $templatePath,
            scalarValues: [
                'day' => '25',
                'month' => 'fevral',
                'year' => '2026',
                'rank_director' => 'general-mayor',
                'name_director' => 'Ferid Əsgərov',
            ],
            rows: [
                ['content_text' => '1. İşə qəbul mətni'],
                ['content_text' => '2. İkinci sətir'],
            ],
            outputBaseName: 'order-test'
        );

        $this->assertFileExists($outputPath);
        $this->assertGreaterThan(0, filesize($outputPath));

        $documentXml = $this->readDocxEntry($outputPath, 'word/document.xml');
        $this->assertNotEmpty($documentXml);

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument;
        $isValidXml = $dom->loadXML($documentXml);

        $this->assertTrue(
            $isValidXml,
            'Generated document.xml is invalid: ' . $this->collectLibxmlErrors()
        );

        $this->assertStringNotContainsString('${newline', $documentXml);
        $this->assertStringNotContainsString('${/newline', $documentXml);
        $this->assertStringContainsString('İşə qəbul mətni', $documentXml);
    }

    public function test_it_normalizes_macro_keys_with_dollar_and_brace_formats(): void
    {
        $templatePath = $this->buildTemplateFixture();

        $renderer = new OrderTemplateRenderer;
        $outputPath = $renderer->render(
            storedTemplatePath: $templatePath,
            scalarValues: [
                '$day' => '26',
                '${month}' => 'mart',
                'year' => '2027',
                '$rank_director' => 'general-leytenant',
                '${name_director}' => 'Test Director',
            ],
            rows: [
                ['${content_text#1}' => '1. Birinci sətir'],
            ],
            outputBaseName: 'order-macro-normalize-test'
        );

        $this->assertFileExists($outputPath);
        $documentXml = $this->readDocxEntry($outputPath, 'word/document.xml');

        $this->assertStringContainsString('26', $documentXml);
        $this->assertStringContainsString('mart', $documentXml);
        $this->assertStringContainsString('2027', $documentXml);
        $this->assertStringContainsString('general-leytenant', $documentXml);
        $this->assertStringContainsString('Test Director', $documentXml);
        $this->assertStringContainsString('Birinci sətir', $documentXml);
        $this->assertStringNotContainsString('${content_text#1}', $documentXml);
        $this->assertStringNotContainsString('${day}', $documentXml);
    }

    private function buildTemplateFixture(): string
    {
        $phpWord = new PhpWord;
        $section = $phpWord->addSection();

        $section->addText('${content}');
        $section->addText('${content_text}');
        $section->addText('${newline}');
        $section->addText('${/newline}');
        $section->addText('${/content}');

        $section->addText('${day}');
        $section->addText('${month}');
        $section->addText('${year}');
        $section->addText('${rank_director}');
        $section->addText('${name_director}');

        $dir = storage_path('app/testing/templates');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $templatePath = $dir . DIRECTORY_SEPARATOR . 'order-template-renderer-test.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($templatePath);

        return $templatePath;
    }

    private function readDocxEntry(string $docxPath, string $entry): string
    {
        $zip = new ZipArchive;
        $opened = $zip->open($docxPath);
        $this->assertTrue($opened === true, 'Cannot open generated docx as zip archive.');

        $content = (string) $zip->getFromName($entry);
        $zip->close();

        return $content;
    }

    private function collectLibxmlErrors(): string
    {
        $errors = libxml_get_errors();
        libxml_clear_errors();

        if (empty($errors)) {
            return 'unknown error';
        }

        return implode(' | ', array_map(static fn ($error) => trim($error->message), $errors));
    }
}
