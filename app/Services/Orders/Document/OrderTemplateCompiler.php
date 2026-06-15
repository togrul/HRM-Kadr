<?php

namespace App\Services\Orders\Document;

use App\Models\Personnel;
use App\Services\Orders\Document\Nodes\Paragraph;
use App\Services\Orders\Variables\OrderEmployeeVariableResolver;
use App\Services\Orders\Variables\VariableInterpolator;

/**
 * Compiles a template + the order's data into the OrderDocument AST: assembles the
 * fixed org chrome, resolves every variable (employee.* with declension, field.*,
 * system.*) and interpolates the per-type body. The resulting document is then
 * rendered to preview HTML and/or the final .docx.
 *
 * This is the bridge that turns phases 1-2 (declension + variable resolution) and
 * phase 3 (AST + renderers) into one end-to-end pipeline.
 */
class OrderTemplateCompiler
{
    public function __construct(
        private readonly OrderEmployeeVariableResolver $employeeResolver,
        private readonly VariableInterpolator $interpolator,
    ) {}

    /**
     * @param  array{personnel?:?Personnel,fields?:array<string,mixed>,order_number?:string,order_date?:string}  $context
     */
    public function compile(OrderTemplateDefinition $template, array $context = []): OrderDocument
    {
        $variables = $this->resolveVariables($template, $context);

        $document = (new OrderDocument)
            ->centered($template->organizationName, bold: true)
            ->spacer()
            ->centered('ƏMR', bold: true)
            ->centered('№ '.$variables['system.order_number'])
            ->spacer()
            ->splitLine($variables['system.organization_city'], $variables['system.order_date'])
            ->spacer()
            ->paragraph($this->interp($template->subject, $variables), Paragraph::ALIGN_CENTER, bold: true)
            ->paragraph($this->interp($template->preamble, $variables))
            ->paragraph('Əmr edirəm:', bold: true)
            ->numberedList(array_map(fn (string $clause) => $this->interp($clause, $variables), $template->clauses));

        if (trim($template->basis) !== '') {
            $document->paragraph('Əsas: '.$this->interp($template->basis, $variables));
        }

        return $document
            ->spacer(2)
            ->signature($template->signatoryTitleLines, $variables['system.signatory_full_name']);
    }

    /**
     * @param  array<string,mixed>  $context
     * @return array<string,string>
     */
    private function resolveVariables(OrderTemplateDefinition $template, array $context): array
    {
        $variables = $this->employeeResolver->resolve($context['personnel'] ?? null);

        foreach ((array) ($context['fields'] ?? []) as $key => $value) {
            $variables['field.'.$key] = (string) $value;
        }

        $variables['system.order_number'] = (string) ($context['order_number'] ?? '');
        $variables['system.order_date'] = (string) ($context['order_date'] ?? '');
        $variables['system.organization_name'] = $template->organizationName;
        $variables['system.organization_city'] = $template->organizationCity;
        $variables['system.signatory_full_name'] = $template->signatoryName;
        $variables['system.signatory_title'] = implode(' ', $template->signatoryTitleLines);

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
