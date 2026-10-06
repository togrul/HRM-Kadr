@if ($filterDetailMounted)
    <livewire:ui.filter.detail :filter="$filters" :key="'personnel-filter-detail'" lazy />
@endif

<x-side-modal size="x-large">
    @can('add-personnels')
        @if ($showSideMenu == 'add-personnel')
            <livewire:personnel.add-personnel :key="'add-personnel-modal'" />
        @endif
    @endcan

    @can('show-personnels')
        @if ($showSideMenu == 'quick-view')
            <livewire:personnel.quick-view :personnelModel="$modelName" :key="'quick-view-' . $modelName" />
        @endif
    @endcan

</x-side-modal>

@can('delete-personnels')
    <div>
        <livewire:personnel.delete-personnel />
    </div>
@endcan
