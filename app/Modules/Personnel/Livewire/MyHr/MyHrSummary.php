<?php

namespace App\Modules\Personnel\Livewire\MyHr;

use App\Models\Personnel;
use Livewire\Attributes\Computed;
use Livewire\Component;

class MyHrSummary extends Component
{
    public int $personnelId;

    public function mount(int $personnelId): void
    {
        abort_if($personnelId <= 0, 404);

        $this->personnelId = $personnelId;
    }

    #[Computed]
    public function personnel(): Personnel
    {
        return Personnel::query()
            ->select([
                'id',
                'tabel_no',
                'surname',
                'name',
                'patronymic',
                'email',
                'phone',
                'mobile',
                'structure_id',
                'position_id',
                'join_work_date',
            ])
            ->with([
                'position:id,name',
                'structure' => fn ($query) => $query
                    ->select('id', 'parent_id', 'name')
                    ->withRecursive('parent', false),
            ])
            ->findOrFail($this->personnelId);
    }

    public function render()
    {
        return view('personnel::livewire.personnel.my-hr.summary');
    }
}
