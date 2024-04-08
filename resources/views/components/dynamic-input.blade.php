@props([
    'list', // arrayin adi
    'field', // hansi columndursa
    'title', // inputun basligi
    'type', // deyisenin adi
    'model' => null, //  list olanda foreach ucun model
    'key', // hansi key e aid datadir
    'selectedName' => null,
    'searchField' => null,
    'isCoded' => false,
    'row',
    'disabled' => false
])

@php
    $input = match ($type)
    {
        '$structure_main','$position','$fullname','$rank' => 'select',
        '$month','$name','$surname' => 'text-input',
        '$day','$year' => 'numeric-input',
        '$structure' => 'radio-list'
    };

    $list_string = 'components';
@endphp

{{--{{$isCoded ? 'true' : 'false'}}--}}
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
        @php
            ${$selectedName."Name"} = array_key_exists($key,$list) ? $list[$key][$field]['name'] : '---';
            ${$selectedName."Id"} = array_key_exists($key,$list) ? $list[$key][$field]['id'] : -1;
        @endphp
        <x-select-list class="w-full" :$disabled :$title mode="gray" :selected="${$selectedName.'Name'}" name="{{ $field }}Id">
            @if(!empty($searchField))
                <x-livewire-input  @click.stop="open = true" mode="gray" name="{{ $searchField }}" wire:model.live="{{ $searchField }}"></x-livewire-input>
            @endif

            <x-select-list-item wire:click="setData('{{ $list_string }}','{{ $field }}',null,'---',null,{{ $key }})" :selected="'---' ==  ${$selectedName.'Name'}"
                                wire:model='{{ $list_string }}.{{ $key }}.{{ $field }}.id'>
                ---
            </x-select-list-item>
            @foreach($model as $model_item)
                @php
                    $_optionValue = ($model_item->fullname_max ?? $model_item->name);
                @endphp
                <x-select-list-item wire:click="setData('{{ $list_string }}','{{ $field }}',null,'{{ $_optionValue }}',{{ $model_item->id }},{{ $key }});$dispatch('dynamicSelectChanged',{value: {{ $model_item->id }},field: '{{ $field }}',rowKey: {{ $key }} })"
                                    :selected="$model_item->id === ${$selectedName.'Id'}" wire:model='{{ $list_string }}.{{ $key }}.{{ $field }}.id'>
                    {{ $_optionValue  }}
                </x-select-list-item>
            @endforeach
        </x-select-list>
        @error("{$list_string}.{$key}.{$field}.id")
            <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
@elseif($input == 'radio-list')
    <div class="flex flex-col space-y-1"
         x-data="{showStructures: false}"
    >
        <x-label for="order.order_no">{{ $title }}</x-label>
        <div class="relative w-full">
            <button @click="showStructures = !showStructures"
                    class="appearance-none flex justify-center items-center w-full rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium"
            >
                {{ array_key_exists($field,$this->{$list_string}[$key]) ? $this->{$list_string}[$key][$field]['name'] : __('Structure') }}
            </button>
            @if(!$disabled)
            <div x-show="showStructures"
                 x-transition:enter="transition ease-in-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-y-0 -translate-y-1/2"
                 x-transition:enter-end="opacity-100 transform scale-y-100 translate-x-0"
                 x-transition:leave="transition ease-in-out duration-300"
                 x-transition:leave-start="opacity-100 transform scale-y-100 translate-y-0"
                 x-transition:leave-end="opacity-0 transform scale-y-0 -translate-y-1/2"
                 class="z-[99999] flex px-4 py-3 bg-neutral-50 border border-gray-200 shadow-xl rounded absolute top-9 {{ $row % 3 == 0 ? 'left-0' : 'right-0' }} w-full sm:max-w-xl md:max-w-screen-sm lg:max-w-screen-md min-w-full sm:w-screen "
            >
                <x-radio-tree.list>
                    @php
                        $wordSuffixService = new \App\Services\WordSuffixService();
                    @endphp
                    @foreach($model->where('parent_id',$this->{$list_string}[$key]['structure_main_id']['id']) as $model_item)
                        @php
                            $_level_name = strtolower((collect(\App\Enums\StructureEnum::cases())->pluck('name','value')[$model_item->level]));

                            $_select_value = ($field == 'structure_id' && $isCoded)
                                            ? $model_item->code."{$wordSuffixService->getNumberSuffix($model_item->code)} {$_level_name}"
                                            : $model_item->name;
                        @endphp
                        <x-radio-tree.item :$isCoded :listData="$list_string" :$field :model="$model_item" :$key>
                            {{ $_select_value }}
                        </x-radio-tree.item>
                    @endforeach
                </x-radio-tree.list>
            </div>
                @endif
        </div>

    </div>
@endif



