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
            <button class="rounded-lg shadow-sm bg-teal-500 text-slate-100 px-6 py-2 font-medium text-sm flex justify-center items-center space-x-2 w-max transition-all duration-300 hover:bg-teal-600"
                    wire:click="addType"
            >
                <svg data-slot="icon" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path>
                </svg>
                <span class="uppercase">{{ __('Add') }}</span>
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
                                <svg data-slot="icon" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="w-5 h-5 text-green-500">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"></path>
                                </svg>
                            </button>
                            <button class="rounded-lg shadow-sm bg-rose-100 p-2 font-medium text-sm flex justify-center items-center space-x-2 w-max transition-all duration-300 hover:bg-rose-200"
                                    wire:click="cancelUpdate"
                            >
                                <svg data-slot="icon" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="w-5 h-5 text-rose-500">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        @endif
                    </div>

                </div>
                <div class="flex items-center space-x-3">
                    <button class="w-8 h-8 px-2 py-1 rounded-lg hover:bg-emerald-50 hover:shadow-sm font-medium text-sm flex justify-center items-center space-x-2 w-max"
                            wire:click="editType({{ $_type->id }})"
                    >
                        <svg data-slot="icon" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="w-6 h-6 text-emerald-500">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"></path>
                        </svg>
                    </button>
                    <button class="w-8 h-8 px-2 py-1 rounded-lg hover:bg-rose-50 hover:shadow-sm font-medium text-sm flex justify-center items-center space-x-2 w-max"
                            wire:click="removeType({{ $_type->id }})"
                            wire:confirm="{{ __('Are you sure you want to delete?') }}"
                    >
                        <svg data-slot="icon" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="w-6 h-6 text-rose-500">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path>
                        </svg>
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
