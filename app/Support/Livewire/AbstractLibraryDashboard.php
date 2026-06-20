<?php

namespace App\Support\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

abstract class AbstractLibraryDashboard extends Component
{
    use DownloadsReportsTable;
    use InteractsWithBulkTargetSelections;
    use InteractsWithTabbedWorkspace;
    use WithFileUploads;
    use WithPagination;

    public string $activeTab = 'general';

    public string $searchPersonnel = '';

    public string $searchStructure = '';

    public string $searchPosition = '';

    public array $selectedPersonnelIds = [];

    public array $selectedStructureIds = [];

    public array $selectedPositionIds = [];

    public function mount(): void
    {
        abort_unless($this->canView(), 403);
        $this->bootActiveTabFromRequest();
    }

    protected function allowedTabs(): array
    {
        return ['general', 'library', 'reports'];
    }

    protected function ensureTargetsSelected(bool $includeRecentHires, string $errorBagKey, string $message): bool
    {
        if ($this->selectedPersonnelIds !== [] || $this->selectedStructureIds !== [] || $this->selectedPositionIds !== [] || $includeRecentHires) {
            return true;
        }

        $this->addError($errorBagKey, $message);

        return false;
    }

    abstract public function canView(): bool;
}
