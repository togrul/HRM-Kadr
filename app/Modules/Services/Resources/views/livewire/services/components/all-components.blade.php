<div class="flex flex-col space-y-8"
     x-data
>
    <div class="flex items-center justify-between py-2 space-x-2 action-section">
        <div class="">
            <x-label for="search">{{ __('Name') }}</x-label>
            <x-livewire-input mode="gray" name="search" wire:model.live="search"></x-livewire-input>
        </div>
        {{-- @can('manage-components') --}}
        <x-button mode="primary" wire:click="openSideMenu('add-component')" class="space-x-2">
            <x-icons.add-icon color="text-white" hover="text-gray-50"></x-icons.add-icon>
            <span>{{ __('Add component') }}</span>
        </x-button>
        {{-- @endcan --}}
    </div>

    <div class="flex flex-col space-y-2">

        <div class="grid grid-cols-1 gap-2">
            @forelse ($_components as $key => $_component)
                <div class="flex items-center justify-between px-4 py-3 shadow-sm rounded-xl bg-neutral-100">
                    <span class="w-20 font-medium text-neutral-900">
                        {{ ($_components->currentpage()-1) * $_components->perpage() + $key + 1 }}
                    </span>
                    <span class="px-3 py-1 text-sm font-medium rounded-lg bg-neutral-200 text-neutral-700">
                        {{ $_component->orderType->name }}
                    </span>
                    <span class="text-sm font-medium text-neutral-600">
                        {{ $_component->name }}
                    </span>
                    <div class="flex items-center justify-end w-20 space-x-2">
                        <button
                            wire:click.prevent="openSideMenu('edit-component',{{ $_component->id }})"
                            class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase rounded-lg hover:bg-gray-200 hover:text-gray-700"
                        >
                            <x-icons.edit-icon color="text-slate-600" hover="text-slate-700"></x-icons.edit-icon>
                        </button>
                        <button
                            wire:click.prevent="setDeleteComponent({{ $_component->id  }})"
                            class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg hover:bg-red-50 hover:text-gray-700"
                        >
                            <x-icons.delete-icon color="text-rose-500" hover="text-rose-600"></x-icons.delete-icon>
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
