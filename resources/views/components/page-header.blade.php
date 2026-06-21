@props([
    'title',
    'count' => null,
    'countLabel' => null,
    'guideTitle' => null,
    'guideDescription' => null,
    'guideUrl' => null,
])

{{--
    Premium module page header (matches the Orders list header). Pair it with the
    x-pill-button component for consistent action buttons. Named slots: icon, actions.
    The default slot renders filters / tabs inside the same card.
--}}

<div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-[0_1px_2px_rgba(16,24,40,0.04)]">
    {{-- header row --}}
    <div class="flex flex-col gap-3 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:px-5">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-zinc-800 to-black text-white shadow-sm">
                @isset($icon)
                    {{ $icon }}
                @else
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                @endisset
            </div>
            <div class="leading-tight">
                <h1 class="text-[16px] font-semibold tracking-tight text-zinc-900">{{ $title }}</h1>
                @if ($count !== null)
                    <p class="text-[12px] text-zinc-400 tabular-nums">{{ $count }}{{ $countLabel ? ' '.$countLabel : '' }}</p>
                @endif
            </div>
        </div>

        @isset($actions)
            <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>

    {{-- optional guide strip --}}
    @if ($guideTitle)
        @php $isExternal = $guideUrl && \Illuminate\Support\Str::startsWith($guideUrl, ['http', '#']); @endphp
        <{{ $guideUrl ? 'a' : 'div' }}
            @if ($guideUrl) href="{{ $guideUrl }}" @unless ($isExternal) wire:navigate @endunless @endif
            class="flex items-center gap-2 border-y border-zinc-100 bg-zinc-50/60 px-4 py-2 text-[12px] text-zinc-500 sm:px-5 {{ $guideUrl ? 'transition hover:bg-zinc-50' : '' }}">
            <svg class="h-3.5 w-3.5 shrink-0 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span class="font-medium text-zinc-600">{{ $guideTitle }}</span>
            @if ($guideDescription)
                <span class="hidden truncate sm:inline">{{ $guideDescription }}</span>
            @endif
        </{{ $guideUrl ? 'a' : 'div' }}>
    @endif

    {{-- optional body (filters / tabs) rendered inside the same card --}}
    @if (trim($slot) !== '')
        <div class="px-4 py-3.5 sm:px-5">{{ $slot }}</div>
    @endif
</div>
