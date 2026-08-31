<?php

use Symfony\Component\Finder\Finder;

/**
 * Blade's component-tag compiler does not accept whitespace around `=` inside an
 * `<x-…>` tag. `wire:click = "delete(1)"` is parsed as a value-less `wire:click`
 * plus junk attributes, so the button renders with `wire:click=""` and silently
 * does nothing. Plain HTML tags tolerate it, component tags never do.
 */
function componentTagsWithSpacedAttributes(string $source): array
{
    $found = [];

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

        $tag = substr($source, $offset, $i - $offset + 1);

        if (preg_match('/(?<![\w:.\-])([@:]?[A-Za-z][\w:.\-]*)\s+=\s*"/', $tag, $attribute)) {
            $found[] = [
                'line' => substr_count(substr($source, 0, $offset), "\n") + 1,
                'attribute' => $attribute[1],
            ];
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
        foreach (componentTagsWithSpacedAttributes($file->getContents()) as $hit) {
            $offenders[] = str_replace(base_path().'/', '', $file->getPathname())
                .':'.$hit['line'].' ('.$hit['attribute'].')';
        }
    }

    expect($offenders)->toBe([], "Blade drops these attributes, so their wire:click/x-on handlers never fire:\n".implode("\n", $offenders));
});
