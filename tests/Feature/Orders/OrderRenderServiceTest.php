<?php

namespace Tests\Feature\Orders;

use App\Services\Orders\Document\OrderRenderService;
use App\Services\Orders\Document\OrderSnapshot;
use App\Services\Orders\Document\OrderTemplateDefinition;
use Tests\TestCase;
use ZipArchive;

/**
 * Phase 4 — preview → (inline edit) → finalize. The preview HTML is the canonical
 * content, so an edit made before approval must appear in the frozen snapshot and
 * the generated .docx.
 */
class OrderRenderServiceTest extends TestCase
{
    private function template(): OrderTemplateDefinition
    {
        return new OrderTemplateDefinition(
            organizationName: '“DİNÇER VƏ CARÇIOĞLU” BİRGƏ MÜƏSSİSƏSİ',
            organizationCity: 'Bakı şəhəri',
            numberSuffix: '-M',
            subject: 'Əmək məzuniyyətinin verilməsi haqqında',
            preamble: 'Azərbaycan Respublikası Əmək Məcəlləsinin 138-ci maddəsini rəhbər tutaraq',
            clauses: ['{{ field.body }} {{ field.days }} təqvim günü müddətində əmək məzuniyyəti verilsin.'],
            basis: 'ərizə.',
            signatoryTitleLines: ['kommunikasiyalar üzrə müavini'],
            signatoryName: 'Sübhan İsmayılov',
        );
    }

    private function context(): array
    {
        return [
            'fields' => ['body' => 'Cəfərova Fidan Məsud oğluna', 'days' => '14'],
            'order_number' => '214-M',
            'order_date' => '14 may 2026-cı il',
        ];
    }

    public function test_preview_returns_editable_html(): void
    {
        $html = app(OrderRenderService::class)->preview($this->template(), $this->context());

        $this->assertStringContainsString('class="order-document"', $html);
        $this->assertStringContainsString('order-split-line', $html);
        $this->assertStringContainsString('Cəfərova Fidan Məsud oğluna', $html);
        $this->assertStringNotContainsString('{{', $html);
    }

    public function test_inline_edit_is_carried_into_the_finalized_docx(): void
    {
        $service = app(OrderRenderService::class);
        $previewHtml = $service->preview($this->template(), $this->context());

        // HR corrects the name in the preview before approving.
        $editedHtml = str_replace(
            'Cəfərova Fidan Məsud oğluna',
            'Cəfərova Fidan Məsud qızına',
            $previewHtml
        );

        $snapshot = $service->finalize($editedHtml);

        $this->assertInstanceOf(OrderSnapshot::class, $snapshot);
        $this->assertSame($editedHtml, $snapshot->html);

        try {
            $zip = new ZipArchive;
            $this->assertTrue($zip->open($snapshot->docxPath) === true);
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();

            // The edit is in the saved document; the original is gone.
            $this->assertStringContainsString('qızına', $xml);
            $this->assertStringNotContainsString('oğluna', $xml);
        } finally {
            @unlink($snapshot->docxPath);
        }
    }
}
