<div class="sidemenu-title">
    <h2 class="text-xl font-title font-semibold text-gray-500" id="slide-over-title">
        {{ $title ?? ''}}
    </h2>
</div>

<div class="grid grid-cols-1 gap-2 sm:grid-cols-3 mt-4">
    <div class="flex flex-col">
        <x-ui.select-dropdown
            :label="__('Order')"
            placeholder="---"
            mode="gray"
            class="w-full"
            wire:model.live="component.order_type_id"
            :model="$this->orderOptions"
                    search-model="searchOrder"
        >
        </x-ui.select-dropdown>
        @error('component.order_type_id')
            <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>

    <div class="flex flex-col">
        <x-ui.select-dropdown
            :label="__('Given rank')"
            placeholder="---"
            mode="gray"
            class="w-full"
            wire:model.live="component.rank_id"
            :model="$this->rankOptions"
        />
    </div>

    <div class="">
        <x-label for="component.name">{{ __('Name') }}</x-label>
        <x-livewire-input mode="gray"  name="component.name" wire:model="component.name"></x-livewire-input>
        @error('component.name')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
</div>

<div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
    <div class="flex flex-col">
        <x-label for="component.content">{{ __('Content') }}</x-label>
        <x-textarea mode="gray" name="component.content" placeholder="{{__('')}}"
                    wire:model.live.debounce.900ms="component.content"></x-textarea>
        @error('component.content')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="component.dynamic_fields">{{ __('Dynamic fields') }}</x-label>
        <x-textarea mode="gray" name="component.dynamic_fields" placeholder="{{__('')}}"
                    wire:model="component.dynamic_fields"></x-textarea>
    </div>
    <div class="flex flex-col">
        <x-label for="component.title">{{ __('Title') }}</x-label>
        <x-textarea mode="gray" name="component.title" placeholder="{{__('')}}"
                    wire:model.live.debounce.900ms="component.title"></x-textarea>
    </div>
</div>

<div class="flex justify-between items-end w-full">
    <x-modal-button>{{ __('Save') }}</x-modal-button>
</div>
