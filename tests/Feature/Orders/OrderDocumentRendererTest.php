<?php

namespace Tests\Feature\Orders;

use App\Services\Orders\Document\Nodes\Paragraph;
use App\Services\Orders\Document\OrderDocument;
use App\Services\Orders\Document\OrderDocumentDocxRenderer;
use App\Services\Orders\Document\OrderDocumentHtmlRenderer;
use Tests\TestCase;
use ZipArchive;

/**
 * One AST → two outputs. A single OrderDocument (modelled on the "Məzuniyyət" sample
 * order) must render to both preview HTML and a valid .docx with the same content,
 * proving the renderers share one document model.
 */
class OrderDocumentRendererTest extends TestCase
{
    private function sampleOrder(): OrderDocument
    {
        return (new OrderDocument)
            ->centered('“DİNÇER VƏ CARÇIOĞLU” BİRGƏ MÜƏSSİSƏSİ', bold: true)
            ->spacer()
            ->centered('ƏMR', bold: true)
            ->centered('№ 214-M')
            ->spacer()
            ->splitLine('Bakı şəhəri', '14 may 2026-cı il')
            ->spacer()
            ->paragraph('Əmək məzuniyyətinin verilməsi haqqında', Paragraph::ALIGN_CENTER, bold: true)
            ->paragraph('Azərbaycan Respublikası Əmək Məcəlləsinin 138-ci maddəsinin 2-ci hissəsini rəhbər tutaraq')
            ->paragraph('Əmr edirəm:', bold: true)
            ->numberedList([
                'Keşlə Qeyri-Qida Satış mərkəzinin satınalma operatoru Cəfərova Fidan Məsud oğluna 14 təqvim günü müddətində əmək məzuniyyəti verilsin.',
                'Məzuniyyətin başlanma tarixi 19.05.2026-cı il, bitmə tarixi 03.06.2026-cı il müəyyən edilsin.',
            ])
            ->paragraph('Əsas: F.M.Cəfərovanın ərizəsi.')
            ->spacer(2)
            ->signature([
                'Baş direktorun İnsan resursları,',
                'təşkilati idarəetmə və',
                'kommunikasiyalar üzrə müavini',
            ], 'Sübhan İsmayılov');
    }

    public function test_html_renderer_emits_editable_structure(): void
    {
        $html = app(OrderDocumentHtmlRenderer::class)->render($this->sampleOrder());

        $this->assertStringContainsString('class="order-document"', $html);
        $this->assertStringContainsString('ƏMR', $html);
        $this->assertStringContainsString('order-split-line', $html);
        $this->assertStringContainsString('<ol class="order-clauses">', $html);
        $this->assertStringContainsString('Cəfərova Fidan Məsud oğluna', $html);
        $this->assertStringContainsString('order-signature', $html);
        $this->assertStringContainsString('Sübhan İsmayılov', $html);
    }

    public function test_docx_renderer_produces_valid_document_with_same_content(): void
    {
        $path = app(OrderDocumentDocxRenderer::class)->renderToFile($this->sampleOrder());

        try {
            $this->assertFileExists($path);
            $this->assertGreaterThan(0, filesize($path));

            $zip = new ZipArchive;
            $this->assertTrue($zip->open($path) === true);
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();

            $this->assertIsString($xml);
            $this->assertStringContainsString('ƏMR', $xml);
            $this->assertStringContainsString('Cəfərova Fidan Məsud oğluna', $xml);
            $this->assertStringContainsString('Sübhan İsmayılov', $xml);
        } finally {
            @unlink($path);
        }
    }

    public function test_html_escapes_user_content(): void
    {
        $html = app(OrderDocumentHtmlRenderer::class)->render(
            (new OrderDocument)->paragraph('<script>alert(1)</script>')
        );

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }
}
