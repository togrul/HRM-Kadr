{{-- The personnel-file side panels. Same components the list used to open from the row menu. --}}
<x-side-modal size="x-large">
    @can('edit-personnels')
        @if ($showSideMenu === 'show-files')
            <livewire:personnel.files :personnelModel="$modelName" :key="'profile-files-'.$modelName" />
        @endif

        @if ($showSideMenu === 'show-information')
            <livewire:personnel.information :personnelModel="$modelName" :key="'profile-information-'.$modelName" />
        @endif

        @if ($showSideMenu === 'show-vacations')
            <livewire:personnel.vacation-list :personnelModel="$modelName" :key="'profile-vacations-'.$modelName" />
        @endif
    @endcan

    @if (\App\Modules\Personnel\Support\ProfessionalPortfolio\ProfessionalPortfolioPermissionMatrix::canViewPortfolio(auth()->user()))
        @if ($showSideMenu === 'professional-portfolio')
            <livewire:personnel.professional-portfolio :personnelModel="$modelName" :key="'profile-portfolio-'.$modelName" />
        @endif
    @endif

    @can('manage-my-hr-accounts')
        @if ($showSideMenu === 'my-hr-account')
            <livewire:personnel.my-hr.account-provisioning :personnelModel="$modelName" :key="'profile-my-hr-'.$modelName" />
        @endif
    @endcan

    @canany(['assign-onboarding-documents', 'manage-onboarding-document-templates'])
        @if ($showSideMenu === 'onboarding-documents')
            <livewire:personnel.my-hr.onboarding-assignment-manager :personnelModel="$modelName" :key="'profile-onboarding-'.$modelName" />
        @endif
    @endcanany

    @canany(['assign-employee-content', 'manage-employee-content-library'])
        @if ($showSideMenu === 'learning-materials')
            <livewire:personnel.my-hr.learning-assignment-manager :personnelModel="$modelName" :key="'profile-learning-'.$modelName" />
        @endif
    @endcanany
</x-side-modal>
