<div wire:transition class="flex border border-gray-300 rounded-md bg-slate-50 relative px-3 py-2 my-3">
    <button class="appearance-none absolute top-2 right-2" wire:click="$dispatch('close-child')">
        <x-icons.close-icon></x-icons.close-icon>
    </button>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-2 mt-4 w-full">
        <div class="flex flex-col">
            <x-label for="childForm.id">{{ __('ID') }}</x-label>
            <x-livewire-input mode="default" type="number" name="childForm.id" wire:model="childForm.id"></x-livewire-input>
            @error('childForm.id')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="childForm.name">{{ __('Name') }}</x-label>
            <x-livewire-input mode="default" type="text" name="childForm.name" wire:model="childForm.name" required></x-livewire-input>
            @error('childForm.name')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex items-end space-x-2">
            <x-modal-button style="width: auto !important;" mode="black">{{ __('Save') }}</x-modal-button>
            <button
                wire:click.prevent = "deleteModel()"
                {{--                                            wire:click="$dispatch('delete-prompt')"--}}
                class="appearance-none flex flex-none items-center justify-center w-9 h-9 bg-rose-50 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-100 hover:text-gray-700"
            >
                <x-icons.delete-icon color="text-rose-500" hover="text-rose-600"></x-icons.delete-icon>
            </button>
        </div>
    </div>
</div>
