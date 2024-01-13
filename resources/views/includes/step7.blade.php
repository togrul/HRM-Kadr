<div class="grid grid-cols-4 gap-2">
        <div class="flex flex-col">
            <x-select-list class="w-full" :title="__('Kinship')" mode="gray" :selected="$kinshipName" name="kinshipId">
                <x-livewire-input  @click.stop="open = true" mode="gray" name="searchKinship" wire:model.live="searchKinship"></x-livewire-input>

                <x-select-list-item wire:click="setData('kinship','kinship_id','kinship','---',null)" :selected="'---' == $kinshipName"
                  wire:model='kinship.kinship_id.id'>
                  ---
                </x-select-list-item>
                    @foreach($kinshipModel as $kns)
                    <x-select-list-item wire:click="setData('kinship','kinship_id','kinship','{{ $kns->name }}',{{ $kns->id }})"
                    :selected="$kns->id === $kinshipId" wire:model='kinship.kinship_id.id'>
                        {{ $kns->name }}
                    </x-select-list-item>
                    @endforeach
            </x-select-list>
            @error('kinship.kinship_id.id')
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="kinship.fullname">{{ __('Fullname') }}</x-label>
            <x-livewire-input mode="gray" name="kinship.fullname" wire:model="kinship.fullname"></x-livewire-input>
            @error('kinship.fullname')
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="kinship.birthdate">{{ __('Birthdate') }}</x-label>
            <x-pikaday-input mode="gray" name="kinship.birthdate" format="Y-MM-DD" wire:model.live="kinship.birthdate">
                <x-slot name="script">
                  $el.onchange = function () {
                  @this.set('kinship.birthdate', $el.value);
                  }
                </x-slot>
              </x-pikaday-input>
              @error('kinship.birthdate')
              <x-validation> {{ $message }} </x-validation>
              @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="kinship.birth_place">{{ __('Birth place') }}</x-label>
            <x-livewire-input mode="gray" name="kinship.birth_place" wire:model="kinship.birth_place"></x-livewire-input>
        </div>
</div>

<div class="grid grid-cols-4 gap-2">
    <div class="flex flex-col">
        <x-label for="kinship.company_name">{{ __('Company name') }}</x-label>
        <x-livewire-input mode="gray" name="kinship.company_name" wire:model="kinship.company_name"></x-livewire-input>
    </div>
    <div class="flex flex-col">
        <x-label for="kinship.position">{{ __('Position') }}</x-label>
        <x-livewire-input mode="gray" name="kinship.position" wire:model="kinship.position"></x-livewire-input>
    </div>
    <div class="flex flex-col">
        <x-label for="kinship.registered_address">{{ __('Registered address') }}</x-label>
        <x-livewire-input mode="gray" name="kinship.registered_address" wire:model="kinship.registered_address"></x-livewire-input>
        @error('kinship.registered_address')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="kinship.residental_address">{{ __('Residental address') }}</x-label>
        <x-livewire-input mode="gray" name="kinship.residental_address" wire:model="kinship.residental_address"></x-livewire-input>
        @error('kinship.residental_address')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
</div>

<div class="grid grid-cols-2 gap-2">
    <div class="flex flex-col">
        <x-label for="kinship.birth_certificate_number">{{ __('Birth certificate number') }}</x-label>
        <x-livewire-input mode="gray" name="kinship.birth_certificate_number" wire:model="kinship.birth_certificate_number"></x-livewire-input>
    </div>
    <div class="flex flex-col">
        <x-label for="kinship.marriage_certificate_number">{{ __('Marriage certificate number') }}</x-label>
        <x-livewire-input mode="gray" name="kinship.marriage_certificate_number" wire:model="kinship.marriage_certificate_number"></x-livewire-input>
    </div>
</div>

<div class="flex justify-end">
    <x-button  mode="black" wire:click="addKinship">{{ __('Add') }}</x-button>
</div>

<div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
    <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
        <x-table.tbl :headers="[__('Kinship'),__('Extra info'),'action']">
            @forelse ($kinship_list as $key => $knshModel)
            <tr>
                <x-table.td>
                    <div class="flex flex-col">
                        <div class="flex space-x-2">
                            <span class="text-sm text-gray-500 font-medium">{{ __('Kinship') }}:</span>
                            <span class="text-sm font-medium text-gray-700">
                                {{ $knshModel['kinship_id']['name'] }}
                           </span>
                        </div>
                        <div class="flex space-x-2">
                            <span class="text-sm text-gray-500 font-medium">{{ __('Fullname') }}:</span>
                            <span class="text-sm font-medium text-gray-700">
                                {{ $knshModel['fullname'] }}
                           </span>
                        </div>
                        <div class="flex space-x-2">
                            <span class="text-sm text-gray-500 font-medium">{{ __('Birth date') }}:</span>
                            <span class="text-sm font-medium text-gray-700">
                                {{ $knshModel['birthdate'] }}
                           </span>
                        </div>
                        <div class="flex space-x-2">
                            <span class="text-sm text-gray-500 font-medium">{{ __('Birth place') }}:</span>
                            <span class="text-sm font-medium text-gray-700">
                                {{ $knshModel['birth_place'] }}
                           </span>
                        </div>
                        @if(!empty($knshModel['birth_certificate_number']))
                        <div class="flex space-x-2">
                            <span class="text-sm text-gray-500 font-medium">{{ __('Birth certificate number') }}:</span>
                            <span class="text-sm font-medium text-gray-700">
                                {{ $knshModel['birth_certificate_number'] }}
                           </span>
                        </div>
                        @endif
                    </div>
                </x-table.td>
                <x-table.td>
                    <div class="flex flex-col">
                        <div class="flex space-x-2">
                            <span class="text-sm text-gray-500 font-medium">{{ __('Registered address') }}:</span>
                            <span class="text-sm font-medium text-gray-700">
                                {{ $knshModel['registered_address'] }}
                           </span>
                        </div>
                        <div class="flex space-x-2">
                            <span class="text-sm text-gray-500 font-medium">{{ __('Residental address') }}:</span>
                            <span class="text-sm font-medium text-gray-700">
                                {{ $knshModel['residental_address'] }}
                           </span>
                        </div>
                        <div class="flex space-x-2">
                            <span class="text-sm text-gray-500 font-medium">{{ __('Company') }}:</span>
                            <span class="text-sm font-medium text-gray-700">
                                {{ $knshModel['company_name'] }}
                           </span>
                        </div>
                        <div class="flex space-x-2">
                            <span class="text-sm text-gray-500 font-medium">{{ __('Position') }}:</span>
                            <span class="text-sm font-medium text-gray-700">
                                {{ $knshModel['position'] }}
                           </span>
                        </div>
                        @if(!empty($knshModel['marriage_certificate_number']))
                        <div class="flex space-x-2">
                            <span class="text-sm text-gray-500 font-medium">{{ __('Marriage certificate number') }}:</span>
                            <span class="text-sm font-medium text-gray-700">
                                {{ $knshModel['marriage_certificate_number'] }}
                           </span>
                        </div>
                        @endif
                    </div>
                </x-table.td>

                <x-table.td :isButton="true">
                     <button
                        onclick="confirm('Are you sure you want to remove this data?') || event.stopImmediatePropagation()"
                        wire:click="forceDeleteKinship({{ $key }})"
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
