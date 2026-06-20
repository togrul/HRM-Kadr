<?php

namespace Tests\Fixtures\Livewire;

use App\Livewire\Concerns\ConfirmsDestructiveActions;
use Livewire\Component;

class DeleteConfirmationHarness extends Component
{
    use ConfirmsDestructiveActions;

    public ?int $deletedId = null;

    public function requestDelete(int $id = 42): void
    {
        $this->confirmDeletion(
            action: 'deleteRecord',
            parameters: ['id' => $id],
            message: 'Delete training record?',
            description: 'Record #'.$id,
            confirmLabel: 'Remove now',
        );
    }

    public function requestDeleteByIndex(int $id = 42): void
    {
        $this->confirmDeletion(
            action: 'deleteRecord',
            parameters: [$id],
            message: 'Delete training record?',
            description: 'Record #'.$id,
            confirmLabel: 'Remove now',
        );
    }

    public function deleteRecord(int $id): void
    {
        $this->deletedId = $id;
    }

    public function render()
    {
        return <<<'BLADE'
        <div>
            <div>{{ $deletedId }}</div>
            <x-ui.delete-confirmation-modal />
        </div>
        BLADE;
    }
}
