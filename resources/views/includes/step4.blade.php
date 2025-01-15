<div class="grid grid-cols-5 gap-2">
    <div class="flex flex-col">
        <x-label for="labor_activities.company_name">{{ __('Company name') }}</x-label>
        <x-livewire-input mode="gray" name="labor_activities.company_name" wire:model="labor_activities.company_name"></x-livewire-input>
        @error('labor_activities.company_name')
            <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="labor_activities.position">{{ __('Position') }}</x-label>
        <x-livewire-input mode="gray" name="labor_activities.position" wire:model="labor_activities.position"></x-livewire-input>
        @error('labor_activities.position')
            <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="labor_activities.join_date">{{ __('Join date') }}</x-label>
        <x-pikaday-input mode="gray" name="labor_activities.join_date" format="Y-MM-DD" wire:model.live="labor_activities.join_date">
            <x-slot name="script">
              $el.onchange = function () {
              @this.set('labor_activities.join_date', $el.value);
              }
            </x-slot>
          </x-pikaday-input>
        @error('labor_activities.join_date')
            <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="labor_activities.leave_date">{{ __('Leave date') }}</x-label>
        <x-pikaday-input mode="gray" name="labor_activities.leave_date" format="Y-MM-DD" wire:model.live="labor_activities.leave_date">
            <x-slot name="script">
              $el.onchange = function () {
              @this.set('labor_activities.leave_date', $el.value);
              }
            </x-slot>
          </x-pikaday-input>
        @error('labor_activities.leave_date')
            <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="labor_activities.coefficient">{{ __('Coefficient') }}</x-label>
        <x-livewire-input mode="gray" type="number" name="labor_activities.coefficient" wire:model="labor_activities.coefficient"></x-livewire-input>
        @error('labor_activities.coefficient')
         <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
</div>

@if($isSpecialService)
<div class="grid grid-cols-3 gap-2">
    <div class="flex flex-col">
        <x-label for="labor_activities.order_given_by">{{ __('Order issued by') }}</x-label>
        <x-livewire-input mode="gray" name="labor_activities.order_given_by" wire:model="labor_activities.order_given_by"></x-livewire-input>
        @error('labor_activities.order_given_by')
            <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="labor_activities.order_no">{{ __('Order number') }}</x-label>
        <x-livewire-input mode="gray" name="labor_activities.order_no" wire:model="labor_activities.order_no"></x-livewire-input>
        @error('labor_activities.order_no')
            <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex items-start justify-between w-full">
        <div class="flex flex-col">
            <x-label for="labor_activities.order_date">{{ __('Order date') }}</x-label>
            <x-pikaday-input mode="gray" name="labor_activities.order_date" format="Y-MM-DD" wire:model.live="labor_activities.order_date">
                <x-slot name="script">
                    $el.onchange = function () {
                    @this.set('labor_activities.order_date', $el.value);
                    }
                </x-slot>
            </x-pikaday-input>
            @error('labor_activities.order_date')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col w-20">
            <x-label for="labor_activities.time">{{ __('Time') }}</x-label>
            <x-livewire-input mode="gray" name="labor_activities.time" wire:model="labor_activities.time" placeholder="12:00"></x-livewire-input>
        </div>
    </div>

</div>
@endif

<div class="flex justify-end space-x-4">
    <x-checkbox
        name="labor_activities.is_current"
        model="labor_activities.is_current"
    >
        {{ __('Is current?') }}
    </x-checkbox>
    <x-checkbox
        name="isSpecialService"
        model="isSpecialService"
    >
        {{ __('Military forces or law enforcement?') }}
    </x-checkbox>
    <x-button  mode="black" wire:click="addLaborActivity">{{ __('Add') }}</x-button>
</div>

