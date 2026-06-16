<?php

namespace App\Services\Orders\Document;

use App\Models\Personnel;
use App\Services\Orders\Document\Nodes\Paragraph;
use App\Services\Orders\Variables\OrderEmployeeVariableResolver;
use App\Services\Orders\Variables\VariableInterpolator;

/**
 * Compiles a template into the OrderDocument AST: resolves every variable
 * (employee.* with declension, field.*, system.*) and interpolates each block,
 * mapping it to the matching AST node.
 *
 * A template is an ordered list of TemplateBlock (the general form that handles
 * every real order shape). compile(OrderTemplateDefinition) is a convenience that
 * lays out the standard order skeleton as blocks and delegates to compileBlocks().
 */
class OrderTemplateCompiler
{
    public function __construct(
        private readonly OrderEmployeeVariableResolver $employeeResolver,
        private readonly VariableInterpolator $interpolator,
    ) {}

    /**
     * @param  TemplateBlock[]  $blocks
     * @param  array{personnel?:?Personnel,fields?:array<string,mixed>,order_number?:string,order_date?:string,system?:array<string,string>}  $context
     */
    public function compileBlocks(array $blocks, array $context = []): OrderDocument
    {
        $variables = $this->resolveVariables($context);
        $document = new OrderDocument;

        foreach ($blocks as $block) {
            match ($block->kind) {
                TemplateBlock::HEADING => $document->centered(
                    $this->interp($block->data['text'] ?? '', $variables),
                    bold: (bool) ($block->data['bold'] ?? true),
                ),
                TemplateBlock::PARAGRAPH => $document->paragraph(
                    $this->interp($block->data['text'] ?? '', $variables),
                    $block->data['align'] ?? Paragraph::ALIGN_JUSTIFY,
                    bold: (bool) ($block->data['bold'] ?? false),
                ),
                TemplateBlock::CLAUSES => $this->clauses($document, $block, $variables),
                TemplateBlock::SPLIT => $document->splitLine(
                    $this->interp($block->data['left'] ?? '', $variables),
                    $this->interp($block->data['right'] ?? '', $variables),
                ),
                TemplateBlock::SIGNATURE => $document->signature(
                    array_map(fn (string $line) => $this->interp($line, $variables), $block->data['titleLines'] ?? []),
                    $this->interp($block->data['name'] ?? '', $variables),
                ),
                TemplateBlock::SPACER => $document->spacer((int) ($block->data['lines'] ?? 1)),
                default => null,
            };
        }

        return $document;
    }

    /**
     * @param  array{personnel?:?Personnel,fields?:array<string,mixed>,order_number?:string,order_date?:string,system?:array<string,string>}  $context
     */
    public function compile(OrderTemplateDefinition $template, array $context = []): OrderDocument
    {
        $context['system'] = array_merge([
            'organization_name' => $template->organizationName,
            'organization_city' => $template->organizationCity,
            'signatory_full_name' => $template->signatoryName,
            'signatory_title' => implode(' ', $template->signatoryTitleLines),
        ], $context['system'] ?? []);

        $blocks = [
            TemplateBlock::heading($template->organizationName),
            TemplateBlock::spacer(),
            TemplateBlock::heading('ƏMR'),
            TemplateBlock::heading('№ {{ system.order_number }}', bold: false),
            TemplateBlock::spacer(),
            TemplateBlock::split('{{ system.organization_city }}', '{{ system.order_date }}'),
            TemplateBlock::spacer(),
            TemplateBlock::paragraph($template->subject, Paragraph::ALIGN_CENTER, bold: true),
            TemplateBlock::paragraph($template->preamble, Paragraph::ALIGN_LEFT),
            TemplateBlock::paragraph('Əmr edirəm:', Paragraph::ALIGN_LEFT, bold: true),
            TemplateBlock::clauses($template->clauses),
        ];

        if (trim($template->basis) !== '') {
            $blocks[] = TemplateBlock::paragraph('Əsas: '.$template->basis, Paragraph::ALIGN_LEFT);
        }

        $blocks[] = TemplateBlock::spacer(2);
        $blocks[] = TemplateBlock::signature($template->signatoryTitleLines, $template->signatoryName);

        return $this->compileBlocks($blocks, $context);
    }

    private function clauses(OrderDocument $document, TemplateBlock $block, array $variables): void
    {
        $items = array_map(fn (string $item) => $this->interp($item, $variables), $block->data['items'] ?? []);

        if (($block->data['numbered'] ?? true) === false) {
            foreach ($items as $item) {
                $document->paragraph($item, Paragraph::ALIGN_JUSTIFY);
            }

            return;
        }

        $document->numberedList($items);
    }

    /**
     * @param  array<string,mixed>  $context
     * @return array<string,string>
     */
    private function resolveVariables(array $context): array
    {
        $variables = $this->employeeResolver->resolve($context['personnel'] ?? null);

        foreach ((array) ($context['fields'] ?? []) as $key => $value) {
            $variables['field.'.$key] = (string) $value;
        }

        foreach ((array) ($context['system'] ?? []) as $key => $value) {
            $variables['system.'.$key] = (string) $value;
        }

        $variables['system.order_number'] = (string) ($context['order_number'] ?? ($variables['system.order_number'] ?? ''));
        $variables['system.order_date'] = (string) ($context['order_date'] ?? ($variables['system.order_date'] ?? ''));

        return $variables;
    }

    /**
     * @param  array<string,string>  $variables
     */
    private function interp(string $text, array $variables): string
    {
        return $this->interpolator->interpolate($text, $variables);
    }
}
