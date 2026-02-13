<?php

namespace App\Modules\Personnel\Services;

class PersonnelUiEvents
{
    public const MODAL_CLOSE = 'ui:modal-close';
    public const PERSONNEL_SAVED = 'personnelAdded';

    public function personnelSavedEvent(): string
    {
        return self::PERSONNEL_SAVED;
    }

    public function modalCloseEvent(): string
    {
        return self::MODAL_CLOSE;
    }

    /**
     * @return array<int, string>
     */
    public function sideModalCloseEvents(): array
    {
        return config('ui.modal_close_events', [self::MODAL_CLOSE]);
    }
}
