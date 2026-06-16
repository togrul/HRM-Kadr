<?php

namespace App\Services\Orders\Document;

use App\Services\Orders\Variables\VariableInterpolator;

/**
 * Derives the input form for an order from its template: scans the blocks for
 * field.* placeholders and maps each to a labelled, typed form field. This is the
 * "form auto-generated from the template" piece — the HR user never wires fields by
 * hand; whatever field.* a template references becomes an input.
 */
class TemplateFieldSchema
{
    public function __construct(private readonly VariableInterpolator $interpolator) {}

    /**
     * Known field metadata (Azerbaijani label + input type). Unknown keys fall back
     * to a humanized label and a text input, so a brand-new field still gets an
     * input even before it is catalogued.
     *
     * @var array<string,array{label:string,type:string}>
     */
    private const CATALOG = [
        'days' => ['label' => 'Gün sayı', 'type' => 'number'],
        // A single start date; the engine derives the full "start-end-cı il" span.
        'work_year' => ['label' => 'İş ilinin başlanğıcı', 'type' => 'date'],
        'start_date' => ['label' => 'Başlama tarixi', 'type' => 'text'],
        'end_date' => ['label' => 'Bitmə tarixi', 'type' => 'text'],
        'return_date' => ['label' => 'İşə başlama tarixi', 'type' => 'text'],
        'position' => ['label' => 'Vəzifə / peşə', 'type' => 'text'],
        'responsible' => ['label' => 'Məsul şəxs', 'type' => 'text'],
        'new_surname' => ['label' => 'Yeni soyad', 'type' => 'text'],
        'basis' => ['label' => 'Əsas (sənəd)', 'type' => 'text'],
    ];

    /**
     * @param  TemplateBlock[]  $blocks
     * @return array<int,array{key:string,placeholder:string,label:string,type:string}>
     */
    public function for(array $blocks): array
    {
        $keys = [];
        foreach ($this->collectText($blocks) as $text) {
            foreach ($this->interpolator->placeholders($text) as $placeholder) {
                if (str_starts_with($placeholder, 'field.')) {
                    $keys[substr($placeholder, strlen('field.'))] = true;
                }
            }
        }

        $fields = [];
        foreach (array_keys($keys) as $key) {
            $meta = self::CATALOG[$key] ?? ['label' => $this->humanize($key), 'type' => 'text'];
            $fields[] = [
                'key' => $key,
                'placeholder' => 'field.'.$key,
                'label' => $meta['label'],
                'type' => $meta['type'],
            ];
        }

        return $fields;
    }

    /**
     * Flatten every text-bearing part of the blocks into a list of strings.
     *
     * @param  TemplateBlock[]  $blocks
     * @return string[]
     */
    private function collectText(array $blocks): array
    {
        $texts = [];
        foreach ($blocks as $block) {
            foreach (['text', 'left', 'right', 'name'] as $scalar) {
                if (isset($block->data[$scalar]) && is_string($block->data[$scalar])) {
                    $texts[] = $block->data[$scalar];
                }
            }
            foreach (['items', 'titleLines'] as $list) {
                foreach ((array) ($block->data[$list] ?? []) as $item) {
                    if (is_string($item)) {
                        $texts[] = $item;
                    }
                }
            }
        }

        return $texts;
    }

    private function humanize(string $key): string
    {
        return ucfirst(str_replace('_', ' ', $key));
    }
}
