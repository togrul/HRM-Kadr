<div class="flex flex-col"
     x-data
     x-init="
        paginator = document.querySelector('span[aria-current=page]>span');
        if(paginator != null)
        {
            paginator.classList.add('bg-blue-50','text-blue-600')
        }
        Livewire.hook('message.processed', (message,component) => {
            const paginator = document.querySelector('span[aria-current=page]>span')
            if(
                ['gotoPage','previousPage','nextPage','setStatus','resetFilter'].includes(message?.updateQueue?.[0]?.payload?.method)
                || ['punishmentSaved'].includes(message?.updateQueue?.[0]?.payload?.event)
                || ['q'].includes(message?.updateQueue?.[0]?.name)
            ){
                if(paginator != null)
                {
                    paginator.classList.add('bg-blue-50','text-blue-600')
                }
            }
        })
    "
>
    <div class="flex flex-col items-center justify-between sm:flex-row filter bg-white py-2 px-2 rounded-xl">
        <x-filter.nav>
            <x-filter.item  wire:click.prevent="setPunishmentType('-1')" :active="$selectedType === '-1'">
                <span class="uppercase text-xs">{{ __('All') }}</span>
            </x-filter.item>
            @foreach($punishment_types as $type)
                <div  class="flex space-x-1 items-center group" wire:key="{{ $type->id }}">
                    <x-filter.item wire:click.prevent="setPunishmentType({{$type->id}})" :active="$selectedType == $type->id">
                        <span class="uppercase text-xs">{{ $type->name }}</span>
                    </x-filter.item>
                    <button
                        x-on:click.prevent="
                            $dispatch('close-child');
                            $wire.loadChildComponent({{ $type->id }});
                        "
                        class="hidden group-hover:flex transition-all duration-300 appearance-none items-center justify-center"
                    >
                        <x-icons.edit-icon size="w-4 h-4"></x-icons.edit-icon>
                    </button>
                </div>
            @endforeach
            <button
                wire:click.prevent="loadChildComponent()"
                wire:ignore.self
                class="appearance-none flex justify-center items-center px-3 py-1 font-medium transition duration-150 ease-in  rounded-lg text-black hover:bg-white"
            >
                <x-icons.add-icon size="w-4 h-4"></x-icons.add-icon>
            </button>
        </x-filter.nav>

        <div class="flex items-center justify-center space-x-2 action-section">
            <x-button class="space-x-2" mode="primary" wire:click.prevent="openCrud()">
                <x-icons.add-icon color="text-white" hover="text-gray-50"></x-icons.add-icon>
                <span>{{ __('Add punishment') }}</span>
            </x-button>
        </div>
    </div>

    @if($showChild)
        <livewire:admin.punishment-types :model=$childModel wire:key="punishment-type-{{ $childModel }}" />
    @endif

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
                    <x-ui.select-dropdown
                        :label="__('Punishment types')"
                        placeholder="---"
                        mode="default"
                        class="w-full"
                        wire:model.live="form.punishment_type_id"
                        :model="$this->punishmentTypeOptions()"
                    search-model="searchPunishmentType"
                    >
                    </x-ui.select-dropdown>
                    @error('form.punishment_type_id')
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
                    <x-table.tbl :headers="[__('ID'),__('Type'),__('Name'),'action']">
                        @forelse ($punishments as $punishment)
                            <tr>
                                <x-table.td>
                                      <span class="text-sm text-gray-500 font-medium">
                                          {{ $punishment->id }}
                                      </span>
                                </x-table.td>
                                <x-table.td>
                                    <span class="text-xs font-medium flex justify-center items-center px-1 py-1 rounded-md border border-gray-300 bg-gray-50 text-gray-600">
                                        {{ $punishment->type->name }}
                                    </span>
                                </x-table.td>
                                <x-table.td style="white-space: normal !important;">
                                    <p class="text-sm font-medium">
                                        {{ $punishment->name }}
                                    </p>
                                </x-table.td>
                                <x-table.td :isButton="true" width="100">
                                    <div class="flex items-center space-x-2">
                                        <button
                                            wire:click.prevent="openCrud({{ $punishment->id }})"
                                            class="appearance-none flex items-center justify-center w-8 h-8 text-xs font-medium uppercase rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700"
                                        >
                                            <x-icons.edit-icon color="text-slate-400" hover="text-slate-500"></x-icons.edit-icon>
                                        </button>
                                        <button
                                            wire:click.prevent = "deleteModel({{ $punishment->id }})"
                                            {{--                                            wire:click="$dispatch('delete-prompt')"--}}
                                            class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-100 hover:text-gray-700"
                                        >
                                            <x-icons.delete-icon color="text-rose-500" hover="text-rose-600"></x-icons.delete-icon>
                                        </button>
                                    </div>
                                </x-table.td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4"></td>
                            </tr>
                        @endforelse
                    </x-table.tbl>
                </div>
                <div class="mt-2">
                    {{ $punishments->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@include('includes.sweetalert-push')
