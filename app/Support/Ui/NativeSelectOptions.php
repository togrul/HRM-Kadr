<?php

namespace App\Support\Ui;

use DOMDocument;
use DOMElement;

/**
 * Reads the <option>/<optgroup> markup written inside <x-ui.select> into a plain list, so the
 * component can draw the app's own dropdown from the very same slot the views already pass.
 *
 * The rules follow the browser's: an option without a value attribute submits its text, the
 * label attribute wins over the text, an option inside a disabled <optgroup> is disabled too.
 */
final class NativeSelectOptions
{
    /** @var array<string, list<array{id: string, label: string, disabled: bool, selected: bool, group: ?string}>> */
    private static array $cache = [];

    /**
     * @return list<array{id: string, label: string, disabled: bool, selected: bool, group: ?string}>
     */
    public static function parse(string $html): array
    {
        if (trim($html) === '' || ! str_contains($html, '<option')) {
            return [];
        }

        $cacheKey = md5($html);
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"?><html><body><select>'.$html.'</select></body></html>',
            LIBXML_NONET | LIBXML_COMPACT
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $options = [];
        foreach ($document->getElementsByTagName('option') as $option) {
            $group = $option->parentNode instanceof DOMElement && strtolower($option->parentNode->nodeName) === 'optgroup'
                ? $option->parentNode
                : null;
            $text = self::collapse($option->textContent);
            $label = $option->hasAttribute('label') ? self::collapse($option->getAttribute('label')) : $text;

            $options[] = [
                'id' => $option->hasAttribute('value') ? $option->getAttribute('value') : $text,
                'label' => $label !== '' ? $label : $text,
                'disabled' => $option->hasAttribute('disabled') || ($group?->hasAttribute('disabled') ?? false),
                'selected' => $option->hasAttribute('selected'),
                'group' => $group ? self::collapse($group->getAttribute('label')) : null,
            ];
        }

        if (count(self::$cache) > 500) {
            self::$cache = [];
        }

        return self::$cache[$cacheKey] = $options;
    }

    private static function collapse(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }
}
