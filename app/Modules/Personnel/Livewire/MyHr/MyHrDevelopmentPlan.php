<?php

namespace App\Modules\Personnel\Livewire\MyHr;

use App\Models\Personnel;
use App\Modules\Personnel\Application\Services\MyHr\MyHrDevelopmentPlanReadService;
use App\Modules\Personnel\Support\MyHr\MyHrAccess;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class MyHrDevelopmentPlan extends Component
{
    public int $personnelId;

    public string $search = '';

    public string $statusFilter = 'all';

    public function mount(MyHrAccess $access, int $personnelId): void
    {
        $access->authorize(Auth::user());
        abort_if($personnelId <= 0, 404);

        $this->personnelId = $personnelId;
    }

    #[Computed]
    public function payload(): array
    {
        return app(MyHrDevelopmentPlanReadService::class)->build($this->personnel(), [
            'search' => $this->search,
            'status' => $this->statusFilter,
        ]);
    }

    protected function personnel(): Personnel
    {
        return Personnel::query()
            ->select(['id', 'tabel_no', 'surname', 'name', 'patronymic'])
            ->findOrFail($this->personnelId);
    }

    public function render()
    {
        return view('personnel::livewire.personnel.my-hr.development-plan');
    }
}
