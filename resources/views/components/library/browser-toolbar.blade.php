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
            <input wire:model.live.debounce.300ms="{{ $searchModel }}" type="text" placeholder="{{ __($translationNs.'.messages.'.$searchPlaceholderKey) }}" class="w-full rounded-2xl border border-zinc-200 bg-white px-3 py-2.5 text-sm text-zinc-800 placeholder:text-zinc-400 focus:border-zinc-300 focus:outline-none" />
        </x-ui.input-shell>
    </div>

    <div class="flex flex-wrap gap-2">
        @foreach ($actions as $action)
            <button type="button" wire:click="{{ $action['method'] }}" class="inline-flex items-center justify-center rounded-2xl border border-zinc-200 bg-white px-4 py-2.5 text-sm font-semibold tracking-tight text-zinc-700 transition hover:border-zinc-300 hover:bg-zinc-50">
                {{ __($translationNs.'.actions.'.$action['label']) }}
            </button>
        @endforeach
    </div>
</div>
