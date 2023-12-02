<div class="w-full rounded-xl px-4 py-3 bg-gray-100 font-semibold text-xl flex justify-center">
    <h1>{{ __('Awards') }}</h1>
</div>
<div class="grid grid-cols-3 gap-2">
    <div class="flex flex-col">
        <x-select-list class="w-full" :title="__('Awards')" mode="gray" :selected="$awardName" name="awardId">
            <x-livewire-input  @click.stop="open = true" mode="gray" name="searchAward" wire:model.live="searchAward"></x-livewire-input>
            
            <x-select-list-item wire:click="setData('award','award_id','award','---',null)" :selected="'---' == $awardName"
              wire:model='award.award_id.id'>
              ---
            </x-select-list-item>
                @foreach($awardModel as $awd)
                <x-select-list-item wire:click="setData('award','award_id','award','{{ $awd->name }}',{{ $awd->id }})"
                :selected="$awd->id === $awardId" wire:model='award.award_id.id'>
                    {{ $awd->name }}
                </x-select-list-item>
                @endforeach
        </x-select-list>
        @error('award.award_id.id')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="award.reason">{{ __('Reason') }}</x-label>
        <x-livewire-input mode="gray" name="award.reason" wire:model="award.reason"></x-livewire-input>
        @error('award.reason')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="award.given_date">{{ __('Given date') }}</x-label>
        <x-pikaday-input mode="gray" name="award.given_date" format="Y-MM-DD" wire:model.live="award.given_date">
            <x-slot name="script">
              $el.onchange = function () {
              @this.set('award.given_date', $el.value);
              }
            </x-slot>
          </x-pikaday-input>
          @error('award.given_date')
          <x-validation> {{ $message }} </x-validation>
          @enderror
    </div>
</div>

<div class="flex justify-end">
    <x-button  mode="black" wire:click="addAward">{{ __('Add') }}</x-button>
</div>

<div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
    <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
        <x-table.tbl :headers="[__('Award'),__('Reason'),__('Date'),'action']">
            @forelse ($award_list as $key => $awdModel)
            <tr>
                <x-table.td>
                    <span class="text-sm font-medium text-gray-700">
                        {{ $awdModel['award_id']['name'] }} 
                   </span>
                </x-table.td>
                <x-table.td>
                   <span class="text-sm font-medium text-gray-700">
                        {{ $awdModel['reason'] }} 
                    </span>
                </x-table.td>
                <x-table.td>
                    <span class="text-sm font-medium text-gray-700">
                        {{ $awdModel['given_date'] }} 
                    </span>
                </x-table.td>
                <x-table.td :isButton="true">
                     <button
                        onclick="confirm('Are you sure you want to remove this data?') || event.stopImmediatePropagation()"
                        wire:click="forceDeleteAward({{ $key }})"
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

<hr>
<div class="w-full rounded-xl px-4 py-3 bg-gray-100 font-semibold text-xl flex justify-center">
    <h1>{{ __('Punishments') }}</h1>
</div>
<div class="grid grid-cols-3 gap-2">
    <div class="flex flex-col">
        <x-select-list class="w-full" :title="__('Punishments')" mode="gray" :selected="$punishmentName" name="punishmentId">
            <x-livewire-input  @click.stop="open = true" mode="gray" name="searchPunishment" wire:model.live="searchPunishment"></x-livewire-input>
            
            <x-select-list-item wire:click="setData('punishment','punishment_id','punishment','---',null)" :selected="'---' == $awardName"
              wire:model='punishment.punishment_id.id'>
              ---
            </x-select-list-item>
                @foreach($punishmentModel as $pnsh)
                <x-select-list-item wire:click="setData('punishment','punishment_id','punishment','{{ $pnsh->name }}',{{ $pnsh->id }})"
                :selected="$pnsh->id === $punishmentId" wire:model='punishment.punishment_id.id'>
                    {{ $pnsh->name }}
                </x-select-list-item>
                @endforeach
        </x-select-list>
        @error('punishment.punishment_id.id')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="punishment.reason">{{ __('Reason') }}</x-label>
        <x-livewire-input mode="gray" name="punishment.reason" wire:model="punishment.reason"></x-livewire-input>
        @error('punishment.reason')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="punishment.given_date">{{ __('Given date') }}</x-label>
        <x-pikaday-input mode="gray" name="punishment.given_date" format="Y-MM-DD" wire:model.live="punishment.given_date">
            <x-slot name="script">
              $el.onchange = function () {
              @this.set('punishment.given_date', $el.value);
              }
            </x-slot>
          </x-pikaday-input>
          @error('punishment.given_date')
          <x-validation> {{ $message }} </x-validation>
          @enderror
    </div>
