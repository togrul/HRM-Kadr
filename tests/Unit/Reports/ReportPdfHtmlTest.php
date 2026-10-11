<?php

use App\Modules\Reports\Application\Pdf\LibreOfficeReportPdfRenderer;
use App\Modules\Reports\Application\Pdf\ReportPdfException;
use Tests\TestCase;

uses(TestCase::class);

/**
 * PDF-in HTML mənbəyi: UTF-8, Azərbaycan hərflərini əhatə edən şrift və escape olunmuş məzmun.
 */
it('builds utf-8 html with the configured unicode font', function (): void {
    config(['reports.pdf.font_family' => 'DejaVu Sans']);

    $html = (new LibreOfficeReportPdfRenderer)->html([
        'title' => 'Kadr hesabatı — əşçğıöü',
        'columns' => [['key' => 'name', 'label' => 'Ad']],
        'rows' => [['name' => 'Gülnar']],
    ], [['label' => 'Dövr', 'value' => 'Mart 2026']]);

    expect($html)->toContain('<meta charset="utf-8">')
        ->and($html)->toContain("font-family: 'DejaVu Sans'")
        ->and($html)->toContain('Kadr hesabatı — əşçğıöü')
        ->and($html)->toContain('Gülnar')
        ->and($html)->toContain('Mart 2026');
});

it('escapes report values so a name cannot inject markup into the pdf', function (): void {
    $html = (new LibreOfficeReportPdfRenderer)->html([
        'title' => '<b>x</b>',
        'columns' => [['key' => 'name', 'label' => 'Ad']],
        'rows' => [['name' => '<script>alert(1)</script>']],
    ]);

    expect($html)->not->toContain('<script>alert(1)</script>')
        ->and($html)->toContain('&lt;script&gt;')
        ->and($html)->not->toContain('<b>x</b>');
});

it('shows the empty state row when the report has no rows', function (): void {
    $html = (new LibreOfficeReportPdfRenderer)->html(['title' => 'Boş', 'columns' => [['key' => 'a', 'label' => 'A']], 'rows' => []]);

    expect($html)->toContain(__('reports::dashboard.empty.no_report_data'));
});

it('tells an unavailable engine apart from a failed conversion', function (): void {
    expect(ReportPdfException::unavailable()->isUnavailable())->toBeTrue()
        ->and(ReportPdfException::failed('timeout')->isUnavailable())->toBeFalse()
        ->and(ReportPdfException::unavailable()->userMessage())->toBe(__('reports::pdf.errors.unavailable'))
        ->and(ReportPdfException::failed('x')->userMessage())->toBe(__('reports::pdf.errors.failed'));
});

it('throws a typed exception when no libreoffice binary can be found', function (): void {
    config(['reports.pdf.soffice_path' => '/nonexistent/soffice']);
    $renderer = new LibreOfficeReportPdfRenderer;

    if ($renderer->isAvailable()) {
        $this->markTestSkipped('LibreOffice standart yolda quraşdırılıb.');
    }

    $renderer->render(['title' => 'x', 'columns' => [], 'rows' => []]);
})->throws(ReportPdfException::class);
