<?php

namespace App\Livewire\Outside;

use Livewire\Component;
use App\Models\LeaveType;
use App\Models\Personnel;
use App\Models\OrderStatus;
use Livewire\WithFileUploads;
use Livewire\Attributes\Computed;
use App\Livewire\Traits\SelectListTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AddLeave extends Component
{
    use AuthorizesRequests;
    use SelectListTrait;
    use WithFileUploads;

    public $title;

    public $leave = [];

    public string $personnelName = '';
    public string $assignedSearch = '';

    // protected function rules()
    // {
    //     return [
    //         'menu.name' => 'required|string|min:1',
    //         'menu.color' => 'required|string|min:1',
    //         'menu.order' => 'required|integer',
    //         'menu.url' => 'required|string|min:1',
    //         'menu.icon' => 'required|string|min:1',
    //         'menu.permission_id.id' => 'required|integer|exists:permissions,id'
    //     ];
    // }

    // protected function validationAttributes()
    // {
    //     return [
    //         'menu.name' => __('Name'),
    //         'menu.color' => __('Color'),
    //         'menu.order' => __('Order'),
    //         'menu.url' => __('URL'),
    //         'menu.icon' => __('Icon'),
    //         'menu.permission_id.id' => __('Permissions')
    //     ];
    // }

    // public function store()
    // {
    //     $this->validate();

    //     $data = $this->menu;
    //     $data['permission_id'] = $data['permission_id']['id'];
    //     Menu::create($data);

    //     $this->dispatch('menuAdded', __('Menu was added successfully!'));
    // }

    public function selectPersonnel(string $tabelNo, string $fullname,$key)
    {
        $this->leave[$key] = [
            'tabel_no' => $tabelNo,
            'fullname' => $fullname
        ];
        $this->reset(['personnelName', 'assignedSearch']);
    }

    public function removePersonnel(string $key)
    {
        unset($this->leave[$key]);
        $this->reset(['personnelName']);
    }

    #[Computed]
    public function personnelList()
    {
        $canSearch = (strlen($this->personnelName) > 2) || (strlen($this->assignedSearch) > 2);
        return $canSearch
             ? Personnel::query()
                ->when(! empty($this->personnelName), fn($q) => $q->nameLike($this->personnelName))
                ->when(! empty($this->assignedSearch), fn($q) => $q->nameLike($this->assignedSearch))
                 ->active()
                 ->whereNull('deleted_at')
                 ->get()
             : [];
    }

    public function mount()
    {
        // $this->authorize('manage-settings',$this->user);
        $this->title = __('Add leave');
        $this->leave = [];
    }

    public function render()
    {
        $leaveTypes = LeaveType::pluck('name','id');
        $statuses = OrderStatus::pluck('name', 'id');

        return view('livewire.outside.add-leave', compact('leaveTypes','statuses'));
    }
}
