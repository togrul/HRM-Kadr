<div class="flex items-start justify-between gap-4 border-b border-hairline-subtle px-5 py-4">
    <div class="min-w-0">
        <p class="hrm-eyebrow">{{ __('compensation::dashboard.kicker') }}</p>
        <h2 id="compensation-panel-title" class="mt-1.5 text-[17px] font-semibold tracking-[-0.025em] text-ink">{{ $panelTitle }}</h2>
    </div>

    <x-pill-button x-ref="closeButton" :icon="true" x-on:click="close()" title="{{ __('compensation::dashboard.actions.close') }}">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
    </x-pill-button>
</div>
