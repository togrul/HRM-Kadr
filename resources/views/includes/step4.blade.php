@inject('calculateSeniority', 'App\Services\CalculateSeniorityService')

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
            <x-label for="labor_activities.order_no">{{ __('Time') }}</x-label>
            <x-livewire-input mode="gray" name="labor_activities.time" wire:model="labor_activities.time" placeholder="12:00"></x-livewire-input>
        </div>
    </div>

</div>
@endif

<div class="flex justify-end space-x-4">
    <x-checkbox name="isSpecialService" model="isSpecialService">{{ __('Military forces or law enforcement?') }}</x-checkbox>
    <x-button  mode="black" wire:click="addLaborActivity">{{ __('Add') }}</x-button>
</div>

<div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
    <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
        <x-table.tbl :headers="[__('Info'),__('Date'),__('Total'),__('Order'),'action']">
            @php
                $total_duration = 0;
            @endphp
            @forelse ($labor_activities_list as $key => $laModel)
            <tr>
                <x-table.td standartWidth="true">
                    <div class="flex flex-col space-y-1">
                        <p class="text-sm font-medium text-gray-700">
                            {{ $laModel['company_name'] }}
                        </p>
                        <span class="text-sm font-medium text-emerald-500 bg-slate-100 rounded-lg px-3 py-1 w-max flex justify-center items-center">
                            {{ $laModel['position'] }}
                       </span>
                    </div>
                </x-table.td>

                <x-table.td>
                    <div class="flex flex-col">
                        <div class="flex space-x-2 items-center">
                            <span class="text-sm font-medium text-gray-500">
                                {{ __('Join date') }}:
                            </span>
                            <span class="text-sm font-medium text-gray-700">
                                {{ $laModel['join_date']}}
                            </span>
                        </div>
                        <div class="flex space-x-2 items-center">
                            <span class="text-sm font-medium text-gray-500">
                                {{ __('Leave date') }}:
                            </span>
                            <span class="text-sm font-medium text-rose-500">
                                {{ $laModel['leave_date']}}
                            </span>
                        </div>
                    </div>

                </x-table.td>
                <x-table.td>
                   <div class="flex flex-col">
                    <div class="flex space-x-2 items-center">
                        @php
                            $data = $calculateSeniority->calculate($laModel['join_date'],$laModel['leave_date'],$laModel['coefficient']);
                            $total_duration += $data['duration'];
                        @endphp
                        <span class="text-sm font-medium text-gray-500">
                            {{ __('Duration') }}:
                        </span>
                        <span class="text-sm font-medium text-gray-800">
                            {{ $data['year'] }} {{ __('year') }} {{ $data['month'] }} {{ __('month') }}  ({{ $data['diff'] }} {{ __('month') }})
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
                            {{ $data['duration'] }} {{ __('month') }}
                        </span>
                    </div>
                    @endif
                   </div>
                </x-table.td>
                <x-table.td>
                    @if($laModel['is_special_service'])
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
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-red-500">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                        </svg>
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
            @php
                $calc_year_month_old = $calculateSeniority->calculateYearAndMonth($total_duration);
            @endphp
            @if (!empty($laModel))
                <div class="flex flex-col space-y-1 items-center">
                    <span class="font-medium text-gray-600 bg-gray-200 px-3 py-1 rounded-lg">{{ __('Old seniority') }}</span>
                    <span class="font-medium text-gray-900">{{ $total_duration }} {{ __('month') }}</span>
                    <span class="font-medium text-gray-900">{{  $calc_year_month_old['year'] }} {{ __('year') }} {{ $calc_year_month_old['month'] }} {{ __('month') }}</span>
                </div>
            @endif
            @php
                $currentData = $calculateSeniority->calculate($personnel['join_work_date'],$personnel['leave_work_date'],$_settings['Work coefficient']);
                $calc_current_year_month = $calculateSeniority->calculateYearAndMonth($currentData['duration']);
                $calc_year_month = $calculateSeniority->calculateYearAndMonth($total_duration  + $currentData['duration']);
            @endphp
            <div class="flex flex-col space-y-1 items-center">
                <span class="font-medium text-gray-600 bg-gray-200 px-3 py-1 rounded-lg">{{ __('Current seniority') }}</span>
                <div class="flex space-x-1 items-center">
                    <span class="font-medium text-gray-600">{{ $currentData['diff'] }} {{ __('month') }}</span>
                    (<span class="font-medium text-rose-500">x2</span>
                    <span class="font-medium text-gray-900">{{ $currentData['duration'] }} {{ __('month') }}</span>)
                </div>

                <div class="flex space-x-1 items-center">
                    <span class="font-medium text-gray-600">{{ $currentData['year'] }} {{ __('year') }} {{ $currentData['month'] }} {{ __('month') }}</span>
                    (<span class="font-medium text-rose-500">x2</span>
                    <span class="font-medium text-gray-900">{{ $calc_current_year_month['year'] }} {{ __('year') }} {{ $calc_current_year_month['month'] }} {{ __('month') }}</span>)
                </div>
            </div>
            @if (!empty($laModel))
                <div class="flex flex-col space-y-1 items-center">
                    <span class="font-medium text-gray-600 bg-gray-200 px-3 py-1 rounded-lg">{{ __('Total seniority') }}</span>
                    <span class="font-medium text-blue-500">{{ $total_duration +  $currentData['duration']   }} {{ __('month') }}</span>
                    <span class="font-medium text-blue-500">{{ $calc_year_month['year'] }} {{ __('year') }} {{ $calc_year_month['month'] }} {{ __('month') }}</span>
                </div>
           @endif
    </div>

    </div>
