<?php

use Illuminate\Support\Facades\Blade;
use Symfony\Component\Finder\Finder;

/**
 * One primary action per page: a pill button is secondary unless it says otherwise,
 * and no page header offers two black buttons competing for attention.
 */
it('renders a pill button without a variant as secondary', function (): void {
    $html = Blade::render('<x-pill-button>Reset</x-pill-button>');

    expect($html)->not->toContain('bg-ink')->toContain('bg-[#f4f4f5]');
});

it('never puts more than one primary button in a page header', function (): void {
    $files = Finder::create()->files()->in([base_path('resources/views'), base_path('app/Modules')])->name('*.blade.php');
    $offenders = [];

    foreach ($files as $file) {
        preg_match_all('#<x-slot:actions>(.*?)</x-slot:actions>|<x-slot name="actions">(.*?)</x-slot>#s', $file->getContents(), $slots, PREG_SET_ORDER);

        foreach ($slots as $slot) {
            $primaries = preg_match_all('/<x-pill-button[^>]*variant="primary"/', ($slot[1] ?? '') ?: ($slot[2] ?? ''));

            if ($primaries > 1) {
                $offenders[] = str_replace(base_path().'/', '', $file->getPathname())." ({$primaries} primary buttons)";
            }
        }
    }

    expect($offenders)->toBe([], "Keep one primary action per header; make the rest secondary:\n".implode("\n", $offenders));
});
