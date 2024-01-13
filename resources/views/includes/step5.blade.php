<div class="grid grid-cols-2 gap-2">
    <div class="flex flex-col">
        <x-select-list class="w-full" :title="__('Ranks')" mode="gray" :selected="$militaryRankName" name="militaryRankId">
            <x-livewire-input  @click.stop="open = true" mode="gray" name="searchMilitaryRank" wire:model.live="searchMilitaryRank"></x-livewire-input>

            <x-select-list-item wire:click="setData('military','rank_id','militaryRank','---',null)" :selected="'---' == $militaryRankName"
              wire:model='military.rank_id.id'>
              ---
            </x-select-list-item>
            @if(!empty($rankModel))
                @foreach($rankModel as $mr)
                <x-select-list-item wire:click="setData('military','rank_id','militaryRank','{{ $mr->name }}',{{ $mr->id }})"
                :selected="$mr->id === $militaryRankId" wire:model='military.rank_id.id'>
                    {{ $mr->name }}
                </x-select-list-item>
                @endforeach
            @endif
        </x-select-list>
        @error('military.rank_id.id')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="military.attitude_to_military_service">{{ __('Attitude to military service') }}</x-label>
        <x-livewire-input mode="gray" name="military.attitude_to_military_service" wire:model="military.attitude_to_military_service"></x-livewire-input>
        @error('military.attitude_to_military_service')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
</div>
<div class="grid grid-cols-3 gap-2">
    <div class="flex flex-col">
        <x-label for="military.given_date">{{ __('Given date') }}</x-label>
        <x-pikaday-input mode="gray" name="military.given_date" format="Y-MM-DD" wire:model.live="military.given_date">
            <x-slot name="script">
              $el.onchange = function () {
              @this.set('military.given_date', $el.value);
              }
            </x-slot>
          </x-pikaday-input>
          @error('military.given_date')
          <x-validation> {{ $message }} </x-validation>
          @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="military.start_date">{{ __('Start date') }}</x-label>
        <x-pikaday-input mode="gray" name="military.start_date" format="Y-MM-DD" wire:model.live="military.start_date">
            <x-slot name="script">
              $el.onchange = function () {
              @this.set('military.start_date', $el.value);
              }
            </x-slot>
          </x-pikaday-input>
    </div>
    <div class="flex flex-col">
        <x-label for="military.end_date">{{ __('End date') }}</x-label>
        <x-pikaday-input mode="gray" name="military.end_date" format="Y-MM-DD" wire:model.live="military.end_date">
            <x-slot name="script">
              $el.onchange = function () {
              @this.set('military.end_date', $el.value);
              }
            </x-slot>
          </x-pikaday-input>
    </div>
</div>

<div class="flex justify-end">
    <x-button  mode="black" wire:click="addMilitary">{{ __('Add') }}</x-button>
</div>

<div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
    <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
        <x-table.tbl :headers="[__('Attitude'),__('Rank'),__('Date'),'action']">
            @forelse ($military_list as $key => $msModel)
            <tr>
                <x-table.td>
                    <span class="text-sm font-medium text-gray-700">
                        {{ $msModel['attitude_to_military_service'] }}
                   </span>
                </x-table.td>
                <x-table.td>
                   <span class="text-sm font-medium text-gray-700">
                        {{ $msModel['rank_id']['name'] }}
                    </span>
                </x-table.td>
                <x-table.td>
                    <div class="flex flex-col">
                        <div class="flex space-x-2">
                            <span class="text-sm text-gray-500 font-medium">{{ __('Given date') }}:</span>
                            <span class="text-sm font-medium text-gray-700">
                                {{ $msModel['given_date'] }}
                           </span>
                        </div>
                        @if(array_key_exists('start_date',$msModel))
                        <div class="flex space-x-2">
                            <span class="text-sm text-gray-500 font-medium">{{ __('Start date') }}:</span>
                            <span class="text-sm font-medium text-gray-700">
                                {{ $msModel['start_date'] }}
                           </span>
                        </div>
                        @endif
                        @if(array_key_exists('end_date',$msModel))
                        <div class="flex space-x-2">
                            <span class="text-sm text-gray-500 font-medium">{{ __('End date') }}:</span>
                            <span class="text-sm font-medium text-gray-700">
                                {{ $msModel['end_date'] }}
                           </span>
                        </div>
                        @endif
                    </div>
                </x-table.td>
                <x-table.td :isButton="true">
                     <button
                        onclick="confirm('Are you sure you want to remove this data?') || event.stopImmediatePropagation()"
                        wire:click="forceDeleteMilitary({{ $key }})"
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

<div class="w-full rounded-xl px-4 py-3 bg-gray-100 font-semibold text-xl flex justify-center">
    <h1>{{ __('Injuries') }}</h1>
</div>

<div class="grid grid-cols-3 gap-2">
    <div class="flex flex-col">
        <x-label for="injuries.injury_type">{{ __('Injury type') }}</x-label>
        <div class="flex flex-row">
            <label class="inline-flex items-center bg-gray-100 rounded shadow-sm py-2 px-2 w-full">
                <input type="radio" class="form-radio" name="injuries.injury_type" wire:model="injuries.injury_type" value="other">
                <span class="ml-2 text-sm font-normal">{{__('Other')}}</span>
            </label>
            <label class="inline-flex items-center ml-4 bg-gray-100 rounded shadow-sm py-2 px-2 w-full">
                <input type="radio" class="form-radio" name="injuries.injury_type" wire:model="injuries.injury_type" value="contusion">
                <span class="ml-2 text-sm font-normal">{{__('Contusion')}}</span>
            </label>
        </div>
        @error('injuries.injury_type')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="injuries.location">{{ __('Location') }}</x-label>
        <x-livewire-input mode="gray" name="injuries.location" wire:model="injuries.location"></x-livewire-input>
        @error('injuries.location')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="injuries.date_time">{{ __('Date') }}</x-label>
        <x-pikaday-input mode="gray" name="injuries.date_time" format="Y-MM-DD" wire:model.live="injuries.date_time">
            <x-slot name="script">
                $el.onchange = function () {
                @this.set('injuries.date_time', $el.value);
                }
            </x-slot>
        </x-pikaday-input>
        @error('injuries.date_time')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
