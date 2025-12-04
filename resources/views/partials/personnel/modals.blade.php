<livewire:ui.filter.detail :filter="$filters"/>

<x-side-modal size="x-large">
    @can('add-personnels')
        @if ($showSideMenu == 'add-personnel')
            <livewire:personnel.add-personnel />
        @endif
    @endcan

    @can('edit-personnels')
        @if ($showSideMenu == 'edit-personnel')
            <livewire:personnel.edit-personnel :personnelModel="$modelName" />
        @endif
    @endcan

    @can('edit-personnels')
        @if ($showSideMenu == 'show-files')
            <livewire:personnel.files :personnelModel="$modelName" />
        @endif
    @endcan

    @can('edit-personnels')
        @if ($showSideMenu == 'show-information')
            <livewire:personnel.information :personnelModel="$modelName" />
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
