<?php

namespace App\Services\Orders\DTO;

final readonly class OrderPrintPayloadData
{
    /**
     * @param  array<string,mixed>  $scalarValues
     * @param  array<int,array<string,mixed>>  $rows
     * @param  array<string,mixed>  $context
     */
    public function __construct(
        public string $templatePath,
        public array $scalarValues,
        public array $rows,
        public string $outputBaseName,
        public array $context,
    ) {}

    /**
     * @return array{
     *   template_path:string,
     *   scalar_values:array<string,mixed>,
     *   rows:array<int,array<string,mixed>>,
     *   output_base_name:string,
     *   context:array<string,mixed>
     * }
     */
    public function toArray(): array
    {
        return [
            'template_path' => $this->templatePath,
            'scalar_values' => $this->scalarValues,
            'rows' => $this->rows,
            'output_base_name' => $this->outputBaseName,
            'context' => $this->context,
        ];
    }
}