</div>

<div class="grid grid-cols-1">
    <div class="flex flex-col">
        <x-label for="injuries.description">{{ __('Description') }}</x-label>
        <x-textarea mode="gray" name="injuries.description" placeholder="{{__('')}}"
                    wire:model="injuries.description"></x-textarea>
    </div>
</div>

<div class="flex justify-end">
    <x-button  mode="black" wire:click="addInjury">{{ __('Add') }}</x-button>
</div>

<div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
        <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
            <x-table.tbl :headers="[__('Type'),__('Location and date'),__('Description'),'action']">
                @forelse ($injury_list as $key => $injuryModel)
                    <tr>
                        <x-table.td>
                            @if(!empty($injuryModel['injury_type']))
                            <span class="text-sm font-medium text-gray-700">
                                {{ __($injuryModel['injury_type']) }}
                           </span>
                            @endif
                        </x-table.td>
                        <x-table.td>
                            <div class="flex flex-col space-y-1">
                                 <span class="text-sm font-medium text-gray-600">
                                    {{ $injuryModel['location'] }}
                                </span>
                                <span class="text-sm font-medium text-gray-900">
                                    {{ \Carbon\Carbon::parse($injuryModel['date_time'])->format('d.m.Y') }}
                                </span>
                            </div>
                        </x-table.td>
                        <x-table.td>
                            <span class="text-sm font-medium text-gray-700">
                                {{ $injuryModel['description']}}
                           </span>
                        </x-table.td>
                        <x-table.td :isButton="true">
                            <button
                                onclick="confirm('Are you sure you want to remove this data?') || event.stopImmediatePropagation()"
                                wire:click="forceDeleteInjury({{ $key }})"
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

<div class="w-full rounded-xl px-4 py-3 bg-gray-100 font-semibold text-xl flex justify-center">
    <h1>{{ __('Been captivity') }}</h1>
</div>

<div class="grid grid-cols-4 gap-2">
    <div class="flex flex-col">
        <x-label for="captivity.location">{{ __('Location') }}</x-label>
        <x-livewire-input mode="gray" name="captivity.location" wire:model="captivity.location"></x-livewire-input>
        @error('captivity.location')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="captivity.condition">{{ __('Condition') }}</x-label>
        <x-livewire-input mode="gray" name="captivity.condition" wire:model="captivity.condition"></x-livewire-input>
        @error('captivity.condition')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="captivity.taken_captive_date">{{ __('Taken date') }}</x-label>
        <x-pikaday-input mode="gray" name="captivity.taken_captive_date" format="Y-MM-DD" wire:model.live="captivity.taken_captive_date">
            <x-slot name="script">
                $el.onchange = function () {
                @this.set('captivity.taken_captive_date', $el.value);
                }
            </x-slot>
        </x-pikaday-input>
        @error('captivity.taken_captive_date')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="captivity.release_date">{{ __('Release date') }}</x-label>
        <x-pikaday-input mode="gray" name="captivity.release_date" format="Y-MM-DD" wire:model.live="captivity.release_date">
            <x-slot name="script">
                $el.onchange = function () {
                @this.set('captivity.release_date', $el.value);
                }
            </x-slot>
        </x-pikaday-input>
    </div>
</div>

<div class="flex justify-end">
    <x-button  mode="black" wire:click="addCaptivity">{{ __('Add') }}</x-button>
</div>

<div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
        <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
            <x-table.tbl :headers="[__('Location'),__('Condition'),__('Date'),'action']">
                @forelse ($captivity_list as $key => $captivityModel)
                    <tr>
                        <x-table.td>
                            <span class="text-sm font-medium text-gray-600">
                                {{ __($captivityModel['location']) }}
                            </span>
                        </x-table.td>
                        <x-table.td>
                            <span class="text-sm font-medium text-gray-900">
                                {{ __($captivityModel['condition']) }}
                            </span>
                        </x-table.td>
                        <x-table.td>
                            <div class="flex flex-col space-y-1">
                                <div class="flex items-center space-x-1">
                                    <span class="text-sm font-medium text-gray-500">{{ __('Taken date')  }}:</span>
                                    <span class="text-sm font-medium text-gray-900">
                                        {{ \Carbon\Carbon::parse($captivityModel['taken_captive_date'])->format('d.m.Y') }}
                                    </span>
                                </div>
                                @if(array_key_exists('release_date',$captivityModel))
                                <div class="flex items-center space-x-1">
                                    <span class="text-sm font-medium text-gray-500">{{ __('Release date')  }}:</span>
                                    <span class="text-sm font-medium text-emerald-500">
                                        {{ \Carbon\Carbon::parse($captivityModel['release_date'])->format('d.m.Y') }}
                                    </span>
                                </div>
                                @endif
                            </div>
                        </x-table.td>
                        <x-table.td :isButton="true">
                            <button
                                onclick="confirm('Are you sure you want to remove this data?') || event.stopImmediatePropagation()"
                                wire:click="forceDeleteCaptivity({{ $key }})"
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
