@props([
    'title' => 'Məlumat gözlənilir',
    'message' => null,
    'compact' => false,
])

<div @class([
    'overflow-hidden rounded-[1.7rem] border border-zinc-200/90 bg-[linear-gradient(180deg,#ffffff_0%,#fcfcfd_100%)] shadow-[0_12px_28px_rgba(15,23,42,0.05)]',
    'p-4' => $compact,
    'p-5' => ! $compact,
])>
    <div class="flex items-start justify-between gap-4 border-b border-zinc-100 pb-4">
        <div class="space-y-1">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-zinc-400">{{ __('reports::dashboard.labels.report_preview') }}</p>
            <h4 class="text-base font-semibold tracking-tight text-zinc-950">{{ $title }}</h4>
            @if ($message)
                <p class="text-sm leading-6 text-zinc-500">{{ $message }}</p>
            @endif
        </div>
        <span class="inline-flex shrink-0 items-center rounded-full border border-zinc-200/90 bg-zinc-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-zinc-500 shadow-[inset_0_1px_0_rgba(255,255,255,0.65)]">
            {{ __('reports::dashboard.labels.empty_state') }}
        </span>
    </div>

    <div class="space-y-4 pt-4">
        @foreach ([88, 64, 72] as $index => $width)
            <div class="space-y-2.5 rounded-[1.2rem] border border-zinc-100/80 bg-zinc-50/60 px-3 py-3">
                <div class="flex items-center justify-between gap-3">
                    <span class="h-3 rounded-full bg-zinc-100" style="width: {{ 120 - ($index * 12) }}px"></span>
                    <span class="h-3 rounded-full bg-zinc-100" style="width: {{ 46 - ($index * 4) }}px"></span>
                </div>
                <div class="h-[16px] rounded-md border border-zinc-100 bg-zinc-50 px-[5px] py-[4px]">
                    <div
                        class="h-full rounded-[3px] bg-[repeating-linear-gradient(90deg,#111827_0_7px,transparent_7px_11px)] opacity-20"
                        style="width: {{ $width }}%"
                    ></div>
                </div>
            </div>
        @endforeach
    </div>
</div>
