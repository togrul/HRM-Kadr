<div class="px-4 pb-4 pt-3 sm:px-5 sm:pb-5">
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Pagination Navigation" class="rounded-[20px] border border-zinc-200 bg-white px-3 py-3 shadow-sm sm:px-4">
            <div class="flex flex-col gap-3 sm:hidden">
                <p class="text-sm font-medium tracking-tight text-zinc-600">
                    <span>{!! __('pagination.showing') !!}</span>
                    <span class="font-semibold text-zinc-900">{{ $paginator->firstItem() }}</span>
                    <span>{!! __('pagination.to') !!}</span>
                    <span class="font-semibold text-zinc-900">{{ $paginator->lastItem() }}</span>
                    <span>/</span>
                    <span class="font-semibold text-zinc-900">{{ $paginator->total() }}</span>
                    <span>{!! __('pagination.results') !!}</span>
                </p>

                <div class="grid grid-cols-2 gap-2">
                    @if ($paginator->onFirstPage())
                        <span class="inline-flex h-9 items-center justify-center rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm font-medium text-zinc-400">
                            {!! __('pagination.previous') !!}
                        </span>
                    @else
                        <button
                            type="button"
                            wire:click="previousPage('{{ $paginator->getPageName() }}')"
                            wire:loading.attr="disabled"
                            dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before"
                            class="inline-flex h-9 items-center justify-center rounded-lg border border-zinc-200 bg-white px-3 text-sm font-medium text-zinc-700 transition hover:border-zinc-300 hover:bg-zinc-50"
                        >
                            {!! __('pagination.previous') !!}
                        </button>
                    @endif

                    @if ($paginator->hasMorePages())
                        <button
                            type="button"
                            wire:click="nextPage('{{ $paginator->getPageName() }}')"
                            wire:loading.attr="disabled"
                            dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before"
                            class="inline-flex h-9 items-center justify-center rounded-lg border border-zinc-200 bg-white px-3 text-sm font-medium text-zinc-700 transition hover:border-zinc-300 hover:bg-zinc-50"
                        >
                            {!! __('pagination.next') !!}
                        </button>
                    @else
                        <span class="inline-flex h-9 items-center justify-center rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-sm font-medium text-zinc-400">
                            {!! __('pagination.next') !!}
                        </span>
                    @endif
                </div>
            </div>

            <div class="hidden items-center justify-between gap-4 sm:flex">
                <p class="text-sm font-medium tracking-tight text-zinc-600">
                    <span>{!! __('pagination.showing') !!}</span>
                    <span class="font-semibold text-zinc-900">{{ $paginator->firstItem() }}</span>
                    <span>{!! __('pagination.to') !!}</span>
                    <span class="font-semibold text-zinc-900">{{ $paginator->lastItem() }}</span>
                    <span>/</span>
                    <span class="font-semibold text-zinc-900">{{ $paginator->total() }}</span>
                    <span>{!! __('pagination.results') !!}</span>
                </p>

                <div class="flex items-center gap-1.5">
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-zinc-200 bg-zinc-50 text-zinc-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6" />
                            </svg>
                        </span>
                    @else
                        <button
                            type="button"
                            wire:click="previousPage('{{ $paginator->getPageName() }}')"
                            dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after"
                            rel="prev"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-zinc-200 bg-white text-zinc-600 transition hover:border-zinc-300 hover:bg-zinc-50 hover:text-zinc-900"
                            aria-label="{{ __('pagination.previous') }}"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6" />
                            </svg>
                        </button>
                    @endif

                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span aria-disabled="true" class="inline-flex h-9 min-w-[2.25rem] items-center justify-center rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 text-sm font-medium text-zinc-400">
                                {{ $element }}
                            </span>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                <span wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}">
                                    @if ($page == $paginator->currentPage())
                                        <span aria-current="page" class="inline-flex h-9 min-w-[2.25rem] items-center justify-center rounded-lg border border-zinc-900 bg-zinc-900 px-3 text-sm font-semibold text-white shadow-sm">
                                            {{ $page }}
                                        </span>
                                    @else
                                        <button
                                            type="button"
                                            wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                            class="inline-flex h-9 min-w-[2.25rem] items-center justify-center rounded-lg border border-zinc-200 bg-white px-3 text-sm font-medium text-zinc-700 transition hover:border-zinc-300 hover:bg-zinc-50 hover:text-zinc-900"
                                            aria-label="{{ __('pagination.go_to_page', ['page' => $page]) }}"
                                        >
                                            {{ $page }}
                                        </button>
                                    @endif
                                </span>
                            @endforeach
                        @endif
                    @endforeach

                    @if ($paginator->hasMorePages())
                        <button
                            type="button"
                            wire:click="nextPage('{{ $paginator->getPageName() }}')"
                            dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after"
                            rel="next"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-zinc-200 bg-white text-zinc-600 transition hover:border-zinc-300 hover:bg-zinc-50 hover:text-zinc-900"
                            aria-label="{{ __('pagination.next') }}"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6" />
                            </svg>
                        </button>
                    @else
                        <span aria-disabled="true" aria-label="{{ __('pagination.next') }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-zinc-200 bg-zinc-50 text-zinc-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6" />
                            </svg>
                        </span>
                    @endif
                </div>
            </div>
        </nav>
    @endif
</div>
