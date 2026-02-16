@props([
    'label' => null,
    'searchModel',              // e.g. "personSearch"
    'selected' => null,         // e.g. ['id' => 1, 'name' => 'Jane'] or null
    'displayKey' => 'name',
    'idKey' => 'id',
    'onClear' => null,          // e.g. "clearPerson"
    'placeholder' => '',
    'clearField' => null,       // optional: pass a field name instead of id
])

@php
    $inputId = $attributes->get('id', $searchModel);
    $listboxId = $inputId . '_listbox';
@endphp

<div x-data="{ open: false }" class="flex flex-col relative">
    @if(!empty($label))
        <x-label for="{{ $inputId }}">{{ __($label) }}</x-label>
    @endif

    @if($selected)
        <div class="flex items-center gap-2">
            <x-livewire-input
                id="{{ $inputId }}_display"
                name="{{ $inputId }}_display"
                mode="gray"
                class="flex-auto"
                :value="$selected[$displayKey] ?? ''"
                readonly
                disabled
            />
            @if($onClear)
                @php
                    $clearPayload = !is_null($clearField)
                        ? json_encode($clearField)
                        : json_encode($selected[$idKey] ?? null);
                @endphp
                <button type="button" class="appearance-none flex-none w-max" wire:click="{{ $onClear }}({{ $clearPayload }})">
                    <x-icons.close-icon color="text-rose-600" size="w-8 h-8" hover="text-rose-700" />
                </button>
            @endif
        </div>
    @else
        <x-livewire-input
            id="{{ $inputId }}"
            mode="gray"
            name="{{ $searchModel }}"
            placeholder="{{ $placeholder }}"
            wire:model.live.debounce.300ms="{{ $searchModel }}"
            role="combobox"
            aria-autocomplete="list"
            aria-controls="{{ $listboxId }}"
            x-bind:aria-expanded="open.toString()"
            x-on:click.stop="open = true"
            x-on:keydown.escape.stop="open = false"
        />
    @endif

    <div
        x-cloak
        x-show="open"
        x-transition.opacity.scale
        x-on:click.outside="open = false"
        x-on:click.away="open = false"
        x-on:mousedown.outside="open = false"
        id="{{ $listboxId }}"
        role="listbox"
        class="absolute z-[99] top-[60px] left-0 w-full px-1 py-2 bg-neutral-50 rounded-lg border border-neutral-200 drop-shadow-md flex flex-col max-h-40 overflow-y-auto"
    >
        {{ $slot }}
    </div>
</div>
