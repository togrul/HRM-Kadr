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
                    label: __('Edit'),
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
                    label: __('Files'),
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
                    label: __('Print'),
                    icon: 'icons.print-outline-icon',
                    href: route('print.personnel', $personnel->id),
                    inMenu: true,
                );

                $actions[] = PersonnelRowActionDescriptor::link(
                    id: 'cv',
                    label: __('CV'),
                    icon: 'icons.cv-outline',
                    href: route('print.cv', $personnel->id),
                    inMenu: true,
                );

                $actions[] = PersonnelRowActionDescriptor::action(
                    id: 'information',
                    label: __('Information'),
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
                    label: __('Vacations'),
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
                        label: __('Delete'),
                        icon: 'icons.delete-icon',
                        actionPayload: [
                            'type' => 'delete',
                            'value' => $personnel->tabel_no,
                        ],
                        permission: 'delete-personnels',
                        confirmMessage: __('Are you sure you want to delete this data?'),
                        wireTarget: 'setDeletePersonnel'
                    );
                }
            }
        }

        if ($status === 'deleted' && auth()->user()?->can('edit-personnels')) {
            $actions[] = PersonnelRowActionDescriptor::action(
                id: 'restore',
                label: __('Restore'),
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
                    label: __('Force delete'),
                    icon: 'icons.force-delete',
                    actionPayload: [
                        'type' => 'force-delete',
                        'value' => $personnel->tabel_no,
                    ],
                    confirmMessage: __('Are you sure you want to remove this data?'),
                    wireTarget: 'forceDeleteData',
                );
            }
        }

        return array_values(array_filter($actions, fn (PersonnelRowActionDescriptor $action): bool => $action->visibleByPermission()));
    }
}

