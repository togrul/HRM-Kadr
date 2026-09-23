{{-- Shortcut to the self-service review queue; a plain secondary control, like its neighbours. --}}
<a
    href="{{ route('self-service-reviews') }}"
    wire:navigate
    title="{{ __('ui::menu.shortcuts.review_queue') }}"
    {{ $attributes->merge([
        'class' => 'inline-flex h-10 items-center gap-2 whitespace-nowrap rounded-[10px] border border-hairline bg-[#f4f4f5] px-4 text-[14px] font-semibold tracking-[-0.01em] text-ink-soft transition hover:border-zinc-300 hover:bg-[#e4e4e7] hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400 focus-visible:ring-offset-2',
    ]) }}
>
    <span class="hrm-icon flex h-4 w-4 shrink-0 items-center justify-center text-ink-faint">
        <x-icons.self-service-review-icon size="w-4 h-4" color="text-current" hover="text-current" />
    </span>
    <span>{{ __('ui::menu.items.self_service_reviews') }}</span>
</a>
