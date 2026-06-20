<div class="flex flex-col">
    <div class="flex space-x-4">
        @can('add-personnels')
            <x-action-button wire:click="openSideMenu('add-personnel')" wire:loading.attr="disabled" wire:target="openSideMenu" class="hover:bg-blue-50" title="{{ __('personnel::common.titles.new_personnel') }}" aria-label="{{ __('personnel::common.titles.new_personnel') }}">
                <x-icons.add-file />
            </x-action-button>
        @endcan

        @can('export-personnels')
            <x-action-button wire:click.prevent="exportExcel" wire:loading.class="opacity-75 pointer-events-none" wire:target="exportExcel" class="hover:bg-green-50" title="{{ __('personnel::common.actions.export_excel') }}" aria-label="{{ __('personnel::common.actions.export_excel') }}">
                <x-icons.excel-icon />
            </x-action-button>
        @endcan

        <x-filter-button :filters="$this->filters" wire:click="openFilter" wire:loading.attr="disabled" wire:target="openFilter" />
    </div>

    @if (count($this->filters) > 0)
        <button wire:click="resetSelectedFilter" wire:loading.attr="disabled" wire:target="resetSelectedFilter"
            class="appearance-none text-rose-500 text-sm font-medium flex items-center space-x-2 justify-end">
            <x-icons.remove-icon />
            <span>{{ __('personnel::common.actions.reset_filter') }}</span>
        </button>
    @endif
</div>
