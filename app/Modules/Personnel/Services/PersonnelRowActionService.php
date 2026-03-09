<?php

namespace App\Modules\Personnel\Services;

use App\Models\Personnel;

class PersonnelRowActionService
{
    /**
     * @return PersonnelRowActionDescriptor[]
     */
    public function build(Personnel $personnel, string $status): array
    {
        $actions = [];

        if ($status !== 'deleted') {
            if (auth()->user()?->can('edit-personnels')) {
                $actions[] = PersonnelRowActionDescriptor::action(
                    id: 'edit',
                    label: __('personnel::common.actions.edit'),
                    icon: 'icons.profile-icon',
                    actionPayload: [
                        'type' => 'open',
                        'menu' => 'edit-personnel',
                        'value' => $personnel->id,
                    ],
                    permission: 'edit-personnels',
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
                    permission: 'edit-personnels',
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
                    permission: 'edit-personnels',
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
                    permission: 'edit-personnels',
                );

                if (auth()->user()?->can('delete-personnels')) {
                    $actions[] = PersonnelRowActionDescriptor::action(
                        id: 'delete',
                        label: __('personnel::common.actions.delete'),
                        icon: 'icons.delete-icon',
                        actionPayload: [
                            'type' => 'delete',
                            'value' => $personnel->tabel_no,
                        ],
                        permission: 'delete-personnels',
                        confirmMessage: __('personnel::common.messages.delete_data_confirm'),
                        wireTarget: 'setDeletePersonnel'
                    );
                }
            }
        }

        if ($status === 'deleted' && auth()->user()?->can('edit-personnels')) {
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

            if (auth()->user()?->can('delete-personnels')) {
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

        return array_values(array_filter($actions, fn (PersonnelRowActionDescriptor $action): bool => $action->visibleByPermission()));
    }
}
