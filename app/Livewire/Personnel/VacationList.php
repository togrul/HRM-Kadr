<?php

namespace App\Livewire\Personnel;

use App\Helpers\UsefulHelpers;
use App\Livewire\Traits\SelectListTrait;
use App\Models\Personnel;
use App\Models\Vacation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

#[On('vacation-updated')]
class VacationList extends Component
{
    use AuthorizesRequests;
    use SelectListTrait;

    public string $title;

    #[Locked]
    public string $personnelModel;

    public $personnelModelData;

    public array $month = [];

    public Vacation $selectedVacation;

    public array $months = [];

    public function updateMonth(Vacation $vacation): void
    {
        $this->selectedVacation = $vacation;
        $this->month['reserved_date_month'] = $vacation->reserved_date_month
            ? [
                'id' => $vacation->reserved_date_month,
                'name' => array_search($vacation->reserved_date_month, $this->months),
            ]
            : ['id' => null, 'name' => '---'];
    }

    public function setMonth(): void
    {
        $this->selectedVacation->update([
            'reserved_date_month' => $this->month['reserved_date_month']['id'] ?? null,
        ]);
        $this->resetVacation();
        $this->dispatch('vacation-updated', __('Vacation is updated!'));
    }

    public function goToVacations(int $vacationYear)
    {
        $preparedQuery = [
            'vacation_status' => 'all',
            'date' => [
                'min' => "01.01.{$vacationYear}",
                'max' => "31.12.{$vacationYear}",
            ],
            'fullname' => $this->personnelModelData->fullname,
        ];
        session()->flash('vacation-updated', $preparedQuery);
        return $this->redirect(route('vacations.list'));
    }

    public function resetVacation(): void
    {
        $this->reset('selectedVacation');
    }

    public function mount()
    {
        $this->authorize('edit-personnels', $this->personnelModel);
        $this->months = UsefulHelpers::monthsList(config('app.locale'));
        $this->personnelModelData = Personnel::with(['yearlyVacation'])
            ->where('tabel_no', $this->personnelModel)
            ->withTrashed()
            ->firstOrFail();

        $this->title = __('Vacations') . ' - ' . "<span class='text-blue-500'>{$this->personnelModelData->fullname}</span>";
    }

    public function render()
    {
        return view('livewire.personnel.vacation-list');
    }
}
