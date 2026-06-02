@props([
    'translationNs',
    'sectionKey',
    'searchModel',
    'searchField',
    'searchPlaceholderKey',
    'searchHintKey',
    'actions' => [],
])

<div class="space-y-4">
    <div class="grid gap-4 lg:grid-cols-[minmax(0,0.75fr)_minmax(18rem,0.65fr)] lg:items-start">
        <div class="space-y-2">
            <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __($translationNs.'.sections.'.$sectionKey) }}</x-ui.field-label>
            <p class="max-w-2xl text-sm leading-6 text-zinc-500">{{ __($translationNs.'.messages.'.$searchHintKey) }}</p>
        </div>

        <x-ui.input-shell :label="__($translationNs.'.fields.'.$searchField)" labelClass="tracking-tight text-zinc-500">
            <x-ui.filter-input wire:model.live.debounce.300ms="{{ $searchModel }}" type="text" placeholder="{{ __($translationNs.'.messages.'.$searchPlaceholderKey) }}" />
        </x-ui.input-shell>
    </div>

    <div class="flex flex-wrap gap-2">
        @foreach ($actions as $action)
            <button type="button" wire:click="{{ $action['method'] }}" class="inline-flex items-center justify-center rounded-2xl bg-[#f5f5f7] px-4 py-2.5 text-sm font-semibold tracking-tight text-zinc-800 shadow-[inset_0_1px_0_rgba(255,255,255,0.8),0_8px_18px_rgba(0,0,0,0.035)] transition hover:bg-zinc-950 hover:text-white">
                {{ __($translationNs.'.actions.'.$action['label']) }}
            </button>
        @endforeach
    </div>
</div>
