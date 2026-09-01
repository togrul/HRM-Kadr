<?php

use Symfony\Component\Finder\Finder;

/**
 * Two things a component tag's attribute list silently swallows, both verified against
 * Blade::render:
 *
 * 1. Whitespace around `=`. `wire:click = "delete(1)"` is parsed as a value-less
 *    `wire:click` plus junk attributes, so the button renders with `wire:click=""` and
 *    does nothing at all.
 * 2. Blade directives other than @class / @style. `@disabled(...)` / `@js(...)` are left
 *    uncompiled, emitting literal `@disabled="@disabled"` — Alpine then throws on the
 *    expression, and a long one can even blow up Livewire's morph-marker regex.
 *
 * Plain HTML tags tolerate both; component tags never do.
 */

/**
 * The text of every `<x-…>` opening tag in the given source.
 *
 * @return array<int, array{line: int, tag: string}>
 */
function componentTags(string $source): array
{
    $tags = [];

    // Blade comments are stripped before compilation, so they cannot break a tag. Their
    // newlines are kept so the reported line numbers still point at the real source.
    $source = preg_replace_callback(
        '/\{\{--.*?--\}\}/s',
        fn (array $match): string => str_repeat("\n", substr_count($match[0], "\n")),
        $source
    ) ?? $source;

    preg_match_all('/<x-[A-Za-z0-9._:-]+/', $source, $matches, PREG_OFFSET_CAPTURE);

    foreach ($matches[0] as [$tagName, $offset]) {
        $i = $offset + strlen($tagName);
        $quote = null;

        while ($i < strlen($source)) {
            $char = $source[$i];

            if ($quote !== null) {
                if ($char === $quote) {
                    $quote = null;
                }
            } elseif ($char === '"' || $char === "'") {
                $quote = $char;
            } elseif ($char === '>') {
                break;
            }

            $i++;
        }

        $tags[] = [
            'line' => substr_count(substr($source, 0, $offset), "\n") + 1,
            'tag' => substr($source, $offset, $i - $offset + 1),
        ];
    }

    return $tags;
}
function componentTagsWithSpacedAttributes(string $source): array
{
    $found = [];

    foreach (componentTags($source) as $tag) {
        if (preg_match('/(?<![\w:.\-])([@:]?[A-Za-z][\w:.\-]*)\s+=\s*"/', $tag['tag'], $attribute)) {
            $found[] = ['line' => $tag['line'], 'attribute' => $attribute[1]];
        }
    }

    return $found;
}

/**
 * @return array<int, array{line: int, attribute: string}>
 */
function componentTagsWithBladeDirectives(string $source): array
{
    $found = [];

    foreach (componentTags($source) as $tag) {
        // @class and @style ARE compiled inside a component tag; nothing else is.
        if (preg_match('/(?<![\w:@])@(?!class\b|style\b)([a-z]+)\s*\(/', $tag['tag'], $directive)) {
            $found[] = ['line' => $tag['line'], 'attribute' => '@'.$directive[1]];
        }
    }

    return $found;
}

it('never writes a component-tag attribute with whitespace around the equals sign', function (): void {
    $files = Finder::create()
        ->files()
        ->in([base_path('resources/views'), base_path('app/Modules')])
        ->name('*.blade.php');

    $offenders = [];

    foreach ($files as $file) {
        $path = str_replace(base_path().'/', '', $file->getPathname());
        $source = $file->getContents();

        foreach (componentTagsWithSpacedAttributes($source) as $hit) {
            $offenders[] = $path.':'.$hit['line'].' ('.$hit['attribute'].' — whitespace around =)';
        }

        foreach (componentTagsWithBladeDirectives($source) as $hit) {
            $offenders[] = $path.':'.$hit['line'].' ('.$hit['attribute'].' — directive is not compiled here)';
        }
    }

    expect($offenders)->toBe([], "Blade drops these attributes, so their wire:click/x-on handlers never fire:\n".implode("\n", $offenders));
});
