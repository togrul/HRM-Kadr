<div class="flex flex-col space-y-8">
    <div class="sidemenu-title">
        <h2 class="text-lg font-medium text-gray-600" id="slide-over-title">
            {{ $title ?? ''}}
        </h2>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 items-center">
        <div class="sm:col-span-2 flex items-end space-x-2">
            <div class="w-full">
                <div class="flex items-center space-x-2">
                    <x-label for="types.name">{{ __('Name') }}</x-label>
                    @error('types.name')
                        <x-validation>(* {{ $message }} )</x-validation>
                    @enderror
                </div>
                <x-livewire-input mode="gray" name="types.name" wire:model="types.name"></x-livewire-input>

            </div>
            <button class="rounded-lg shadow-sm bg-teal-500 text-slate-100 px-6 py-2 font-medium text-sm flex justify-center items-center space-x-2 w-max transition-all duration-300 hover:bg-teal-600 flex-none"
                    wire:click="addType"
            >
                <x-icons.add-icon color="text-white" hover="text-gray-50"></x-icons.add-icon>
                <span class="">{{ __('Add') }}</span>
            </button>
        </div>
    </div>

    <div class="flex flex-col space-y-2">
        @forelse($_order_types as $_type)
            <div class="flex items-center justify-between space-x-2 px-4 py-3 bg-slate-100 rounded-xl shadow-sm">
                <div class="flex items-center space-x-2">
                    <span class="text-sm font-medium text-slate-900">
                        {{ $loop->iteration }}.
                    </span>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm font-medium text-slate-600">
                            {{ $_type->name }}
                        </span>
                        @if($selectedType == $_type->id)
                            <x-livewire-input mode="default" name="types.name" wire:model="types.name"></x-livewire-input>
                            <button class="rounded-lg shadow-sm bg-green-100 p-2 font-medium text-sm flex justify-center items-center space-x-2 w-max transition-all duration-300 hover:bg-green-200"
                                    wire:click="updateModel"
                            >
                                <x-icons.check-simple-icon color="text-green-500" hover="text-green-600"></x-icons.check-simple-icon>
                            </button>
                            <button class="rounded-lg shadow-sm bg-rose-100 p-2 font-medium text-sm flex justify-center items-center space-x-2 w-max transition-all duration-300 hover:bg-rose-200"
                                    wire:click="cancelUpdate"
                            >
                                <x-icons.close-icon color="text-rose-500" hover="text-rose-600"></x-icons.close-icon>
                            </button>
                        @endif
                    </div>

                </div>
                <div class="flex items-center space-x-3">
                    <button class="w-8 h-8 px-2 py-1 rounded-lg hover:bg-emerald-50 hover:shadow-sm font-medium text-sm flex justify-center items-center space-x-2 w-max"
                            wire:click="editType({{ $_type->id }})"
                    >
                        <x-icons.edit-icon color="text-emerald-500" hover="text-emerald-600"></x-icons.edit-icon>
                    </button>
                    <button class="w-8 h-8 px-2 py-1 rounded-lg hover:bg-rose-50 hover:shadow-sm font-medium text-sm flex justify-center items-center space-x-2 w-max"
                            wire:click="removeType({{ $_type->id }})"
                            wire:confirm="{{ __('Are you sure you want to delete?') }}"
                    >
                        <x-icons.backspace-icon color="text-rose-500" hover="text-rose-600"></x-icons.backspace-icon>
                    </button>
                </div>
            </div>
        @empty
            <div class="flex justify-start items-center px-4 py-3 font-medium bg-gray-100 rounded-lg text-gray-500 text-base">
                <span>{{ __('No data exists.') }}</span>
            </div>
        @endforelse

    </div>
</div>
