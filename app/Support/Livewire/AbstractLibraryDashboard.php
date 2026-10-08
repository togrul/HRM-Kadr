<?php

namespace App\Support\Livewire;

use App\Livewire\Traits\SideModalAction;
use App\Support\Library\AbstractLibraryReadService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Shared shell of the learning and onboarding library pages. Both render the same view
 * (partials.library.dashboard) and differ only in the config array from libraryConfig().
 *
 * @property array<string, mixed> $assignmentForm Declared by each concrete dashboard with its own defaults.
 */
abstract class AbstractLibraryDashboard extends Component
{
    use DownloadsReportsTable;
    use InteractsWithBulkTargetSelections;
    use InteractsWithTabbedWorkspace;
    use SideModalAction;
    use WithFileUploads;
    use WithPagination;

    public string $activeTab = 'library';

    public string $searchPersonnel = '';

    public string $searchStructure = '';

    public string $searchPosition = '';

    public array $selectedPersonnelIds = [];

    public array $selectedStructureIds = [];

    public array $selectedPositionIds = [];

    public string $typeFilter = '';

    public string $statusFilter = 'all';

    public function mount(): void
    {
        abort_unless($this->canView(), 403);
        $this->bootActiveTabFromRequest();
    }

    protected function allowedTabs(): array
    {
        return ['library', 'assignments', 'reports'];
    }

    public function updated(string $property): void
    {
        if (in_array($property, [$this->libraryConfig()['search'], 'typeFilter', 'statusFilter'], true)) {
            $this->resetPage('libraryPage');
        }
    }

    public function openCreate(): void
    {
        abort_unless($this->libraryConfig()['can_manage'], 403);

        $this->resetLibraryForm();
        $this->resetValidation();
        $this->openSideMenu('library-create');
    }

    public function openAssign(?int $itemId = null): void
    {
        $config = $this->libraryConfig();
        abort_unless($config['can_assign'], 403);

        $this->assignmentForm[$config['assign_key']] = $itemId;
        $this->resetValidation();
        $this->openSideMenu('library-assign');
    }

    #[Computed]
    public function catalogPayload(): array
    {
        return $this->readService()->buildCatalog(
            (string) $this->{$this->libraryConfig()['search']},
            $this->typeFilter,
            in_array($this->statusFilter, AbstractLibraryReadService::CATALOG_STATUSES, true) ? $this->statusFilter : 'all',
            'libraryPage'
        );
    }

    protected function ensureTargetsSelected(bool $includeRecentHires, string $errorBagKey, string $message): bool
    {
        if ($this->selectedPersonnelIds !== [] || $this->selectedStructureIds !== [] || $this->selectedPositionIds !== [] || $includeRecentHires) {
            return true;
        }

        $this->addError($errorBagKey, $message);

        return false;
    }

    public function render(): View
    {
        return view('partials.library.dashboard', ['library' => $this->libraryConfig()]);
    }

    abstract public function canView(): bool;

    abstract protected function readService(): AbstractLibraryReadService;

    abstract protected function resetLibraryForm(): void;

    /**
     * Everything the shared view needs to tell the two libraries apart: translation
     * namespace, Livewire property/method names, permissions and create-form fields.
     *
     * @return array<string, mixed>
     */
    abstract protected function libraryConfig(): array;
}
