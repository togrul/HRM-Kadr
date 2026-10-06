<?php

namespace App\Modules\Compensation\Livewire\Tabs;

use App\Modules\Compensation\Application\Services\CompensationService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Reactive;

/**
 * Pay history of the employee picked in the workspace shell. Read-only.
 */
class HistoryTab extends CompensationTab
{
    #[Reactive]
    public ?string $tabelNo = null;

    protected function viewName(): string
    {
        return 'history';
    }

    #[Computed]
    public function history(): Collection
    {
        if (! $this->tabelNo) {
            return collect();
        }

        return app(CompensationService::class)->historyFor($this->tabelNo);
    }
}
