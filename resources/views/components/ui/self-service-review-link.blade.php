<a
    href="{{ route('self-service-reviews') }}"
    {{ $attributes->merge([
        'class' => 'group inline-flex h-12 items-center gap-3 rounded-2xl border border-cyan-200 bg-white/95 px-4 shadow-sm ring-1 ring-cyan-100/80 transition-all duration-200 hover:-translate-y-0.5 hover:border-cyan-300 hover:bg-cyan-50/70 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-200',
    ]) }}
>
    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-cyan-50 text-cyan-600 transition-colors duration-200 group-hover:bg-cyan-100 group-hover:text-cyan-700">
        <x-icons.self-service-review-icon size="w-4 h-4" color="text-cyan-600" hover="text-cyan-700" />
    </span>

    <span class="min-w-0">
        <span class="block text-[10px] font-semibold uppercase tracking-[0.2em] text-cyan-700/80">
            {{ __('ui::menu.shortcuts.review_queue') }}
        </span>
        <span class="mt-0.5 block whitespace-nowrap text-sm font-semibold tracking-tight text-zinc-800">
            {{ __('ui::menu.items.self_service_reviews') }}
        </span>
    </span>

    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-zinc-50 text-zinc-400 transition-colors duration-200 group-hover:bg-white group-hover:text-cyan-700">
        <x-icons.chevron-right-icon size="w-4 h-4" color="text-zinc-400" hover="text-cyan-700" />
    </span>
</a>
