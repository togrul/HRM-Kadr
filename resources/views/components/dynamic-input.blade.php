@props([
    'list', // array values (kept for BC)
    'field', // hansi columndursa
    'title', // inputun basligi
    'type', // deyisenin adi
    'model' => null, //  list olanda foreach ucun model
    'key', // hansi key e aid datadir
    'selectedName' => null,
    'searchField' => null,
    'isCoded' => false, // kodu yoxsa tam adi gelsin
    'row',
    'disabled' => false,
    'listProperty' => 'componentForms',
    'selectedLabel' => null,
    'selectedValue' => null,
    'suffixService' => null,
    'structureLevels' => null,
    'inputTypes' => null,
    'selectedResolver' => null,
])

@php
    $resolvedInputTypes = $inputTypes ?? [
        '$structure_main' => 'select',
        '$position' => 'select',
        '$fullname' => 'select',
        '$rank' => 'select',
        '$transportation' => 'select',
        '$month' => 'text-input',
        '$name' => 'text-input',
        '$surname' => 'text-input',
        '$days' => 'text-input',
        '$location' => 'text-input',
        '$trip_start_month' => 'text-input',
        '$meeting_hour' => 'text-input',
        '$return_month' => 'text-input',
        '$car' => 'text-input',
        '$weapon' => 'text-input',
        '$day' => 'numeric-input',
        '$year' => 'numeric-input',
        '$trip_start_day' => 'numeric-input',
        '$trip_start_year' => 'numeric-input',
        '$return_day' => 'numeric-input',
        '$structure' => 'radio-list',
        '$start_date' => 'date-input',
        '$end_date' => 'date-input',
    ];

    $input = $resolvedInputTypes[$type] ?? 'text-input';
    $list_string = $listProperty;
    $suffixService = \App\Support\StructureSelect::suffixService($suffixService);
    $structureLevels = \App\Support\StructureSelect::levels($structureLevels);
    $resolveSelected = $selectedResolver ?? function ($component, $field, $key, $defaultLabel = null, $defaultId = null) use ($list_string) {
        $selectedId = \App\Support\StructureSelect::resolveSelected($component, $list_string, $key, $field, $defaultId);
        $fallbackValue = data_get($component->{$list_string}[$key] ?? [], $field);
        $fieldLabel = $defaultLabel ?? (is_array($fallbackValue)
                ? ($fallbackValue['name'] ?? __('Structure'))
                : (! empty($fallbackValue) ? $fallbackValue : __('Structure')));

        return [$selectedId, $fieldLabel];
    };

    $resolvedSearchModel = blank($searchField) ? null : $searchField;
@endphp

@if($input == 'text-input')
    <div class="">
        <x-label for="{{ $list_string }}.{{ $key }}.{{ $field }}">{{ $title }}</x-label>
        <x-livewire-input disabled="{{ $disabled }}" mode="gray" name="{{ $list_string }}.{{ $key }}.{{ $field }}" wire:model="{{ $list_string }}.{{ $key }}.{{ $field }}"></x-livewire-input>
        @error("{$list_string}.{$key}.{$field}")
            <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
@elseif($input == 'numeric-input')
    <div>
        <x-label for="{{ $list_string }}.{{ $key }}.{{ $field }}">{{ $title }}</x-label>
        <x-livewire-input :$disabled mode="gray" type="number" name="{{ $list_string }}.{{ $key }}.{{ $field }}" wire:model="{{ $list_string }}.{{ $key }}.{{ $field }}"></x-livewire-input>
        @error("{$list_string}.{$key}.{$field}")
            <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
@elseif($input == 'select')
    <div class="flex flex-col">
        <x-ui.select-dropdown
            :label="$title"
            placeholder="---"
            mode="gray"
            class="w-full"
            wire:model.live="{{ $list_string }}.{{ $key }}.{{ $field }}"
            :model="$model ?? []"
            :disabled="$disabled"
            :search-model="$resolvedSearchModel"
            :selected-label="$selectedLabel"
        />
        @error("{$list_string}.{$key}.{$field}")
            <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
@elseif($input == 'radio-list')
    <div class="flex flex-col space-y-1"
         x-data="{showStructures: false}"
    >
        <x-label for="orderForm.order_no">{{ $title }}</x-label>
        @php
            [$selectedId, $fieldLabel] = $resolveSelected($this, $field, $key, $selectedLabel, $selectedValue);
        @endphp
        <div class="relative w-full" x-data="{showStructures: false, openNodes: $store.structureTree ?? ($store.structureTree = {})}">
            <button @click="showStructures = !showStructures"
                    class="flex items-center justify-center w-full px-4 py-2 text-sm font-medium bg-gray-100 rounded-lg appearance-none"
            >
                {{ $fieldLabel }}
            </button>
            @if(!$disabled)
            <div x-show="showStructures"
                 x-transition:enter="transition ease-in-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-y-0 -translate-y-1/2"
                 x-transition:enter-end="opacity-100 transform scale-y-100 translate-x-0"
                 x-transition:leave="transition ease-in-out duration-300"
                 x-transition:leave-start="opacity-100 transform scale-y-100 translate-y-0"
                 x-transition:leave-end="opacity-0 transform scale-y-0 -translate-y-1/2"
                 class="z-40 flex px-4 py-3 bg-neutral-50 border border-gray-200 shadow-xl rounded absolute top-9 {{ ($row % 3) === 0 ? 'left-0' : 'right-0' }} w-full sm:max-w-xl md:max-w-screen-sm lg:max-w-screen-md min-w-full sm:w-screen "
            >
                <x-radio-tree.list>
                    @php
                        $mainStructureId = data_get($this->{$list_string}[$key] ?? [], 'structure_main_id');
                    @endphp
                    @foreach($model?->where('parent_id', $mainStructureId) ?? [] as $model_item)
                        @php
                            $_level_name = __($structureLevels[$model_item->level] ?? '');
                            $_select_value = ($field == 'structure_id' && $isCoded)
                                                    ? $model_item->code."{$suffixService->getNumberSuffix($model_item->code)} {$_level_name}"
                                                    : $model_item->name;         
                        @endphp
                        <x-radio-tree.item
                            :$isCoded
                            :listData="$list_string"
                            :$field
                            :model="$model_item"
                            :$key
                            :selected-id="$selectedId"
                            :suffix-service="$suffixService"
                            :structure-levels="$structureLevels"
                        >
                            {{ __($_select_value) }}
                        </x-radio-tree.item>
                    @endforeach
                </x-radio-tree.list>
            </div>
            @endif
        </div>

    </div>
    @elseif($input == 'date-input')
    <div class="flex flex-col">
        <x-label for="{{ $list_string }}.{{ $key }}.{{ $field }}">{{ $title }}</x-label>
        <x-pikaday-input mode="gray" name="{{ $list_string }}.{{ $key }}.{{ $field }}" format="Y-MM-DD" wire:model.live="{{ $list_string }}.{{ $key }}.{{ $field }}">
            <x-slot name="script">
                $el.onchange = function () {
                @this.set('{{ $list_string }}.{{ $key }}.{{ $field }}', $el.value);
                }
            </x-slot>
        </x-pikaday-input>
        @error("{{ $list_string }}.{{ $key }}.{{ $field }}")
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>

@endif
