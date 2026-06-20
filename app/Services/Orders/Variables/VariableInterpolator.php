<?php

namespace App\Services\Orders\Variables;

/**
 * Substitutes `{{ variable }}` placeholders in template text with resolved values,
 * and extracts the placeholders a template uses.
 *
 * Single primitive shared by: template rendering (text → resolved text → AST), the
 * designer (auto-extract the variables/fields a pasted body needs), and validation
 * (every placeholder must be resolvable via OrderVariableRegistry). Replaces the
 * ad-hoc regex substitution scattered through the legacy renderers.
 */
class VariableInterpolator
{
    private const TOKEN = '/\{\{\s*([A-Za-z0-9_.]+)\s*\}\}/u';

    /**
     * @param  array<string,string>  $variables
     */
    public function interpolate(string $text, array $variables, string $missing = '___'): string
    {
        return (string) preg_replace_callback(
            self::TOKEN,
            static function (array $m) use ($variables, $missing): string {
                $key = $m[1];
                $value = $variables[$key] ?? null;

                return ($value === null || $value === '') ? $missing : $value;
            },
            $text,
        );
    }

    /**
     * The distinct placeholder keys referenced by a piece of template text.
     *
     * @return string[]
     */
    public function placeholders(string $text): array
    {
        preg_match_all(self::TOKEN, $text, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    /**
     * Placeholders that cannot be resolved by the registry or the template's own
     * field.* declarations — i.e. tokens that would render as the missing marker.
     *
     * @param  string[]  $fieldKeys
     * @return string[]
     */
    public function unresolvablePlaceholders(string $text, OrderVariableRegistry $registry, array $fieldKeys = []): array
    {
        return array_values(array_filter(
            $this->placeholders($text),
            static fn (string $key) => ! $registry->isResolvable($key, $fieldKeys),
        ));
    }
}
