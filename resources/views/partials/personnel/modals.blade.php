@if ($filterDetailMounted)
    <livewire:ui.filter.detail :filter="$filters" :key="'personnel-filter-detail'" />
@endif

<x-side-modal size="x-large">
    @can('add-personnels')
        @if ($showSideMenu == 'add-personnel')
            <livewire:personnel.add-personnel :key="'add-personnel-modal'" />
        @endif
    @endcan

    @can('edit-personnels')
        @if ($showSideMenu == 'edit-personnel')
            <livewire:personnel.edit-personnel :personnelModel="$modelName" :key="'edit-personnel-' . $modelName" />
        @endif
    @endcan

    @can('edit-personnels')
        @if ($showSideMenu == 'show-files')
            <livewire:personnel.files :personnelModel="$modelName" :key="'files-personnel-' . $modelName" />
        @endif
    @endcan

    @can('edit-personnels')
        @if ($showSideMenu == 'show-information')
            <livewire:personnel.information :personnelModel="$modelName" :key="'information-personnel-' . $modelName" />
        @endif
    @endcan

    @can('edit-personnels')
        @if ($showSideMenu == 'show-vacations')
            <livewire:personnel.vacation-list :personnelModel="$modelName" :key="'vacation-list-' . $modelName" />
        @endif
    @endcan

</x-side-modal>

@can('delete-personnels')
    <div>
        <livewire:personnel.delete-personnel />
    </div>
@endcan
