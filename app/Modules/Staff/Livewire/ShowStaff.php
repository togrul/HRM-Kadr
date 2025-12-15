<?php

namespace App\Modules\Staff\Livewire;

use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class ShowStaff extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $structureModel;

    public $positionModel;

    public $title;

    public function mount()
    {
        $this->authorize('viewAny', \App\Models\StaffSchedule::class);
        $structure = Structure::where('id', $this->structureModel)->value('name');
        $position = Position::where('id', $this->positionModel)->value('name');
        $this->title = "{$structure}($position)";
    }

    public function render()
    {
        $staffs = Personnel::with('structure')
            ->where('structure_id', $this->structureModel)
            ->where('position_id', $this->positionModel)
            ->active()
            ->paginate(20);

        return view('staff::livewire.staff-schedule.show-staff', compact('staffs'));
    }
}
