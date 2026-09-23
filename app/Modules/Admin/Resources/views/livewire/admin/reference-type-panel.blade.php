<div wire:transition class="flex border border-zinc-300 rounded-md bg-zinc-50 relative px-3 py-2 my-3">
    <button type="button" class="appearance-none absolute right-2 top-2 flex h-10 w-10 items-center justify-center rounded-lg hover:bg-zinc-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400" wire:click="$dispatch('close-child')" aria-label="{{ __('admin::references.actions.close') }}">
        <x-icons.close-icon></x-icons.close-icon>
    </button>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-2 mt-4 w-full">
        <div class="flex flex-col">
            <x-label for="childForm.id">{{ __('admin::references.fields.id') }}</x-label>
            <x-livewire-input mode="default" type="number" name="childForm.id" wire:model="childForm.id"></x-livewire-input>
            @error('childForm.id')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="childForm.name">{{ __('admin::references.fields.name') }}</x-label>
            <x-livewire-input mode="default" type="text" name="childForm.name" wire:model="childForm.name" required></x-livewire-input>
            @error('childForm.name')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex items-end space-x-2">
            <x-modal-button style="width: auto !important;" mode="black">{{ __('admin::references.actions.save') }}</x-modal-button>
            <button
                wire:click.prevent="deleteModel()"
                class="appearance-none flex h-10 w-10 flex-none items-center justify-center rounded-lg bg-rose-50 font-medium text-zinc-500 transition hover:bg-red-100 hover:text-zinc-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-400"
                aria-label="{{ __('admin::references.actions.delete') }}"
            >
                <x-icons.delete-icon color="text-rose-500" hover="text-rose-600"></x-icons.delete-icon>
            </button>
        </div>
    </div>
</div>
