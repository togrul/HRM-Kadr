<div class="flex flex-col space-y-8"
     x-data
>
    <div class="flex items-center justify-between py-2 space-x-2 action-section">
        <div class="w-full sm:w-72">
            <x-label for="search">{{ __('services::common.labels.name') }}</x-label>
            <x-livewire-input mode="gray" name="search" wire:model.live="search"></x-livewire-input>
        </div>
        {{-- @can('manage-components') --}}
        <x-button mode="primary" wire:click="openSideMenu('add-component')" class="space-x-2">
            <x-icons.add-icon color="text-white" hover="text-gray-50"></x-icons.add-icon>
            <span>{{ __('services::components.actions.add_component') }}</span>
        </x-button>
        {{-- @endcan --}}
    </div>

    <div class="flex flex-col space-y-2">

        <div class="grid grid-cols-1 gap-2">
            @forelse ($_components as $_component)
                <div class="flex items-center justify-between gap-4 rounded-2xl border border-zinc-200 bg-white px-4 py-3 shadow-sm transition hover:border-zinc-300 hover:bg-zinc-50" wire:key="component-row-{{ $_component->id }}">
                    <span class="w-20 font-medium text-neutral-900">
                        {{ $_component->row_no }}
                    </span>
                    <span class="rounded-full border border-zinc-200 bg-zinc-100 px-3 py-1 text-sm font-medium text-zinc-700">
                        {{ $_component->orderType->name }}
                    </span>
                    <span class="text-sm font-medium text-neutral-600">
                        {{ $_component->name }}
                    </span>
                    <div class="flex items-center justify-end w-20 space-x-2">
                        <x-action-button
                            wire:click.prevent="openSideMenu('edit-component',{{ $_component->id }})"
                            class="h-9 w-9 hover:bg-zinc-100"
                            :title="__('services::components.titles.edit')"
                        >
                            <x-icons.edit-icon color="text-slate-600" hover="text-slate-700"></x-icons.edit-icon>
                        </x-action-button>
                        <x-action-button
                            wire:click.prevent="setDeleteComponent({{ $_component->id  }})"
                            class="h-9 w-9 hover:bg-red-50"
                            :title="__('services::components.titles.delete')"
                        >
                            <x-icons.delete-icon color="text-rose-500" hover="text-rose-600"></x-icons.delete-icon>
                        </x-action-button>
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
                <livewire:services.components.add-component wire:key="services-component-add-modal" />
            @endif

            @if($showSideMenu == 'edit-component')
                <livewire:services.components.edit-component :componentModel="$modelName" :key="'services-component-edit-modal-' . ($modelName ?? 'none')" />
            @endif
        </x-side-modal>
    </div>
    {{-- @endcan --}}

    <div class="">
        @auth
            <livewire:services.components.delete-component wire:key="services-component-delete-modal" />
        @endauth
    </div>
</div>
