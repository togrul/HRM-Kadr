<div class="mx-auto flex w-full max-w-3xl flex-col gap-4 pb-20">
    <div class="sidemenu-title">
        <h2 class="text-xl font-title font-semibold text-gray-500" id="slide-over-title">
            {{ $title ?? '' }}
        </h2>
    </div>

    <x-surface-card
        :title="__('services::components.sections.main_fields')"
        class="bg-white shadow-none overflow-visible"
        bodyClass="overflow-visible"
        contentClass="p-3 overflow-visible"
    >
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="flex flex-col">
                <x-ui.select-dropdown
                    :label="__('services::common.labels.order')"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="component.order_type_id"
                    :model="$this->orderOptions"
                    search-model="searchOrder"
                />
                @error('component.order_type_id')
                    <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>

            <div class="flex flex-col">
                <x-ui.select-dropdown
                    :label="__('services::components.fields.given_rank')"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="component.rank_id"
                    :model="$this->rankOptions"
                />
            </div>

            <div class="flex flex-col">
                <x-label for="component.name">{{ __('services::common.labels.name') }}</x-label>
                <x-livewire-input mode="gray" name="component.name" wire:model="component.name" />
                @error('component.name')
                    <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>
    </x-surface-card>

    <x-surface-card
        :title="__('services::common.labels.content')"
        class="bg-white shadow-none"
        contentClass="p-3"
    >
        <div class="mb-2">
            <p class="text-xs text-zinc-500">{{ __('services::components.messages.content_help') }}</p>
        </div>
        <x-textarea
            mode="gray"
            name="component.content"
            placeholder="{{ __('services::components.placeholders.content') }}"
            wire:model.live.debounce.900ms="component.content"
            class="min-h-[150px]"
        />
        @error('component.content')
            <x-validation> {{ $message }} </x-validation>
        @enderror
    </x-surface-card>

    <x-surface-card
        :title="__('services::components.sections.dynamic_references')"
        class="bg-white shadow-none"
        contentClass="p-3"
    >
        <div class="mb-2 flex items-center justify-between gap-3">
            <div>
                <p class="text-xs text-zinc-500">{{ __('services::components.messages.dynamic_help') }}</p>
            </div>
            <button
                type="button"
                class="inline-flex h-8 items-center rounded-md border border-zinc-300 bg-white px-2.5 text-xs font-medium text-zinc-600 transition hover:bg-zinc-100"
                x-on:click="navigator.clipboard?.writeText(@js($this->dynamicFieldTokenString()))"
            >
                {{ __('services::components.actions.copy_all') }}
            </button>
        </div>

        <div class="flex flex-wrap gap-2">
            @forelse($this->dynamicFieldTokens() as $token)
                <div class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 bg-white px-2.5 py-1 text-xs font-medium text-zinc-700">
                    <span class="font-mono">{{ $token }}</span>
                    <button
                        type="button"
                        class="rounded p-0.5 text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700"
                        x-on:click="navigator.clipboard?.writeText(@js($token))"
                        title="{{ __('services::components.messages.copy') }}"
                    >
                        <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h8M8 7V5a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2h-2" />
                        </svg>
                    </button>
                </div>
            @empty
                <p class="text-xs text-zinc-500">{{ __('services::components.messages.no_dynamic_fields') }}</p>
            @endforelse
        </div>
    </x-surface-card>

    <x-surface-card
        :title="__('services::common.labels.title')"
        class="bg-white shadow-none"
        contentClass="p-3"
    >
        <div class="mb-2">
            <p class="text-xs text-zinc-500">{{ __('services::components.messages.title_help') }}</p>
        </div>
        <x-textarea
            mode="gray"
            name="component.title"
            placeholder="{{ __('services::components.placeholders.title') }}"
            wire:model.live.debounce.900ms="component.title"
            class="min-h-[92px]"
        />
        @error('component.title')
            <x-validation> {{ $message }} </x-validation>
        @enderror
    </x-surface-card>

    <div class="sticky bottom-0 z-20 -mx-1 border-t border-zinc-200 bg-white/95 px-1 pb-1 pt-3 backdrop-blur">
        <div class="flex items-center justify-end gap-2">
            <x-button mode="default" wire:click="$dispatch('ui:modal-close')">
                {{ __('services::common.actions.cancel') }}
            </x-button>

            <x-button
                mode="black"
                wire:click="store"
                wire:loading.attr="disabled"
                wire:target="store"
            >
                <svg wire:loading wire:target="store" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.37 0 0 5.37 0 12h4zm2 5.29A7.96 7.96 0 014 12H0c0 3.04 1.13 5.82 3 7.94l3-2.65z"></path>
                </svg>
                <span>{{ __('services::common.actions.save') }}</span>
            </x-button>
        </div>
    </div>
</div>
