@props([
    'label',
    'options' => [],
    'searchModel',
    'placeholder' => '---',
])

<div class="flex flex-col">
    <x-ui.select-dropdown
        :label="$label"
        :placeholder="$placeholder"
        mode="gray"
        class="w-full"
        :model="$options"
        {{ $attributes }}
    >
        <x-livewire-input
            mode="gray"
            :name="$searchModel"
            wire:model.live.debounce.300ms="{{ $searchModel }}"
            @click.stop="isOpen = true"
            x-on:input.stop="null"
            x-on:keyup.stop="null"
            x-on:keydown.stop="null"
            x-on:change.stop="null"
        />
    </x-ui.select-dropdown>
</div>

