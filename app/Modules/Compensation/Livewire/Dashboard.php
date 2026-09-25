<?php

namespace App\Modules\Compensation\Livewire;

use App\Models\CompensationComponent;
use App\Models\EmployeeCompensation;
use App\Models\PayGrade;
use App\Models\PayScale;
use App\Support\Livewire\InteractsWithTabbedWorkspace;
use App\Support\Livewire\SearchesPersonnel;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Workspace shell: tab navigation, summary counters and the employee picker shared by the
 * assignments / bank / history tabs. Each tab is its own component under Livewire\Tabs.
 */
class Dashboard extends Component
{
    use InteractsWithTabbedWorkspace;
    use SearchesPersonnel;

    public string $activeTab = 'scales';

    public function mount(): void
    {
        abort_unless($this->canView(), 403);
        $this->bootActiveTabFromRequest();
    }

    protected function allowedTabs(): array
    {
        return ['scales', 'components', 'assignments', 'bank', 'history', 'statutory'];
    }

    #[Computed]
    public function allowedTabsList(): array
    {
        return $this->allowedTabs();
    }

    public function canView(): bool
    {
        return auth()->user()?->can('show-compensation') ?? false;
    }

    public function canManage(): bool
    {
        return auth()->user()?->can('manage-compensation') ?? false;
    }

    /** A tab saved or deleted something — re-render so the counters follow. */
    #[On('compensation-updated')]
    public function refreshSummary(): void
    {
        unset($this->summaryStats);
    }

    #[Computed]
    public function summaryStats(): array
    {
        return [
            ['key' => 'scales', 'value' => PayScale::query()->count(), 'accent' => 'bg-sky-500'],
            ['key' => 'grades', 'value' => PayGrade::query()->count(), 'accent' => 'bg-violet-500'],
            ['key' => 'components', 'value' => CompensationComponent::query()->where('is_active', true)->count(), 'accent' => 'bg-amber-400'],
            ['key' => 'assignments', 'value' => EmployeeCompensation::query()->where('status', 'active')->count(), 'accent' => 'bg-emerald-500'],
        ];
    }

    public function render(): View
    {
        return view('compensation::livewire.dashboard');
    }
}
