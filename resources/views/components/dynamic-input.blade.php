@props([
    'list',
    'field',
    'title',
    'type',
    'model' => null,
    'key',
    'selectedName' => null,
    'searchField' => null,
    'isCoded' => false
])

{{--list -> arrayin adi , field -> hansi columndursa , title -> inputun basligi, type -> deyisenin adi , model -> list olanda foreach ucun model, key->hansi key e aid datadir--}}

@php
    $input = match ($type)
    {
        '$structure_main','$structure','$position','$fullname','$rank' => 'select',
        '$month','$name','$surname' => 'text-input',
        '$day','$year' => 'numeric-input'
    };

    $list_string = 'components';
@endphp

@if($input == 'text-input')
    <div class="">
        <x-label for="{{ $list_string }}.{{ $key }}.{{ $field }}">{{ $title }}</x-label>
        <x-livewire-input mode="gray" name="{{ $list_string }}.{{ $key }}.{{ $field }}" wire:model="{{ $list_string }}.{{ $key }}.{{ $field }}"></x-livewire-input>
        @error("{$list_string}.{$key}.{$field}")
            <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
@elseif($input == 'numeric-input')
    <div>
        <x-label for="{{ $list_string }}.{{ $key }}.{{ $field }}">{{ $title }}</x-label>
        <x-livewire-input mode="gray" type="number" name="{{ $list_string }}.{{ $key }}.{{ $field }}" wire:model="{{ $list_string }}.{{ $key }}.{{ $field }}"></x-livewire-input>
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
        <x-select-list class="w-full" :$title mode="gray" :selected="${$selectedName.'Name'}" name="{{ $field }}Id">
            @if(!empty($searchField))
                <x-livewire-input  @click.stop="open = true" mode="gray" name="{{ $searchField }}" wire:model.live="{{ $searchField }}"></x-livewire-input>
            @endif

            <x-select-list-item wire:click="setData('{{ $list_string }}','{{ $field }}',null,'---',null,{{ $key }})" :selected="'---' ==  ${$selectedName.'Name'}"
                                wire:model='{{ $list_string }}.{{ $key }}.{{ $field }}.id'>
                ---
            </x-select-list-item>
            @foreach($model as $model_item)
                <x-select-list-item wire:click="setData('{{ $list_string }}','{{ $field }}',null,'{{ $model_item->fullname_max ?? $model_item->name }}',{{ $model_item->id }},{{ $key }});$dispatch('dynamicSelectChanged',{value: {{ $model_item->id }},field: '{{ $field }}',rowKey: {{ $key }} })"
                                    :selected="$model_item->id === ${$selectedName.'Id'}" wire:model='{{ $list_string }}.{{ $key }}.{{ $field }}.id'>
                    {{ ($isCoded && $field == 'structure_id') ? $model_item->code : $model_item->fullname_max ?? $model_item->name  }}
                </x-select-list-item>
            @endforeach
        </x-select-list>
        @error("{$list_string}.{$key}.{$field}.id")
            <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
@endif



