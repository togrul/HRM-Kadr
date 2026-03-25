@if ($filterDetailMounted)
    <livewire:ui.filter.detail :filter="$filters" :key="'personnel-filter-detail'" lazy />
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

    @if (\App\Modules\Personnel\Support\ProfessionalPortfolio\ProfessionalPortfolioPermissionMatrix::canViewPortfolio(auth()->user()))
        @if ($showSideMenu == 'professional-portfolio')
            <livewire:personnel.professional-portfolio :personnelModel="$modelName" :key="'professional-portfolio-' . $modelName" />
        @endif
    @endif

    @can('manage-my-hr-accounts')
        @if ($showSideMenu == 'my-hr-account')
            <livewire:personnel.my-hr.account-provisioning :personnelModel="$modelName" :key="'my-hr-account-' . $modelName" />
        @endif
    @endcan

    @canany(['assign-onboarding-documents', 'manage-onboarding-document-templates'])
        @if ($showSideMenu == 'onboarding-documents')
            <livewire:personnel.my-hr.onboarding-assignment-manager :personnelModel="$modelName" :key="'onboarding-documents-' . $modelName" />
        @endif
    @endcanany

    @canany(['assign-employee-content', 'manage-employee-content-library'])
        @if ($showSideMenu == 'learning-materials')
            <livewire:personnel.my-hr.learning-assignment-manager :personnelModel="$modelName" :key="'learning-materials-' . $modelName" />
        @endif
    @endcanany

</x-side-modal>

@can('delete-personnels')
    <div>
        <livewire:personnel.delete-personnel />
    </div>
@endcan
