<div class="sidemenu-title">
    <h2 class="text-2xl font-title font-semibold text-gray-500" id="slide-over-title">
        {{ $title ?? ''}}
    </h2>
</div>

<div class="grid grid-cols-1 gap-2 sm:grid-cols-3 mt-4">
    <div class="flex flex-col">
        <x-select-list class="w-full" :title="__('Order')" mode="gray" :selected="$orderName" name="orderId">
            <x-livewire-input  @click.stop="open = true" mode="gray" name="searchOrder" wire:model.live="searchOrder"></x-livewire-input>

            <x-select-list-item wire:click="setData('component','order_type_id','order','---',null)" :selected="'---' == $orderName"
                                wire:model='component.order_type_id.id'>
                ---
            </x-select-list-item>
            @foreach($_orders as $_order)
                <x-select-list-item wire:click="setData('component','order_type_id','order','{{ $_order->name }}',{{ $_order->id }})"
                                    :selected="$_order->id === $orderId" wire:model='component.order_type_id.id'>
                    {{ $_order->name }}
                </x-select-list-item>
            @endforeach
        </x-select-list>
        @error('component.order_type_id.id')
            <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>

    <div class="flex flex-col">
        @php
            $selectedName = array_key_exists('rank_id',$this->component) ? $this->component['rank_id']['name'] : '---';
            $selectedId = array_key_exists('rank_id',$this->component) ? $this->component['rank_id']['id'] : -1;
        @endphp
        <x-select-list class="w-full" :title="__('Given rank')" mode="gray" :selected="$selectedName" name="rankId">
            <x-select-list-item wire:click="setData('component','rank_id',null,'---',null)" :selected="'---' ==  $selectedName"
                                wire:model='component.rank_id.id'>
                ---
            </x-select-list-item>
            @foreach($_ranks as $_rank)
                <x-select-list-item wire:click="setData('component','rank_id',null,'{{ trim($_rank->name) }}',{{ $_rank->id }})"
                                    :selected="$_rank->id === $selectedId" wire:model='component.rank_id.id'>
                    {{ $_rank->name }}
                </x-select-list-item>
            @endforeach
        </x-select-list>
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
                    wire:model.live="component.content"></x-textarea>
        @error('component.content')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="component.dynamic_fields">{{ __('Dynamic fields') }}</x-label>
        <x-textarea mode="gray" name="component.dynamic_fields" placeholder="{{__('')}}"
                    wire:model="component.dynamic_fields"></x-textarea>
    </div>
</div>

<div class="flex justify-between items-end w-full">
    <x-modal-button>{{ __('Save') }}</x-modal-button>
</div>
