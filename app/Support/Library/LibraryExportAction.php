<?php

namespace App\Support\Library;

final readonly class LibraryExportAction
{
    public function __construct(
        public string $method,
        public string $label,
    ) {
    }

    public static function make(string $method, string $label): self
    {
        return new self($method, $label);
    }

    public function toArray(): array
    {
        return [
            'method' => $this->method,
            'label' => $this->label,
        ];
    }
}
