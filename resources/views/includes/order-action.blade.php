@inject('getDynamicFieldOptions', 'App\Services\GenerateDynamicFieldsService')

@php
    $service = $getDynamicFieldOptions->handle();
@endphp

<div class="flex flex-col space-y-4" x-data="{showPersonnelList : -1}">
    <div class="sidemenu-title">
        <h2 class="text-2xl font-title font-semibold text-gray-500" id="slide-over-title">
            {{ $title ?? ''}}
        </h2>
    </div>

    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 mt-4">
        <div class="flex flex-col">
            @php
                $selectedName = array_key_exists('order_type_id',$order) ? $order['order_type_id']['name'] : '---';
                $selectedId = array_key_exists('order_type_id',$order) ? $order['order_type_id']['id'] : -1;
            @endphp
            <x-select-list class="w-full" :title="__('Template')" mode="gray" :selected="$selectedName" name="templateId" :disabled="$orderModel">
                <x-livewire-input  @click.stop="open = true" mode="gray" name="searchTemplate" wire:model.live="searchTemplate"></x-livewire-input>

                <x-select-list-item wire:click="setData('order','order_type_id',null,'---',null);$dispatch('templateSelected',{value: -1})" :selected="'---' ==  $selectedName"
                                    wire:model='order.order_type_id.id'>
                    ---
                </x-select-list-item>
                @foreach($_templates as $_template)
                    <x-select-list-item wire:click="setData('order','order_type_id',null,'{{ trim($_template->name) }}',{{ $_template->id }});$dispatch('templateSelected',{ value: {{  $_template->id }} })"
                                        :selected="$_template->id === $selectedId" wire:model='order.order_type_id.id'>
                        {{ $_template->name }}
                    </x-select-list-item>
                @endforeach
            </x-select-list>
            @error('order.order_type_id.id')
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>

        <div class="">
            <x-label for="order.order_no">{{ __('Order #') }}</x-label>
            <x-livewire-input mode="gray"  name="order.order_no" wire:model="order.order_no"></x-livewire-input>
            @error('order.order_no')
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
        <div class="">
            <x-label for="order.given_by">{{ __('Given by') }}</x-label>
            <x-livewire-input mode="gray" disabled="true" name="order.given_by" wire:model="order.given_by"></x-livewire-input>
        </div>
        <div class="">
            <x-label for="order.given_by_rank">{{ __('Rank') }}</x-label>
            <x-livewire-input mode="gray" disabled="true" name="order.given_by_rank" wire:model="order.given_by_rank"></x-livewire-input>
        </div>
        <div class="">
            <x-label for="order.given_date">{{ __('Given date') }}</x-label>
            <x-pikaday-input mode="gray" name="order.given_date" format="Y-MM-DD" wire:model.live="order.given_date">
                <x-slot name="script">
                    $el.onchange = function () {
                    @this.set('order.given_date', $el.value);
                    }
                </x-slot>
            </x-pikaday-input>
            @error('order.given_date')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
    </div>

    @if($showComponent)

        @for($i = 0; $i < $componentRows; $i++)
            <div class="grid grid-cols-1 gap-2 border-2 border-slate-200 border-dashed px-4 py-3 rounded-lg relative">
                @if(($i+1) > count($selectedBlade == 'default' ? $originalComponents : Arr::except($this->originalComponents,'personnels')))
                <button class="flex justify-center items-center rounded-lg p-1 shadow-sm absolute right-0 top-0 bg-slate-50 text-rose-500"
                        wire:click="deleteRow"
                >
                    <svg data-slot="icon" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"></path>
                    </svg>
                </button>
                @endif
                <div class="flex flex-col space-y-2">
                    <div class="flex flex-col w-1/2">
                        @php
                            $componentName = array_key_exists($i,$components) ? $components[$i]['component_id']['name'] : '---';
                            $componentId = array_key_exists($i,$components) ? $components[$i]['component_id']['id'] : -1;
                        @endphp
                        <x-select-list class="w-full" :title="__('Select component')" mode="gray" :selected="$componentName" name="componentId">
                            <x-select-list-item wire:click="setData('components','component_id',null,'---',null,{{ $i }});$dispatch('componentSelected')" :selected="'---' ==  $componentName"
                                                wire:model='components.component_id.id'>
                                ---
                            </x-select-list-item>
                            @foreach($_components as $_component_item)
                                <x-select-list-item wire:click="
                                                        setData('components','component_id',null,'{{ $_component_item->name }}',{{ $_component_item->id }},{{ $i }});
                                                        $dispatch('componentSelected',{ value: {{  $_component_item }}, rowKey: {{ $i }} })
                                                    "
                                                    :selected="$_component_item->id === $componentId"
                                                    wire:model='components.component_id.id'
                                >
                                    {{ $_component_item->name }}
                                </x-select-list-item>
                            @endforeach
                        </x-select-list>
                        @error("components.{$i}.component_id.id")
                            <x-validation> {{ $message }} </x-validation>
                        @enderror
                    </div>
                </div>
                    {{--secilen emre gore doldurululan fieldleri auto generasiya etmek--}}
                    @include("includes.order-templates.{$selectedBlade}")
            </div>
        @endfor

        <div class="flex justify-center items-center">
            <button class="rounded-lg shadow-sm bg-gray-100 text-slate-900 px-6 py-2 font-medium text-sm flex justify-center items-center space-x-2" wire:click="addRow">
                <svg data-slot="icon" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path>
                </svg>
                <span class="uppercase">{{ __('Add') }}</span>
            </button>
        </div>
        @endif


    <div class="grid grid-cols-1">
        <div class="flex flex-col space-y-1">
            <x-label for="personnel.gender">{{ __('Status') }}</x-label>
            <div class="flex flex-row">
                @foreach($_statuses as $_status)
                    <label class="inline-flex items-center bg-gray-100 rounded shadow-sm py-2 px-2">
                        <input type="radio" class="form-radio" name="order.status_id" wire:model="order.status_id" value={{ $_status->id }}>
                        <span class="ml-2 text-sm font-normal">{{ $_status->name }}</span>
                    </label>
                @endforeach
            </div>
            @error('order.status_id')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
    </div>

    <div class="flex justify-between items-end w-full">
        <x-modal-button>{{ __('Save') }}</x-modal-button>
    </div>


    <div>
        <x-modal-info
            livewire-event-to-open-modal="checkVacancyWasSet"
            :modal-title="__('Vacancy error')"
        ></x-modal-info>
    </div>
</div>


