<div x-data
    class="flex flex-col"
>
    <div class="flex flex-col justify-between sm:flex-row filter mb-4">
        <div class="flex items-center justify-center space-x-2 action-section">
            {{-- @can('manage-settings') --}}
            <x-button class="space-x-2" mode="primary" @click.prevent="Livewire.dispatch('settingsWasSet')" >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>                                  
               <span>{{ __('Add settings') }}</span>
            </x-button>
            {{-- @endcan --}}
        </div>
    </div>

   
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
        @foreach ($settings as $key => $stg)
        <div class="flex space-x-2 justify-between items-end w-full">
            <div class="flex flex-col space-y-2 w-full">
                <span class="text-sm font-medium text-gray-500">{{ __($stg->name) }}</span>
                @if($stg->type == 'string')
                <x-livewire-input mode="gray" name="setting.{{ $key }}.value" wire:model.live="setting.{{ $key }}.value"></x-livewire-input>
                @elseif($stg->type == 'bool')
                <x-checkbox name="setting.{{ $key }}.value" model="setting.{{ $key }}.value"></x-checkbox>
                @else
                <x-livewire-input mode="gray" type="number" name="setting.{{ $key }}.value" wire:model.live="setting.{{ $key }}.value"></x-livewire-input>
                @endif
            </div>
            <button
            wire:click.prevent = "setDeleteSettings({{ $stg->id }})"
                class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-100 hover:text-gray-700"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-red-400">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
            </button>
        </div>
       
        @endforeach
    </div>

   
    
    {{-- @can('manage-settings') --}}
    <div>
        @livewire('services.settings.add-settings')
    </div>
    {{-- @endcan --}}

    <div class="">
        @auth
            @livewire('services.settings.delete-settings')
        @endauth
   </div>
</div>
