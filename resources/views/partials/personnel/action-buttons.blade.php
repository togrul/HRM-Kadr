<div class="flex flex-col">
    <div class="flex space-x-4">
        @can('add-personnels')
            <x-action-button wire:click="openSideMenu('add-personnel')" class="hover:bg-blue-50" title="Add Personnel">
                <x-icons.add-file />
            </x-action-button>
        @endcan

        @can('export-personnels')
            <x-action-button wire:click.prevent="exportExcel" class="hover:bg-green-50" title="Export Excel">
                <x-icons.excel-icon />
            </x-action-button>
        @endcan

        @can('edit-personnels')
            <x-action-button wire:click.prevent="printPage('personnel')" class="hover:bg-red-50" title="Print">
                <x-icons.print-file color="text-rose-500" hover="text-rose-600" size="w-8 h-8" />
            </x-action-button>
        @endcan

        <x-filter-button :filters="$filters" />
    </div>

    @if (count($filters) > 0)
        <button wire:click="resetSelectedFilter"
            class="appearance-none text-rose-500 text-sm font-medium flex items-center space-x-2 justify-end">
            <x-icons.remove-icon />
            <span>{{ __('Reset filter') }}</span>
        </button>
    @endif
</div>
