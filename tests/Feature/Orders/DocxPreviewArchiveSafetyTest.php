<?php

use App\Modules\Orders\Application\Document\DocxToHtmlRenderer;
use App\Modules\Orders\Application\Document\DocxToPdfConverter;
use App\Modules\Orders\Application\Document\UnsafeDocxException;

it('rejects a compressed bomb before either preview engine reads it', function (string $renderer): void {
    $path = tempnam(sys_get_temp_dir(), 'docx_safety_');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::OVERWRITE);
    $zip->addFromString('word/document.xml', str_repeat('A', 2 * 1024 * 1024));
    $zip->close();

    try {
        expect(fn () => $renderer === DocxToHtmlRenderer::class
            ? app($renderer)->render($path)
            : app($renderer)->convert($path))->toThrow(UnsafeDocxException::class);
    } finally {
        unlink($path);
    }
})->with([DocxToHtmlRenderer::class, DocxToPdfConverter::class]);
