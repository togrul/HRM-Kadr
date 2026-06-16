<?php

namespace App\Services\Orders\Document;

/**
 * Facade for the order render flow:
 *   1. preview()  — compile a template + the order's data into editable preview HTML
 *   2. (the HR user reviews and may edit the HTML inline)
 *   3. finalize() — freeze the approved HTML into an immutable snapshot + the .docx
 *
 * The HTML is the single canonical content, so any inline edit made between steps 1
 * and 3 is carried into the saved document.
 */
class OrderRenderService
{
    public function __construct(
        private readonly OrderTemplateCompiler $compiler,
        private readonly OrderDocumentHtmlRenderer $htmlRenderer,
        private readonly OrderHtmlToDocxRenderer $docxRenderer,
    ) {}

    /**
     * @param  array{personnel?:mixed,fields?:array<string,mixed>,order_number?:string,order_date?:string}  $context
     */
    public function preview(OrderTemplateDefinition $template, array $context = []): string
    {
        return $this->htmlRenderer->render($this->compiler->compile($template, $context));
    }

    /**
     * Preview from a raw block list (the form the designer/presets produce).
     *
     * @param  TemplateBlock[]  $blocks
     * @param  array<string,mixed>  $context
     */
    public function previewBlocks(array $blocks, array $context = []): string
    {
        return $this->htmlRenderer->render($this->compiler->compileBlocks($blocks, $context));
    }

    /**
     * Freeze the approved (possibly edited) HTML into the order snapshot + .docx.
     */
    public function finalize(string $approvedHtml, ?string $docxPath = null): OrderSnapshot
    {
        return new OrderSnapshot(
            html: $approvedHtml,
            docxPath: $this->docxRenderer->renderToFile($approvedHtml, $docxPath),
        );
    }

    /**
     * Convenience: compile + finalize in one call when no manual edit is needed.
     *
     * @param  array{personnel?:mixed,fields?:array<string,mixed>,order_number?:string,order_date?:string}  $context
     */
    public function compileAndFinalize(OrderTemplateDefinition $template, array $context = [], ?string $docxPath = null): OrderSnapshot
    {
        return $this->finalize($this->preview($template, $context), $docxPath);
    }
}
