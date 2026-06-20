@props([
    'state' => 'showDeleteConfirmation',
    'dialog' => 'deleteConfirmation',
    'confirmAction' => 'runConfirmedDeletion',
    'cancelAction' => 'closeDeleteConfirmation',
    'target' => 'runConfirmedDeletion',
])

@php
    use App\Support\Translations\ModuleTranslation;

    $dialogState = data_get($this, $dialog, []);
    $title = ModuleTranslation::resolveStoredText((string) data_get($dialogState, 'title', 'ui::common.destructive.title'));
    $message = ModuleTranslation::resolveStoredText((string) data_get($dialogState, 'message', ''));
    $description = ModuleTranslation::resolveStoredText((string) data_get($dialogState, 'description', 'ui::common.destructive.description'));
    $confirmLabel = ModuleTranslation::resolveStoredText((string) data_get($dialogState, 'confirm_label', 'ui::common.actions.delete'));
@endphp

<div
    x-data="{ open: $wire.entangle('{{ $state }}').live }"
    x-on:keydown.escape.window="open && $wire.call('{{ $cancelAction }}')"
    class="relative"
>
    <template x-teleport="body">
        <div
            x-cloak
            x-show="open"
            x-transition.opacity
            class="fixed inset-0 z-[80] flex items-center justify-center px-4 py-6 sm:px-6"
            role="dialog"
            aria-modal="true"
            aria-labelledby="delete-confirmation-title"
        >
            <div class="absolute inset-0 bg-zinc-950/45 backdrop-blur-[3px]" @click="$wire.call('{{ $cancelAction }}')" aria-hidden="true"></div>

            <div x-show="open" x-transition.scale.origin.center class="relative z-10 w-full max-w-xl">
                <div class="overflow-hidden rounded-[22px] border border-zinc-200 bg-zinc-100/90 shadow-[0_24px_80px_rgba(15,23,42,0.18)]">
                    <div class="border-b border-zinc-200 px-1 pb-1 pt-1">
                        <div class="rounded-[18px] border border-zinc-200 bg-white px-6 py-5 sm:px-7">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex min-w-0 items-start gap-4">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-rose-200 bg-rose-50 text-rose-600">
                                        <x-icons.delete-icon color="text-rose-500" hover="text-rose-500" />
                                    </div>
                                    <div class="min-w-0 space-y-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="inline-flex items-center rounded-full border border-zinc-200 bg-zinc-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-zinc-500">
                                                {{ __('ui::common.destructive.impact') }}
                                            </span>
                                            <span class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-[11px] font-medium text-rose-600">
                                                {{ __('ui::common.actions.delete') }}
                                            </span>
                                        </div>

                                        <div class="space-y-2">
                                            <h2 id="delete-confirmation-title" class="text-xl font-semibold tracking-tight text-slate-700">
                                                {{ $title }}
                                            </h2>

                                            @if (filled($message))
                                                <p class="text-sm leading-6 text-zinc-600">
                                                    {{ $message }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700"
                                    aria-label="{{ __('ui::common.actions.close') }}"
                                    @click="$wire.call('{{ $cancelAction }}')"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18 18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            @if (filled($description))
                                <div class="mt-5 grid gap-3 sm:grid-cols-[auto,1fr]">
                                    <div class="inline-flex h-fit items-center rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-700">
                                        {{ __('ui::common.destructive.description') }}
                                    </div>
                                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50/90 px-4 py-3 text-sm leading-6 text-zinc-600 shadow-[0_1px_2px_rgba(16,24,40,0.04)]">
                                        {{ $description }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-end sm:px-6">
                        <button
                            type="button"
                            class="camelcase inline-flex h-10 items-center justify-center gap-2 whitespace-nowrap rounded-md border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-zinc-700 shadow-sm transition-colors duration-150 hover:bg-zinc-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-300"
                            @click="$wire.call('{{ $cancelAction }}')"
                        >
                            {{ __('ui::common.actions.cancel') }}
                        </button>

                        <button
                            type="button"
                            wire:click="{{ $confirmAction }}"
                            wire:loading.attr="disabled"
                            wire:target="{{ $target }}"
                            class="camelcase inline-flex h-10 items-center justify-center gap-2 whitespace-nowrap rounded-md border border-rose-500 bg-rose-50 px-4 py-2 text-sm font-medium text-rose-600 shadow-sm transition-colors duration-150 hover:bg-rose-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-200 disabled:pointer-events-none disabled:opacity-50"
                        >
                            <svg wire:loading wire:target="{{ $target }}" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                                <path class="opacity-75" d="M4 12a8 8 0 0 1 8-8v3a5 5 0 0 0-5 5H4z" fill="currentColor"></path>
                            </svg>
                            <span>{{ $confirmLabel }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