</div>

<div class="flex justify-end">
    <x-button  mode="black" wire:click="addPunishment">{{ __('Add') }}</x-button>
</div>

<div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
    <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
        <x-table.tbl :headers="[__('Punishment'),__('Reason'),__('Date'),'action']">
            @forelse ($punishment_list as $key => $pnshModel)
            <tr>
                <x-table.td>
                    <span class="text-sm font-medium text-gray-700">
                        {{ $pnshModel['punishment_id']['name'] }} 
                   </span>
                </x-table.td>
                <x-table.td>
                   <span class="text-sm font-medium text-gray-700">
                        {{ $pnshModel['reason'] }} 
                    </span>
                </x-table.td>
                <x-table.td>
                    <span class="text-sm font-medium text-gray-700">
                        {{ $pnshModel['given_date'] }} 
                    </span>
                </x-table.td>
                <x-table.td :isButton="true">
                     <button
                        onclick="confirm('Are you sure you want to remove this data?') || event.stopImmediatePropagation()"
                        wire:click="forceDeletePunishment({{ $key }})"
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

<hr>

<div class="w-full rounded-xl px-4 py-3 bg-gray-100 font-semibold text-xl flex justify-center">
    <h1>{{ __('Criminals') }}</h1>
</div>

<div class="grid grid-cols-3 gap-2">
    <div class="flex flex-col">
        <x-select-list class="w-full" :title="__('Criminals')" mode="gray" :selected="$criminalName" name="criminalId">
            <x-livewire-input  @click.stop="open = true" mode="gray" name="searchCriminal" wire:model.live="searchCriminal"></x-livewire-input>
            
            <x-select-list-item wire:click="setData('criminal','criminal_id','criminal','---',null)" :selected="'---' == $criminalName"
              wire:model='criminal.criminal_id.id'>
              ---
            </x-select-list-item>
                @foreach($criminalModel as $crm)
                <x-select-list-item wire:click="setData('criminal','criminal_id','criminal','{{ $crm->name }}',{{ $crm->id }})"
                :selected="$crm->id === $criminalId" wire:model='criminal.criminal_id.id'>
                    {{ $crm->name }}
                </x-select-list-item>
                @endforeach
        </x-select-list>
        @error('criminal.criminal_id.id')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="criminal.reason">{{ __('Reason') }}</x-label>
        <x-livewire-input mode="gray" name="criminal.reason" wire:model="criminal.reason"></x-livewire-input>
        @error('criminal.reason')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="criminal.given_date">{{ __('Given date') }}</x-label>
        <x-pikaday-input mode="gray" name="criminal.given_date" format="Y-MM-DD" wire:model.live="criminal.given_date">
            <x-slot name="script">
              $el.onchange = function () {
              @this.set('criminal.given_date', $el.value);
              }
            </x-slot>
          </x-pikaday-input>
          @error('criminal.given_date')
          <x-validation> {{ $message }} </x-validation>
          @enderror
    </div>
</div>

<div class="flex justify-end">
    <x-button  mode="black" wire:click="addCriminal">{{ __('Add') }}</x-button>
</div>

<div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
    <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
        <x-table.tbl :headers="[__('Criminal'),__('Reason'),__('Date'),'action']">
            @forelse ($criminal_list as $key => $crmModel)
            <tr>
                <x-table.td>
                    <span class="text-sm font-medium text-gray-700">
                        {{ $crmModel['criminal_id']['name'] }} 
                   </span>
                </x-table.td>
                <x-table.td>
                   <span class="text-sm font-medium text-gray-700">
                        {{ $crmModel['reason'] }} 
                    </span>
                </x-table.td>
                <x-table.td>
                    <span class="text-sm font-medium text-gray-700">
                        {{ $crmModel['given_date'] }} 
                    </span>
                </x-table.td>
                <x-table.td :isButton="true">
                     <button
                        onclick="confirm('Are you sure you want to remove this data?') || event.stopImmediatePropagation()"
                        wire:click="forceDeleteCriminal({{ $key }})"
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