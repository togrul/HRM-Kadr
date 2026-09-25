<?php

namespace App\Modules\Personnel\Livewire\MyHr;

use App\Models\Personnel;
use App\Modules\Personnel\Application\Services\MyHr\MyHrHierarchyReadService;
use App\Modules\Personnel\Support\MyHr\MyHrAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class MyHrHierarchy extends Component
{
    public int $personnelId;

    public function mount(MyHrAccess $access, int $personnelId): void
    {
        $access->authorize(Auth::user());
        abort_unless($access->canAccess(Auth::user(), 'view-own-hierarchy'), 403);
        abort_if($personnelId <= 0, 404);

        $this->personnelId = $personnelId;
    }

    #[Computed]
    public function payload(): array
    {
        return app(MyHrHierarchyReadService::class)->build($this->personnel());
    }

    protected function personnel(): Personnel
    {
        return Personnel::query()
            ->with([
                'position:id,name,approval_rank,is_approval_target',
                'structure:id,parent_id,name',
            ])
            ->select(['id', 'surname', 'name', 'patronymic', 'position_id', 'structure_id'])
            ->findOrFail($this->personnelId);
    }

    public function render(): View
    {
        return view('personnel::livewire.personnel.my-hr.hierarchy');
    }
}
