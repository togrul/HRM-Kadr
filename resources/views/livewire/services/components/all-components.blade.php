<div class="flex flex-col space-y-8"
     x-data
>
    <div class="flex items-center justify-between space-x-2 action-section py-2">
        <div class="">
            <x-label for="search">{{ __('Name') }}</x-label>
            <x-livewire-input mode="gray" name="search" wire:model.live="search"></x-livewire-input>
        </div>
        {{-- @can('manage-components') --}}
        <x-button mode="primary" wire:click="openSideMenu('add-component')" class="space-x-2">
            <svg data-slot="icon" fill="none" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"></path>
            </svg>
            <span>{{ __('Add component') }}</span>
        </x-button>
        {{-- @endcan --}}
    </div>

    <div class="flex flex-col space-y-2">

        <div class="grid grid-cols-1 gap-2">
            @forelse ($_components as $key => $_component)
                <div class="flex justify-between items-center px-4 py-3 rounded-xl shadow-sm bg-slate-100">
                    <span class="text-slate-900 font-medium">
                        {{ ($_components->currentpage()-1) * $_components->perpage() + $key + 1 }}
                    </span>
                    <span class="bg-slate-200 text-slate-700 font-medium px-3 py-1 text-sm rounded-lg">
                        {{ $_component->orderType->name }}
                    </span>
                    <span class="text-slate-600 font-medium text-sm">
                        {{ $_component->name }}
                    </span>
                    <div class="flex justify-end items-center space-x-2">
                        <a href="javascript:void(0)" wire:click.prevent="openSideMenu('edit-component',{{ $_component->id }})" class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase rounded-lg text-gray-500 hover:bg-gray-200 hover:text-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-gray-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                            </svg>
                        </a>
                        <button
                            wire:click.prevent="setDeleteComponent({{ $_component->id  }})"
                            class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-red-500">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                        </button>
                    </div>

                </div>
            @empty

            @endforelse
        </div>

        <div>
            {{ $_components->links() }}
        </div>
    </div>

    {{-- @can('manage-components') --}}
    <div>
        <x-side-modal>
            @if($showSideMenu == 'add-component')
                <livewire:services.components.add-component />
            @endif

            @if($showSideMenu == 'edit-component')
                <livewire:services.components.edit-component :componentModel="$modelName" />
            @endif
        </x-side-modal>
    </div>
    {{-- @endcan --}}

    <div class="">
        @auth
            @livewire('services.components.delete-component')
        @endauth
    </div>
</div>
