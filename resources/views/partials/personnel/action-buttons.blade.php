<x-filter-button :filters="$this->filters" wire:click="openFilter" wire:loading.attr="disabled" wire:target="openFilter" />

@if (count($this->filters) > 0)
    <x-pill-button
        variant="danger"
        wire:click="resetSelectedFilter"
        wire:loading.attr="disabled"
        wire:target="resetSelectedFilter"
    >
        <x-icons.remove-icon size="w-4 h-4" color="text-current" hover="text-current" />
        <span>{{ __('personnel::common.actions.reset_filter') }}</span>
    </x-pill-button>
@endif

@can('export-personnels')
    <x-pill-button
        variant="emerald"
        icon
        wire:click.prevent="exportExcel"
        wire:loading.class="opacity-75 pointer-events-none"
        wire:target="exportExcel"
        :title="__('personnel::common.actions.export_excel')"
        :aria-label="__('personnel::common.actions.export_excel')"
    >
        <x-icons.excel-icon size="w-[18px] h-[18px]" />
    </x-pill-button>
@endcan

@can('add-personnels')
    <x-pill-button
        variant="primary"
        wire:click="openSideMenu('add-personnel')"
        wire:loading.attr="disabled"
        wire:target="openSideMenu"
    >
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
        <span>{{ __('personnel::common.titles.new_personnel') }}</span>
    </x-pill-button>
@endcan
