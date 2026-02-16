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
                ['gotoPage','previousPage','nextPage','setStatus','resetFilter'].includes(message.updateQueue[0].payload.method)
                || ['citiesUpdated'].includes(message.updateQueue[0].payload.event)
                || ['q'].includes(message.updateQueue[0].name)
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
        <div class="flex items-center justify-center space-x-2 action-section">
            <x-button class="space-x-2"
                      mode="primary"
                      wire:click.prevent="openCrud()"
            >
                <x-icons.add-icon color="text-white" hover="text-gray-50"></x-icons.add-icon>
                <span>{{ __('Add city') }}</span>
            </x-button>
        </div>
    </div>

    @if($isAdded)
        <div wire:key="{{ $model ? $model->id : 'create-crud' }}"
             class="flex border border-gray-300 rounded-md bg-slate-50 relative px-3 py-2 my-3"
        >
            <button class="appearance-none absolute top-2 right-2" wire:click="closeCrud()">
                <x-icons.close-icon></x-icons.close-icon>
            </button>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 mt-4 w-full">
                <div class="flex flex-col">
                    <x-label for="form.id">{{ __('ID') }}</x-label>
                    <x-livewire-input mode="disabled" disabled="true" type="number" name="form.id" wire:model="form.id"></x-livewire-input>
                    @error('form.id')
                    <x-validation>{{ $message }}</x-validation>
                    @enderror
                </div>
                <div class="flex flex-col">
                    <x-ui.select-dropdown
                        :label="__('Country')"
                        placeholder="---"
                        mode="default"
                        class="w-full"
                        wire:model.live="form.country_id"
                        :model="$this->countryOptions()"
                    search-model="searchCountry"
                    >
                    </x-ui.select-dropdown>
                    @error('form.country_id')
                    <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
                <div class="flex flex-col">
                    <x-ui.select-dropdown
                        :label="__('Parent')"
                        placeholder="---"
                        mode="default"
                        class="w-full"
                        wire:model.live="form.parent_id"
                        :model="$this->parentCityOptions()"
                    search-model="searchParent"
                    >
                    </x-ui.select-dropdown>
                    @error('form.parent_id')
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
                    <x-table.tbl :headers="[__('ID'),__('Country'),__('Parent'),__('Name'),'action']">
                        @forelse ($cities as $city)
                            <tr>
                                <x-table.td>
                                      <span class="text-sm text-gray-500 font-medium">
                                          {{ $city->id }}
                                      </span>
                                </x-table.td>
                                <x-table.td>
                                    <span class="text-xs font-medium flex justify-center items-center px-1 py-1 rounded-md border border-gray-300 bg-gray-50 text-gray-600">
                                        {{ $city->country->currentCountryTranslations->title }}
                                    </span>
                                </x-table.td>

                                <x-table.td>
                                    @if($city->parent)
                                    <span class="text-xs font-medium flex justify-center items-center px-1 py-1 rounded-md border border-blue-300 bg-blue-50 text-gray-600">
                                        {{ $city->parent?->name }}
                                    </span>
                                    @endif
                                </x-table.td>

                                <x-table.td>
                                    <span class="text-sm font-medium">
                                        {{ $city->name }}
                                    </span>
                                </x-table.td>

                                <x-table.td :isButton="true" width="100">
                                    <div class="flex items-center space-x-2">
                                        <button
                                            wire:click.prevent="openCrud({{ $city->id }})"
                                            class="appearance-none flex items-center justify-center w-8 h-8 text-xs font-medium uppercase rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700"
                                        >
                                            <x-icons.edit-icon color="text-slate-400" hover="text-slate-500"></x-icons.edit-icon>
                                        </button>
                                        <button
                                            wire:click.prevent = "deleteModel({{ $city->id }})"
                                            class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-100 hover:text-gray-700"
                                        >
                                            <x-icons.delete-icon color="text-rose-500" hover="text-rose-600"></x-icons.delete-icon>
                                        </button>
                                    </div>
                                </x-table.td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                </td>
                            </tr>
                        @endforelse
                    </x-table.tbl>
                </div>
                <div class="">
                    {{ $cities->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@include('includes.sweetalert-push')
