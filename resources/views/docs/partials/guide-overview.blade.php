{{-- The guide's start page: every module as a card (straight from GuideRegistry, so a new guide
     appears here by itself), then the general rules from the overview markdown. --}}
<section id="overview-modules" class="scroll-mt-24">
    <div class="flex items-baseline justify-between gap-4">
        <h2 class="text-[17px] font-bold text-ink">Bütün modullar</h2>
        <span class="text-[12.5px] text-ink-faint">{{ count($modules) }} bələdçi</span>
    </div>

    <div class="mt-4 grid gap-2.5 sm:grid-cols-2">
        @foreach ($modules as $moduleKey => $entry)
            <a href="{{ route('docs.guide', ['focus' => $moduleKey]) }}" class="group flex items-center gap-3 rounded-xl border border-hairline bg-white px-4 py-3 transition hover:border-zinc-300 hover:bg-[#fafafa]">
                <span class="h-2 w-2 shrink-0 rounded-full {{ $toneDots[$entry['tone']] ?? 'bg-zinc-400' }}"></span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-[14px] font-semibold text-ink">{{ $entry['label'] }}</span>
                    <span class="block text-[12px] text-ink-faint">{{ $sectionCounts[$moduleKey] ?? 0 }} bölmə</span>
                </span>
                <svg class="h-4 w-4 shrink-0 text-zinc-300 transition group-hover:translate-x-0.5 group-hover:text-ink" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </a>
        @endforeach
    </div>
</section>

<section class="mt-12">
    <div class="guide-prose">
        {!! $html !!}
    </div>
</section>