<div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
    <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
        <x-table.tbl :headers="[__('Info'),__('Date'),__('Total'),__('Order'),'action']">
            @forelse ($labor_activities_list as $key => $laModel)
            <tr>
                <x-table.td standartWidth="true">
                    <div class="flex flex-col space-y-1">
                        <div class="flex items-center space-x-2">
                            <p class="text-sm font-medium text-gray-700">
                                {{ $laModel['company_name'] }}
                            </p>
                            @if($laModel['is_current'] ??= false)
                                <span class="flex items-center justify-center rounded-full w-4 h-4 bg-green-500 border-4 border-green-200">
                                </span>
                            @endif
                        </div>

                        <span class="text-sm font-medium text-emerald-500 bg-slate-100 rounded-lg px-3 py-1 w-max flex justify-center items-center">
                            {{ $laModel['position'] }}
                       </span>
                    </div>
                </x-table.td>

                <x-table.td>
                    <div class="flex flex-col space-y-1">
                        <div class="flex space-x-2 items-center">
                            <span class="text-sm font-medium text-gray-500">
                                {{ __('Join date') }}:
                            </span>
                            <span class="text-sm font-medium text-gray-700">
                                {{ \Carbon\Carbon::parse($laModel['join_date'])->format('d.m.Y') }}
                            </span>
                        </div>
                        <div class="flex space-x-2 items-center">
                            <span class="text-sm font-medium text-gray-500">
                                {{ __('Leave date') }}:
                            </span>
                            <div class="text-sm font-medium text-rose-500">
                                @if(!empty($laModel['leave_date']))
                                    {{ \Carbon\Carbon::parse($laModel['leave_date'])->format('d.m.Y') }}
                                @else
                                    <span class="flex items-center justify-center w-max px-2 py-1 text-sm font-medium bg-green-100 text-green-500 rounded-lg">
                                        {{ __('active') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                </x-table.td>
                <x-table.td>
                   <div class="flex flex-col">
                    <div class="flex space-x-2 items-center">
                        <span class="text-sm font-medium text-gray-500">
                            {{ __('Duration') }}:
                        </span>
                        <span class="text-sm font-medium text-gray-800">
                            {{ $calculatedData['data'][$key]['duration']['year'] }} {{ __('year') }}
                            {{ $calculatedData['data'][$key]['duration']['month'] }} {{ __('month') }}
                            ({{ $calculatedData['data'][$key]['duration']['diff'] }} {{ __('month') }})
                        </span>
                    </div>
                    @if(!empty($laModel['coefficient']))
                    <div class="flex space-x-2 items-center">
                        <span class="text-sm font-medium text-gray-500">
                            {{ __('Coefficient') }}:
                        </span>
                        <span class="text-sm font-medium text-blue-500">
                            {{ $laModel['coefficient'] }}
                        </span>
                    </div>
                    <div class="flex space-x-2 items-center">
                        <span class="text-sm font-medium text-gray-500">
                            {{ __('Total') }}:
                        </span>
                        <span class="text-sm font-medium text-green-500">
                            {{ $calculatedData['data'][$key]['duration']['duration'] }} {{ __('month') }}
                        </span>
                    </div>
                    @endif
                   </div>
                </x-table.td>
                <x-table.td>
                    @if(array_key_exists('is_special_service',$laModel) && $laModel['is_special_service'])
                        <div class="flex flex-col space-y-1">
                            <div class="flex space-x-2 items-center">
                                <span class="text-sm font-medium text-gray-500">
                                    {{ __('Issued by') }}:
                                </span>
                                <span class="text-sm font-medium text-gray-900">
                                    {{ $laModel['order_given_by'] }}
                                </span>
                            </div>
                            <div class="flex space-x-2 items-center">
                                <span class="text-sm font-medium text-gray-500">
                                    {{ __('Number') }} #:
                                </span>
                                <span class="text-sm font-medium text-blue-500">
                                    {{ $laModel['order_no'] }}
                                </span>
                            </div>
                            <div class="flex space-x-2 items-center">
                                <span class="text-sm font-medium text-gray-500">
                                    {{ __('Date') }}:
                                </span>
                                <span class="text-sm font-medium text-gray-700">
                                    {{ \Carbon\Carbon::parse($laModel['order_date'])->format('d.m.Y') }}
                                </span>
                            </div>
                        </div>
                    @endif
                </x-table.td>
                <x-table.td :isButton="true">
                     <button
                        onclick="confirm('Are you sure you want to remove this data?') || event.stopImmediatePropagation()"
                        wire:click="forceDeleteLaborActivity({{ $key }})"
                        class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                    >
                         @include('components.icons.force-delete')
                     </button>
                </x-table.td>
            </tr>
            @empty
            <tr>
                <td colspan="5">
                   <div class="flex justify-center items-center py-4">
                        <span class="font-medium">{{ __('No information added') }}</span>
                   </div>
                </td>
            </tr>
            @endforelse
        </x-table.tbl>
    </div>


    <div class="my-2 flex justify-between items-center border border-gray-100 p-2 shadow-sm bg-gray-50 rounded-lg">
            @if (!empty($laModel))
                <div class="flex flex-col space-y-1 items-center">
                    <span class="font-medium text-gray-600 bg-gray-200 px-3 py-1 rounded-lg">
                        {{ __('Old seniority') }}
                    </span>
                    <div class="flex items-center space-x-2 text-sm self-start">
                        <span class="font-medium text-teal-500">{{ __('Property') }}:</span>
                        <span class="font-medium text-gray-900">
                            {{ $calculatedData['sum_month_old'] }} {{ __('month') }}
                            ({{ $calculatedData['sum_old']['year'] }} {{ __('year') }}
                            {{ $calculatedData['sum_old']['month'] }} {{ __('month') }})
                        </span>
                    </div>
                    <div class="flex items-center space-x-2 text-sm self-start">
                        <span class="font-medium text-rose-500">{{ __('Military') }}:</span>
                        <span class="font-medium text-gray-900">
                            {{ $calculatedData['sum_month_military_old'] }} {{ __('month') }}
                            ({{ $calculatedData['sum_old_military']['year'] }} {{ __('year') }}
                            {{ $calculatedData['sum_old_military']['month'] }} {{ __('month') }})
                        </span>
                    </div>
                </div>
            @endif
            <div class="flex flex-col space-y-1 items-center">
                <span class="font-medium text-gray-600 bg-gray-200 px-3 py-1 rounded-lg">{{ __('Current seniority') }}</span>
                <div class="flex items-center space-x-2 text-sm self-start">
                    <span class="font-medium text-teal-500">{{ __('Standart') }}:</span>
                    <span class="font-medium text-gray-900">
                            {{ $calculatedData['sum_month_current_diff'] }} {{ __('month') }}
                            ({{ $calculatedData['sum_current_diff']['year'] }} {{ __('year') }}
                            {{ $calculatedData['sum_current_diff']['month'] }} {{ __('month') }})
                    </span>
                </div>
                <div class="flex items-center space-x-2 text-sm self-start">
                    <span class="font-medium text-blue-500">{{ __('Coefficient') }}:</span>
                    <span class="font-medium text-gray-900">
                            {{ $calculatedData['sum_month_current'] }} {{ __('month') }}
                            ({{ $calculatedData['sum_current']['year'] }} {{ __('year') }}
                            {{ $calculatedData['sum_current']['month'] }} {{ __('month') }})
                    </span>
                </div>
            </div>
            @if (!empty($laModel))
                <div class="flex flex-col space-y-1 items-center">
                    <span class="font-medium text-gray-600 bg-gray-200 px-3 py-1 rounded-lg">
                        {{ __('Total seniority') }}
                    </span>
                    <div class="flex items-center space-x-2 text-sm self-start">
                        <span class="font-medium text-teal-500">{{ __('Property') }}:</span>
                        <span class="font-medium text-gray-900">
                            {{ $calculatedData['sum_total'] }} {{ __('month') }}
                            ({{ $calculatedData['sum_total_full']['year'] }} {{ __('year') }}
                            {{ $calculatedData['sum_total_full']['month'] }} {{ __('month') }})
                        </span>
                    </div>
                    <div class="flex items-center space-x-2 text-sm self-start">
                        <span class="font-medium text-rose-500">{{ __('Military') }}:</span>
                        <span class="font-medium text-gray-900">
                            {{ $calculatedData['sum_total_military'] }} {{ __('month') }}
                            ({{ $calculatedData['sum_total_military_full']['year'] }} {{ __('year') }}
                            {{ $calculatedData['sum_total_military_full']['month'] }} {{ __('month') }})
                        </span>
                    </div>
                </div>
           @endif
    </div>

    </div>
</div>

<hr>

<div class="step-section__title">
    <h1>{{ __('Ranks') }}</h1>
</div>

<div class="grid grid-cols-4 gap-3">
    <div class="flex flex-col">
        <x-select-list class="w-full" :title="__('Ranks')" mode="gray" :selected="$rankName" name="rankId">
            <x-livewire-input  @click.stop="open = true" mode="gray" name="searchRank" wire:model.live="searchRank"></x-livewire-input>

            <x-select-list-item wire:click="setData('ranks','rank_id','rank','---',null)" :selected="'---' == $rankName"
              wire:model='ranks.rank_id.id'>
              ---
            </x-select-list-item>
            @foreach($rankModel as $rnk)
                <x-select-list-item wire:click="setData('ranks','rank_id','rank','{{ $rnk->name }}',{{ $rnk->id }})"
                                    :selected="$rnk->id === $rankId"
                                    wire:model='ranks.rank_id.id'
                >
                    {{ $rnk->name }}
                </x-select-list-item>
            @endforeach
        </x-select-list>
        @error('ranks.rank_id.id')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        @php
            $selectedName = array_key_exists('rank_reason_id',$ranks) ? $ranks['rank_reason_id']['name'] : '---';
            $selectedId = array_key_exists('rank_reason_id',$ranks) ? $ranks['rank_reason_id']['id'] : -1;
        @endphp
        <x-select-list class="w-full" :title="__('Rank reasons')" mode="gray" :selected="$selectedName" name="rankReasonId">
            <x-select-list-item wire:click="setData('ranks','rank_reason_id',null,'---',null)"
                                :selected="'---' ==  $selectedName"
                                wire:model='ranks.rank_reason_id.id'>
                ---
            </x-select-list-item>
            @foreach($rankReasons as $reason)
                <x-select-list-item wire:click="setData('ranks','rank_reason_id',null,'{{ trim($reason->name) }}',{{ $reason->id }})"
                                    :selected="$reason->id === $selectedId"
                                    wire:model='ranks.rank_reason_id.id'>
                    {{ $reason->name }}
                </x-select-list-item>
            @endforeach
        </x-select-list>
        @error('ranks.rank_reason_id.id')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="ranks.name">{{ __('Name') }}</x-label>
        <x-livewire-input mode="gray" name="ranks.name" wire:model="ranks.name"></x-livewire-input>
        @error('ranks.name')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="ranks.given_date">{{ __('Given date') }}</x-label>
        <x-pikaday-input mode="gray" name="ranks.given_date" format="Y-MM-DD" wire:model.live="ranks.given_date">
            <x-slot name="script">
              $el.onchange = function () {
              @this.set('ranks.given_date', $el.value);
              }
            </x-slot>
          </x-pikaday-input>
          @error('ranks.given_date')
          <x-validation> {{ $message }} </x-validation>
          @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="ranks.order_given_by">{{ __('Order issued by') }}</x-label>
        <x-livewire-input mode="gray" name="ranks.order_given_by" wire:model="ranks.order_given_by"></x-livewire-input>
        @error('ranks.order_given_by')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="ranks.order_no">{{ __('Order number') }}</x-label>
        <x-livewire-input mode="gray" name="ranks.order_no" wire:model="ranks.order_no"></x-livewire-input>
        @error('ranks.order_no')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="ranks.order_date">{{ __('Order date') }}</x-label>
        <x-pikaday-input mode="gray" name="ranks.order_date" format="Y-MM-DD" wire:model.live="ranks.order_date">
            <x-slot name="script">
                $el.onchange = function () {
                @this.set('ranks.order_date', $el.value);
                }
            </x-slot>
        </x-pikaday-input>
        @error('ranks.order_date')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
</div>

<div class="flex justify-end">
    <x-button  mode="black" wire:click="addRank">{{ __('Add') }}</x-button>
</div>

<div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
    <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
        <x-table.tbl :headers="[__('Rank'),__('Name'),__('Given date'),'action']">
            @forelse ($rank_list as $keyRank => $rModel)
            <tr>
                <x-table.td>
                    <div class="flex flex-col space-y-1">
                        <span class="text-sm font-medium flex justify-center items-center px-1 py-1 rounded-md border border-gray-300 bg-gray-50 text-emerald-600 w-max">
                            {{ $rModel['rank_id']['name'] }}
                       </span>
                        <span class="text-xs font-medium flex justify-center items-center px-1 py-1 rounded-md border border-gray-300 bg-gray-50 text-gray-600 w-max">
                            {{ $rModel['rank_reason_id']['name'] }}
                       </span>
                    </div>
                </x-table.td>
                <x-table.td>
                    <span class="text-sm font-medium text-gray-700">
                        {{ $rModel['given_date']}}
                   </span>
                </x-table.td>
                <x-table.td>
                    <div class="flex flex-col space-y-1">
                        <div class="flex space-x-2 items-center">
                             <span class="text-sm font-medium text-gray-500">
                                    {{ __('Issued by') }}:
                             </span>
                            <span class="text-sm font-medium text-gray-900">
                                    {{ $rModel['order_given_by'] }}
                            </span>
                        </div>
                        <div class="flex space-x-2 items-center">
                            <span class="text-sm font-medium text-gray-500">
                                {{ __('Number') }} #:
                            </span>
                            <span class="text-sm font-medium text-blue-500">
                                {{ $rModel['order_no'] }}
                            </span>
                        </div>
                        <div class="flex space-x-2 items-center">
                            <span class="text-sm font-medium text-gray-500">
                                {{ __('Date') }}:
                            </span>
                            <span class="text-sm font-medium text-gray-700">
                                {{ \Carbon\Carbon::parse($rModel['order_date'])->format('d.m.Y') }}
                            </span>
                        </div>
                    </div>
                </x-table.td>
                <x-table.td :isButton="true">
                     <button
                        onclick="confirm('Are you sure you want to remove this data?') || event.stopImmediatePropagation()"
                        wire:click="forceDeleteRank({{ $keyRank }})"
                        class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                    >
                         <x-icons.force-delete></x-icons.force-delete>
                     </button>
                </x-table.td>
            </tr>
            @empty
            <tr>
                <td colspan="5">
                   <div class="flex justify-center items-center py-4">
                        <span class="font-medium">{{ __('No information added') }}</span>
                   </div>
                </td>
            </tr>
            @endforelse
        </x-table.tbl>
    </div>
    </div>
</div>