</div>

<hr>

<div class="w-full rounded-xl px-4 py-3 bg-gray-100 font-semibold text-xl flex justify-center">
    <h1>{{ __('Ranks') }}</h1>
</div>

<div class="grid grid-cols-3 gap-2">
    <div class="flex flex-col">
        <x-select-list class="w-full" :title="__('Ranks')" mode="gray" :selected="$rankName" name="rankId">
            <x-livewire-input  @click.stop="open = true" mode="gray" name="searchRank" wire:model.live="searchRank"></x-livewire-input>

            <x-select-list-item wire:click="setData('ranks','rank_id','rank','---',null)" :selected="'---' == $rankName"
              wire:model='ranks.rank_id.id'>
              ---
            </x-select-list-item>
            @foreach($rankModel as $rnk)
                <x-select-list-item wire:click="setData('ranks','rank_id','rank','{{ $rnk->name }}',{{ $rnk->id }})"
                :selected="$rnk->id === $rankId" wire:model='ranks.rank_id.id'>
                    {{ $rnk->name }}
                </x-select-list-item>
            @endforeach
        </x-select-list>
        @error('ranks.rank_id.id')
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
</div>
<div class="flex justify-end">
    <x-button  mode="black" wire:click="addRank">{{ __('Add') }}</x-button>
</div>

<div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
    <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
        <x-table.tbl :headers="[__('Rank'),__('Name'),__('Given date'),'action']">
            @forelse ($rank_list as $key => $rModel)
            <tr>
                <x-table.td>
                    <span class="text-sm font-medium text-gray-700">
                        {{ $rModel['rank_id']['name'] }}
                   </span>
                </x-table.td>
                <x-table.td>
                    <span class="text-sm font-medium text-gray-700">
                        {{ $rModel['name'] }}
                   </span>
                </x-table.td>
                <x-table.td>
                    <span class="text-sm font-medium text-gray-700">
                        {{ $rModel['given_date']}}
                   </span>
                </x-table.td>
                <x-table.td :isButton="true">
                     <button
                        onclick="confirm('Are you sure you want to remove this data?') || event.stopImmediatePropagation()"
                        wire:click="forceDeleteRank({{ $key }})"
                        class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-red-500">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                        </svg>
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

