@props([
    'paginator',
    'summary' => null,   // overrides the left-hand caption, e.g. "48 ezamiyyət · 23 nəfər ezamiyyətdədir"
    'unit' => null,      // noun for the default caption, e.g. "nəticə"
    'window' => 2,       // page links kept either side of the current page
])

@php
    $current = $paginator->currentPage();
    $last = method_exists($paginator, 'lastPage') ? $paginator->lastPage() : 1;

    // First, last and a window around the current page — the design shows a short
    // numeric pager, never the full run of pages.
    $pages = collect(range(1, $last))
        ->filter(fn (int $page): bool => $page === 1
            || $page === $last
            || abs($page - $current) <= $window)
        ->values();

    // array_filter() would drop a zero total, leaving the caption with no number at all.
    $caption = $summary ?? trim(implode(' ', array_filter([
        number_format($paginator->total(), 0, ',', ' '),
        $unit,
    ], fn (?string $part): bool => $part !== null && $part !== ''))).' · '
        .__('ui::common.pagination.page_of', ['current' => $current, 'last' => $last]);

    $link = 'inline-flex h-8 min-w-[32px] items-center justify-center rounded-[10px] px-2 text-[12.5px] transition';
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-3 border-t border-hairline px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5']) }}>
    <p class="hrm-num text-[12.5px] text-ink-muted">{{ $caption }}</p>

    @if ($last > 1)
        <nav class="flex items-center gap-1" role="navigation" aria-label="{{ __('ui::common.pagination.label') }}">
            <button
                type="button"
                @disabled($paginator->onFirstPage())
                wire:click="previousPage('{{ $paginator->getPageName() }}')"
                class="{{ $link }} border border-hairline text-ink-muted hover:bg-[#fafafa] hover:text-ink disabled:pointer-events-none disabled:opacity-40"
                aria-label="{{ __('ui::common.pagination.previous') }}"
            >&lsaquo;</button>

            @foreach ($pages as $page)
                @if ($loop->index > 0 && $page - $pages[$loop->index - 1] > 1)
                    <span class="px-1 text-[12.5px] text-ink-faint">…</span>
                @endif

                <button
                    type="button"
                    wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                    @class([
                        $link,
                        'hrm-num',
                        'bg-ink font-semibold text-white' => $page === $current,
                        'border border-hairline text-ink-muted hover:bg-[#fafafa] hover:text-ink' => $page !== $current,
                    ])
                    @if ($page === $current) aria-current="page" @endif
                >{{ $page }}</button>
            @endforeach

            <button
                type="button"
                @disabled(! $paginator->hasMorePages())
                wire:click="nextPage('{{ $paginator->getPageName() }}')"
                class="{{ $link }} border border-hairline text-ink-muted hover:bg-[#fafafa] hover:text-ink disabled:pointer-events-none disabled:opacity-40"
                aria-label="{{ __('ui::common.pagination.next') }}"
            >&rsaquo;</button>
        </nav>
    @endif
</div>
