<div class="flex flex-col"
     x-data
     x-init="
        const root = $el;
        const paintPaginator = () => {
            const paginator = root.querySelector('span[aria-current=page]>span');
            if (paginator) {
                paginator.classList.add('bg-blue-50', 'text-blue-600');
            }
        };
        paintPaginator();
        if (typeof Livewire !== 'undefined') {
            Livewire.hook('commit', ({ component, succeed }) => {
                if (component.id !== $wire.__instance.id) return;
                succeed(() => queueMicrotask(paintPaginator));
            });
        }
    "
>
    <div class="flex flex-col items-center justify-between sm:flex-row filter bg-white py-2 px-2 rounded-xl">
        <div class="flex items-center justify-center space-x-2 action-section">
            <x-button class="space-x-2" mode="primary" wire:click.prevent="openCrud()">
                <x-icons.add-icon color="text-white" hover="text-gray-50"></x-icons.add-icon>
                <span>{{ __('Add institutions') }}</span>
            </x-button>
        </div>
    </div>

    @if($isAdded)
        <div wire:transition class="flex border border-gray-300 rounded-md bg-slate-50 relative px-3 py-2 my-3">
            <button class="appearance-none absolute top-2 right-2" wire:click="closeCrud()">
                <x-icons.close-icon></x-icons.close-icon>
            </button>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-2 mt-4 w-full">
                <div class="flex flex-col">
                    <x-label for="form.id">{{ __('ID') }}</x-label>
                    <x-livewire-input mode="default" type="number" name="form.id" wire:model="form.id"></x-livewire-input>
                    @error('form.id')
                    <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
                <div class="flex flex-col">
                    <x-label for="form.name">{{ __('Name') }}</x-label>
                    <x-livewire-input mode="default" name="form.name" wire:model="form.name"></x-livewire-input>
                    @error('form.name')
                    <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
                <div class="flex flex-col">
                    <x-label for="form.shortname">{{ __('Shortname') }}</x-label>
                    <x-livewire-input mode="default" name="form.shortname" wire:model="form.shortname"></x-livewire-input>
                    @error('form.shortname')
                    <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
                <div class="flex flex-col">
                    <x-label for="form.old_name_1">{{ __('Old name') }} 1</x-label>
                    <x-livewire-input mode="default" name="form.old_name_1" wire:model="form.old_name_1"></x-livewire-input>
                </div>
                <div class="flex flex-col">
                    <x-label for="form.old_name_2">{{ __('Old name') }} 2</x-label>
                    <x-livewire-input mode="default" name="form.old_name_2" wire:model="form.old_name_2"></x-livewire-input>
                </div>
                <div class="flex flex-col">
                    <x-label for="form.old_name_3">{{ __('Old name') }} 3</x-label>
                    <x-livewire-input mode="default" name="form.old_name_3" wire:model="form.old_name_3"></x-livewire-input>
                </div>
                <div class="flex items-end">
                    <x-modal-button mode="black">{{ __('Save') }}</x-modal-button>
                </div>
            </div>
        </div>
    @endif

    <div class="flex flex-col space-y-2">
        <div class="relative min-h-[300px] -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
                    <x-table.tbl :headers="[__('ID'),__('Name'),__('Shortname'),__('Old names'),'action']">
                        @forelse ($educationalInstitutions as $institution)
                            <tr>
                                <x-table.td>
                                      <span class="text-sm text-gray-500 font-medium">
                                          {{ $institution->id }}
                                      </span>
                                </x-table.td>

                                <x-table.td>
                                      <span class="text-sm text-gray-500 font-medium">
                                          {{ $institution->name }}
                                      </span>
                                </x-table.td>

                                <x-table.td>
                                      <span class="text-sm text-gray-500 font-medium">
                                          {{ $institution->shortname }}
                                      </span>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col space-y-1">
                                        @if($institution->old_name_1)
                                        <span class="text-sm font-medium">
                                            <b>Old name 1</b> - {{ $institution->old_name_1 }}
                                        </span>
                                        @endif
                                        @if($institution->old_name_2)
                                        <span class="text-sm font-medium">
                                            <b>Old name 2</b> - {{ $institution->old_name_2 }}
                                        </span>
                                         @endif
                                        @if($institution->old_name_3)
                                        <span class="text-sm font-medium">
                                            <b>Old name 3</b> - {{ $institution->old_name_3 }}
                                        </span>
                                        @endif
                                    </div>
                                </x-table.td>

                                <x-table.td :isButton="true" width="100">
                                    <div class="flex items-center space-x-2">
                                        <button
                                            wire:click.prevent="openCrud({{ $institution->id }})"
                                            class="appearance-none flex items-center justify-center w-8 h-8 text-xs font-medium uppercase rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700"
                                        >
                                            <x-icons.edit-icon color="text-slate-400" hover="text-slate-500"></x-icons.edit-icon>
                                        </button>
                                        <button
                                            wire:click.prevent = "deleteModel({{ $institution->id }})"
                                            class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-100 hover:text-gray-700"
                                        >
                                            <x-icons.delete-icon color="text-rose-500" hover="text-rose-600"></x-icons.delete-icon>
                                        </button>
                                    </div>
                                </x-table.td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                </td>
                            </tr>
                        @endforelse
                    </x-table.tbl>
                </div>
            </div>
        </div>
    </div>
</div>
@include('includes.sweetalert-push')
