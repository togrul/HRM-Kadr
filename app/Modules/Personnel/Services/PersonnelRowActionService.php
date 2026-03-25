<?php

namespace App\Modules\Personnel\Services;

use App\Models\Personnel;
use App\Modules\Personnel\Support\ProfessionalPortfolio\ProfessionalPortfolioPermissionMatrix;

class PersonnelRowActionService
{
    /**
     * @param  array{
     *     can_edit?: bool,
     *     can_delete?: bool,
     *     can_view_portfolio?: bool,
     *     can_manage_my_hr_accounts?: bool,
     *     can_manage_onboarding_documents?: bool,
     *     can_manage_learning_materials?: bool
     * }|null  $capabilities
     * @return PersonnelRowActionDescriptor[]
     */
    public function build(Personnel $personnel, string $status, ?array $capabilities = null): array
    {
        $capabilities = array_replace([
            'can_edit' => false,
            'can_delete' => false,
            'can_view_portfolio' => false,
            'can_manage_my_hr_accounts' => false,
            'can_manage_onboarding_documents' => false,
            'can_manage_learning_materials' => false,
        ], $capabilities ?? $this->resolveCapabilities());
        $actions = [];

        if ($status !== 'deleted') {
            if ($capabilities['can_view_portfolio']) {
                $actions[] = PersonnelRowActionDescriptor::action(
                    id: 'professional-portfolio',
                    label: __('personnel::common.actions.professional_portfolio'),
                    icon: 'icons.briefcase-outline-icon',
                    actionPayload: [
                        'type' => 'open',
                        'menu' => 'professional-portfolio',
                        'value' => $personnel->id,
                    ],
                    inMenu: true,
                );
            }

            if ($capabilities['can_manage_my_hr_accounts']) {
                $actions[] = PersonnelRowActionDescriptor::action(
                    id: 'my-hr-account',
                    label: __('personnel::common.actions.self_service_account'),
                    icon: 'icons.key-icon',
                    actionPayload: [
                        'type' => 'open',
                        'menu' => 'my-hr-account',
                        'value' => $personnel->id,
                    ],
                    inMenu: true,
                );
            }

            if ($capabilities['can_manage_onboarding_documents']) {
                $actions[] = PersonnelRowActionDescriptor::action(
                    id: 'onboarding-documents',
                    label: __('personnel::common.actions.onboarding_documents'),
                    icon: 'icons.onboarding-library-icon',
                    actionPayload: [
                        'type' => 'open',
                        'menu' => 'onboarding-documents',
                        'value' => $personnel->id,
                    ],
                    inMenu: true,
                );
            }

            if ($capabilities['can_manage_learning_materials']) {
                $actions[] = PersonnelRowActionDescriptor::action(
                    id: 'learning-materials',
                    label: __('personnel::common.actions.learning_materials'),
                    icon: 'icons.learning-library-icon',
                    actionPayload: [
                        'type' => 'open',
                        'menu' => 'learning-materials',
                        'value' => $personnel->id,
                    ],
                    inMenu: true,
                );
            }

            if ($capabilities['can_edit']) {
                $actions[] = PersonnelRowActionDescriptor::action(
                    id: 'edit',
                    label: __('personnel::common.actions.edit'),
                    icon: 'icons.profile-icon',
                    actionPayload: [
                        'type' => 'open',
                        'menu' => 'edit-personnel',
                        'value' => $personnel->id,
                    ],
                );

                $actions[] = PersonnelRowActionDescriptor::action(
                    id: 'files',
                    label: __('personnel::common.actions.files'),
                    icon: 'icons.files-icon',
                    actionPayload: [
                        'type' => 'open',
                        'menu' => 'show-files',
                        'value' => $personnel->tabel_no,
                    ],
                    inMenu: true,
                );

                $actions[] = PersonnelRowActionDescriptor::link(
                    id: 'print',
                    label: __('personnel::common.actions.print'),
                    icon: 'icons.print-outline-icon',
                    href: route('print.personnel', $personnel->id),
                    inMenu: true,
                );

                $actions[] = PersonnelRowActionDescriptor::link(
                    id: 'cv',
                    label: __('personnel::common.actions.cv'),
                    icon: 'icons.cv-outline',
                    href: route('print.cv', $personnel->id),
                    inMenu: true,
                );

                $actions[] = PersonnelRowActionDescriptor::action(
                    id: 'information',
                    label: __('personnel::common.actions.information'),
                    icon: 'icons.profile-outline-icon',
                    actionPayload: [
                        'type' => 'open',
                        'menu' => 'show-information',
                        'value' => $personnel->tabel_no,
                    ],
                    inMenu: true,
                );

                $actions[] = PersonnelRowActionDescriptor::action(
                    id: 'vacations',
                    label: __('personnel::common.actions.vacations'),
                    icon: 'icons.vacation-outline-icon',
                    actionPayload: [
                        'type' => 'open',
                        'menu' => 'show-vacations',
                        'value' => $personnel->tabel_no,
                    ],
                    inMenu: true,
                );

                if ($capabilities['can_delete']) {
                    $actions[] = PersonnelRowActionDescriptor::action(
                        id: 'delete',
                        label: __('personnel::common.actions.delete'),
                        icon: 'icons.delete-icon',
                        actionPayload: [
                            'type' => 'delete',
                            'value' => $personnel->tabel_no,
                        ],
                        confirmMessage: __('personnel::common.messages.delete_data_confirm'),
                        wireTarget: 'setDeletePersonnel'
                    );
                }
            }
        }

        if ($status === 'deleted' && $capabilities['can_edit']) {
            $actions[] = PersonnelRowActionDescriptor::action(
                id: 'restore',
                label: __('personnel::common.actions.restore'),
                icon: 'icons.recover',
                actionPayload: [
                    'type' => 'restore',
                    'value' => $personnel->tabel_no,
                ],
                iconProps: [
                    'color' => 'text-teal-500',
                    'hover' => 'text-teal-600',
                ],
            );

            if ($capabilities['can_delete']) {
                $actions[] = PersonnelRowActionDescriptor::action(
                    id: 'force-delete',
                    label: __('personnel::common.actions.force_delete'),
                    icon: 'icons.force-delete',
                    actionPayload: [
                        'type' => 'force-delete',
                        'value' => $personnel->tabel_no,
                    ],
                    confirmMessage: __('personnel::common.messages.remove_data_confirm'),
                    wireTarget: 'forceDeleteData',
                );
            }
        }

        return array_values($actions);
    }

    /**
     * @return array{
     *     can_edit: bool,
     *     can_delete: bool,
     *     can_view_portfolio: bool,
     *     can_manage_my_hr_accounts: bool,
     *     can_manage_onboarding_documents: bool,
     *     can_manage_learning_materials: bool
     * }
     */
    protected function resolveCapabilities(): array
    {
        $user = auth()->user();

        return [
            'can_edit' => $user?->can('edit-personnels') ?? false,
            'can_delete' => $user?->can('delete-personnels') ?? false,
            'can_view_portfolio' => ProfessionalPortfolioPermissionMatrix::canViewPortfolio($user),
            'can_manage_my_hr_accounts' => $user?->can('manage-my-hr-accounts') ?? false,
            'can_manage_onboarding_documents' => ($user?->can('assign-onboarding-documents') ?? false) || ($user?->can('manage-onboarding-document-templates') ?? false),
            'can_manage_learning_materials' => ($user?->can('assign-employee-content') ?? false) || ($user?->can('manage-employee-content-library') ?? false),
        ];
    }
}
