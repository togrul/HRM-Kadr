<?php

namespace App\Modules\Personnel\Livewire;

use App\Helpers\UsefulHelpers;
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

    public string $title;

    #[Locked]
    public string $personnelModel;

    public $personnelModelData;

    public ?int $reservedMonthId = null;

    public Vacation $selectedVacation;

    public array $months = [];

    public function updateMonth(Vacation $vacation): void
    {
        $this->selectedVacation = $vacation;
        $this->reservedMonthId = $vacation->reserved_date_month;
    }

    public function setMonth(): void
    {
        $this->selectedVacation->update([
            'reserved_date_month' => $this->reservedMonthId ?: null,
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
        $this->reset('selectedVacation', 'reservedMonthId');
    }

    public function mount()
    {
        $this->personnelModelData = Personnel::with(['yearlyVacation'])
            ->where('tabel_no', $this->personnelModel)
            ->withTrashed()
            ->firstOrFail();

        $this->authorize('update', $this->personnelModelData);

        $this->months = UsefulHelpers::monthsList(config('app.locale'));

        $this->title = __('Vacations') . ' - ' . "<span class='text-blue-500'>{$this->personnelModelData->fullname}</span>";
    }

    public function render()
    {
        return view('personnel::livewire.personnel.vacation-list');
    }

    public function monthOptions(): array
    {
        return collect($this->months)
            ->map(fn ($value, $label) => ['id' => $value, 'label' => $label])
            ->values()
            ->all();
    }
}
