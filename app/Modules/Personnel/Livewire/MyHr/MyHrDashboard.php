<?php

namespace App\Modules\Personnel\Livewire\MyHr;

use App\Models\Leave;
use App\Models\OnboardingDocumentAssignment;
use App\Models\Personnel;
use App\Models\PersonnelBusinessTrip;
use App\Models\PersonnelVacation;
use App\Modules\Personnel\Support\MyHr\MyHrAccess;
use App\Modules\Personnel\Support\MyHr\MyHrTabs;
use App\Services\Vacation\VacationBalanceService;
use App\Support\Livewire\InteractsWithTabbedWorkspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * @property-read bool $hasPersonnelLink
 * @property-read Personnel|null $personnel
 */
class MyHrDashboard extends Component
{
    use InteractsWithTabbedWorkspace;

    public ?int $personnelId = null;

    public string $activeTab = 'overview';

    /** Create form the requests tab should open when the employee arrives from a quick link. */
    public string $pendingRequestForm = '';

    public function mount(MyHrAccess $access): void
    {
        $access->authorize(Auth::user());

        $this->personnelId = $access->resolvePersonnelId(Auth::user());
        $this->bootActiveTabFromRequest();
    }

    public function setActiveTab(string $tab): void
    {
        $this->pendingRequestForm = '';
        $this->switchTab($tab);
    }

    /** Quick link on the overview: jump to a tab, optionally opening one of its forms. */
    #[On('my-hr:goto')]
    public function goto(string $tab, string $form = ''): void
    {
        $this->switchTab($tab);
        $this->pendingRequestForm = $tab === 'requests' ? $form : '';
    }

    #[Computed]
    public function hasPersonnelLink(): bool
    {
        return $this->personnelId !== null && $this->personnelId > 0;
    }

    #[Computed]
    public function personnel(): ?Personnel
    {
        if (! $this->hasPersonnelLink) {
            return null;
        }

        return Personnel::query()
            ->select(['id', 'tabel_no', 'surname', 'name', 'patronymic'])
            ->find($this->personnelId);
    }

    /**
     * Counts shown beside the panel items — plain totals ("how many rows the tab holds"),
     * gathered as correlated sub-selects so the panel costs one round trip, not five.
     * The request counts include trashed rows because the requests tab lists them too; a
     * panel number that disagrees with the list it opens is worse than no number.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function panelCounts(): array
    {
        $personnel = $this->personnel;
        $user = Auth::user();

        if (! $personnel || ! $user) {
            return [];
        }

        $tabelNo = $personnel->tabel_no;
        $countOf = fn (Builder $query): Builder => $query->selectRaw('count(*)');

        $row = DB::query()
            ->selectSub($countOf(Leave::query()->withTrashed()->where('tabel_no', $tabelNo)), 'leaves')
            ->selectSub($countOf(PersonnelVacation::query()->withTrashed()->where('tabel_no', $tabelNo)), 'vacations')
            ->selectSub($countOf(PersonnelBusinessTrip::query()->withTrashed()->where('tabel_no', $tabelNo)), 'trips')
            ->selectSub($countOf($user->notifications()->getQuery()), 'notifications')
            ->selectSub($countOf(OnboardingDocumentAssignment::query()->where('personnel_id', $personnel->id)), 'onboarding')
            ->first();

        return [
            'requests' => (int) $row->leaves + (int) $row->vacations + (int) $row->trips,
            'notifications' => (int) $row->notifications,
            'onboarding' => (int) $row->onboarding,
        ];
    }

    /**
     * This year's vacation balance, or null when the yearly allocation has not produced a
     * row for the employee yet (the panel simply drops the section then).
     *
     * @return array{total:int,used:int,remaining:int}|null
     */
    #[Computed]
    public function vacationBalance(): ?array
    {
        $personnel = $this->personnel;

        return $personnel
            ? app(VacationBalanceService::class)->storedSnapshot($personnel, (int) now()->year)
            : null;
    }

    /**
     * Request forms offered by the header's "Yeni ərizə" menu.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function createForms(): array
    {
        return app(MyHrAccess::class)->allowedRequestForms(Auth::user());
    }

    /**
     * @return array<int, string>
     */
    public function tabs(): array
    {
        return MyHrTabs::all();
    }

    protected function allowedTabs(): array
    {
        return MyHrTabs::all();
    }

    public function render()
    {
        return view('personnel::livewire.personnel.my-hr.dashboard');
    }
}
