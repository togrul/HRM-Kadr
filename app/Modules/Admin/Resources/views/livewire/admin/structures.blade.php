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
                <span>{{ __('Add structure') }}</span>
            </x-button>
        </div>
    </div>

    @if($isAdded)
        <div class="flex border border-gray-300 rounded-md bg-slate-50 relative px-3 py-2 my-3">
            <button class="appearance-none absolute top-2 right-2" wire:click="closeCrud()">
                <x-icons.close-icon></x-icons.close-icon>
            </button>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 mt-4 w-full">
                <div class="flex flex-col">
                    <x-ui.select-dropdown
                        :label="__('Parent')"
                        placeholder="---"
                        mode="default"
                        class="w-full"
                        wire:model.live="form.parent_id"
                        :model="$this->parentStructureOptions()"
                    search-model="searchParent"
                    >
                    </x-ui.select-dropdown>
                    @error('form.parent_id')
                    <x-validation>{{ $message }}</x-validation>
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
                    <x-label for="form.coefficient">{{ __('Coefficient') }}</x-label>
                    <x-livewire-input type="number" name="form.coefficient" wire:model="form.coefficient"></x-livewire-input>
                    @error('form.coefficient')
                    <x-validation>{{ $message }}</x-validation>
                    @enderror
                </div>
                <div class="flex flex-col">
                    <x-label for="form.code">{{ __('Code') }}</x-label>
                    <x-livewire-input type="number" name="form.code" wire:model="form.code"></x-livewire-input>
                    @error('form.code')
                    <x-validation>{{ $message }}</x-validation>
                    @enderror
                </div>
                <div class="flex flex-col">
                    <x-label for="form.level">{{ __('Level') }}</x-label>
                    <x-livewire-input type="number" name="form.level" wire:model="form.level"></x-livewire-input>
                    @error('form.level')
                    <x-validation>{{ $message }}</x-validation>
                    @enderror
                </div>

                <div class="flex items-end">
                    <x-modal-button mode="black">{{ __('Save') }}</x-modal-button>
                </div>
            </div>
        </div>
    @endif

        <x-nested.list>
            @foreach ($structureList as $structure)
                <x-nested.item :model="$structure">{{ $structure->name }}</x-nested.item>
            @endforeach
        </x-nested.list>
</div>
@include('includes.sweetalert-push')
