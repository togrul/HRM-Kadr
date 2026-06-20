<?php

namespace App\Modules\Personnel\Support\Traits;

use App\Modules\Personnel\Services\PersonnelUiEvents;

trait DispatchesPersonnelUiEvents
{
    protected ?PersonnelUiEvents $personnelUiEventsInstance = null;

    protected function uiEventsService(): PersonnelUiEvents
    {
        return $this->personnelUiEventsInstance
            ??= resolve(PersonnelUiEvents::class);
    }

    protected function dispatchPersonnelStored(string $message): void
    {
        $this->dispatch($this->uiEventsService()->personnelSavedEvent(), $message);
    }

    protected function dispatchModalCloseEvent(): void
    {
        $this->dispatch($this->uiEventsService()->modalCloseEvent());
    }
}
