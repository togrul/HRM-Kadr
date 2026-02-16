<div 
    x-init="
        Livewire.on('settingsUpdated',()=>{
            showMiniModal = false
        })
        Livewire.on('settingsWasSet', () => {
            showMiniModal = true
        })
    "
    x-data={showMiniModal:false}
    x-show="showMiniModal"
    x-on:keydown.escape.window="showMiniModal = false;"
    class="fixed inset-0 z-50 overflow-y-auto" 
    aria-labelledby="modal-title" 
    role="dialog" 
    aria-modal="true"
    style="display:none;"
    >
    <div class="flex items-end justify-center min-h-screen">

      <div  x-show.transition.opacity="showMiniModal"
            class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" 
            aria-hidden="true"
      ></div>

      <div  x-show.transition.origin.bottom.duration.300ms="showMiniModal"
            class="overflow-hidden transition-all transform bg-white shadow-xl modal leading-5py-4 rounded-tl-xl rounded-tr-xl sm:max-w-xl sm:w-full"
      >

        <div class="absolute top-0 right-0 pt-4 pr-4">
            <button 
                @click="showMiniModal = false;"
                class="text-gray-400 hover:text-gray-500"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="px-4 pt-5 pb-4 bg-white sm:p-6 sm:pb-4">
            <h3 class="text-lg font-medium text-center text-gray-900">{{ __('Add setting') }}</h3>
            <form wire:submit.prevent='store' action="#" method="POST" class="px-4 py-6 space-y-4">
               

                <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                    <div>
                        <x-label for="settings.name">{{ __('Name') }}</x-label>
                        <x-livewire-input mode="gray"  name="settings.name" wire:model="settings.name"></x-livewire-input>
                         @error('settings.name')
                             <p class="mt-1 text-xs text-red-500">
                                 {{ $message }}
                             </p>
                         @enderror
                    </div>
                    <div>
                        <x-label for="settings.value">{{ __('Value') }}</x-label>
                        <x-livewire-input mode="gray"  name="settings.value" wire:model="settings.value"></x-livewire-input>
                         @error('settings.value')
                             <p class="mt-1 text-xs text-red-500">
                                 {{ $message }}
                             </p>
                         @enderror
                    </div>
                    <div>
                        <x-label for="settings.type">{{ __('Type') }}</x-label>
                        <x-livewire-input mode="gray"  name="settings.type" wire:model="settings.type"></x-livewire-input>
                    </div>
                </div>
                
                
                   <div class="flex items-center justify-between space-x-3">
                      <button type="button"  @click="showMiniModal = false;" class="flex items-center justify-center w-1/2 px-6 py-3 text-xs font-medium transition duration-200 ease-in bg-gray-200 border border-gray-200 h-11 rounded-xl hover:border-gray-400">
                        <span>{{ __('Cancel') }}</span>
                       </button>
                
                    <button type="submit" class="flex items-center justify-center w-1/2 px-6 py-3 text-xs font-medium text-white transition duration-200 ease-in bg-blue-500 border border-blue-500 h-11 rounded-xl hover:border-blue-800">
                        <span>{{ __('Add') }}</span>
                    </button>
                </div>
            
            </form>
        </div>
       
      </div>
    </div>
  </div>
