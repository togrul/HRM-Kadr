<?php

namespace App\Modules\Personnel\Livewire;

use App\Helpers\UsefulHelpers;
use App\Models\Personnel;
use App\Models\Vacation;
use App\Models\VacationBalanceEntry;
use App\Services\Vacation\VacationBalanceService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * İşçi kartı → Məzuniyyətlər: iş illəri üzrə hüquq (əsas + staj + uşaq + şərait), istifadə və
 * qalıq, planlaşdırılmış ay. Admin köhnə məlumatın köçürülməsi üçün açılış qalığı daxil edir
 * (redaktə yoxdur — silinib yenidən əlavə olunur). Köhnə təqvim ili sətirləri tarixçə kimi görünür.
 *
 * @property-read list<array<string, mixed>> $workYears
 */
#[On('vacation-updated')]
class VacationList extends Component
{
    use AuthorizesRequests;

    public string $title;

    #[Locked]
    public string $personnelModel;

    public $personnelModelData;

    public ?int $reservedSequence = null;

    public ?int $reservedMonthId = null;

    public array $months = [];

    public ?int $openingSequence = null;

    public ?int $openingDays = null;

    public string $openingNote = '';

    public ?int $pendingOpeningId = null;

    public function mount(): void
    {
        $this->personnelModelData = Personnel::query()
            ->where('tabel_no', $this->personnelModel)
            ->withTrashed()
            ->firstOrFail();

        $this->authorize('update', $this->personnelModelData);

        $this->months = UsefulHelpers::monthsList(config('app.locale'));

        $this->title = __('personnel::vacations.titles.vacations_for', [
            'name' => "<span class='text-blue-500'>{$this->personnelModelData->fullname}</span>",
        ]);
    }

    public function updateMonth(int $sequence): void
    {
        $this->reservedSequence = $sequence;
        $this->reservedMonthId = collect($this->workYears)->where('kind', 'annual')->firstWhere('sequence', $sequence)['reserved_month'] ?? null;
    }

    public function setMonth(VacationBalanceService $balances): void
    {
        $this->authorize('update', $this->personnelModelData);

        if ($this->reservedSequence !== null) {
            $balances->reserveMonth($this->personnelModelData, $this->reservedSequence, $this->reservedMonthId ?: null);
        }

        $this->resetVacation();
        unset($this->workYears);
        $this->dispatch('vacation-updated', __('personnel::vacations.messages.updated'));
    }

    public function resetVacation(): void
    {
        $this->reset('reservedSequence', 'reservedMonthId');
    }

    public function goToVacations(string $start, string $end): void
    {
        session()->flash('vacation-updated', [
            'vacation_status' => 'all',
            'date' => [
                'min' => Carbon::parse($start)->format('d.m.Y'),
                'max' => Carbon::parse($end)->format('d.m.Y'),
            ],
            'fullname' => $this->personnelModelData->fullname,
        ]);

        $this->redirect(route('vacations.list'));
    }

    public function addOpening(VacationBalanceService $balances): void
    {
        Gate::authorize('access-admin');

        $this->validate([
            'openingSequence' => ['required', 'integer', 'min:1'],
            'openingDays' => ['required', 'integer', 'min:1', 'max:366'],
            'openingNote' => ['nullable', 'string', 'max:255'],
        ], [], [
            'openingSequence' => __('personnel::vacations.labels.work_year'),
            'openingDays' => __('personnel::vacations.labels.opening_days'),
            'openingNote' => __('personnel::vacations.labels.note'),
        ]);

        try {
            $balances->addOpening($this->personnelModelData, (int) $this->openingSequence, (int) $this->openingDays, trim($this->openingNote) ?: null, auth()->id());
        } catch (DomainException $e) {
            $this->addError('openingDays', $e->getMessage());

            return;
        }

        $this->reset('openingSequence', 'openingDays', 'openingNote');
        unset($this->workYears, $this->openingEntries);
        $this->dispatch('notify', type: 'success', message: __('personnel::vacations.messages.opening_added'));
    }

    public function confirmDeleteOpening(int $entryId): void
    {
        Gate::authorize('access-admin');
        $this->pendingOpeningId = $entryId;

        $this->dispatch(
            'confirm-action',
            title: __('personnel::vacations.confirm.delete_opening_title'),
            message: __('personnel::vacations.confirm.delete_opening_text'),
            confirmText: __('ui::common.swal.yes_delete_it'),
            tone: 'rose',
            wireId: $this->getId(),
            method: 'deleteOpening',
        );
    }

    public function deleteOpening(VacationBalanceService $balances): void
    {
        Gate::authorize('access-admin');

        if ($this->pendingOpeningId !== null) {
            $balances->deleteOpening($this->personnelModelData, $this->pendingOpeningId);
        }

        $this->pendingOpeningId = null;
        unset($this->workYears, $this->openingEntries);
        $this->dispatch('notify', type: 'success', message: __('ui::common.messages.record_deleted'));
    }

    public function render(): View
    {
        return view('personnel::livewire.personnel.vacation-list');
    }

    /**
     * İş illəri (ən yenisi yuxarıda), hər biri hüququn tərkibi və qalığı ilə. Yalnız oxuyur.
     *
     * @return list<array<string, mixed>>
     */
    #[Computed]
    public function workYears(): array
    {
        $today = Carbon::today();

        return array_reverse(array_map(
            fn ($balance): array => $balance->toArray($today),
            app(VacationBalanceService::class)->workYears($this->personnelModelData, $today),
        ));
    }

    /**
     * @return array{total:int,used:int,remaining:int}
     */
    #[Computed]
    public function totals(): array
    {
        $years = collect($this->workYears);

        return [
            'total' => (int) $years->sum('total'),
            'used' => (int) $years->sum(fn (array $y): int => $y['used'] + $y['compensated']),
            'remaining' => (int) $years->sum('remaining'),
        ];
    }

    /**
     * @return Collection<int, VacationBalanceEntry>
     */
    #[Computed]
    public function openingEntries(): Collection
    {
        return app(VacationBalanceService::class)->openingEntries($this->personnelModelData);
    }

    /**
     * Açılış qalığı yazıla biləcək iş illəri (işə qəbuldan bu günədək).
     *
     * @return list<array{id:int,label:string}>
     */
    #[Computed]
    public function openingYearOptions(): array
    {
        return array_reverse(array_map(
            fn ($period): array => ['id' => $period->sequence, 'label' => $period->label()],
            app(VacationBalanceService::class)->periodsFor($this->personnelModelData, Carbon::today()),
        ));
    }

    /**
     * @return Collection<int, Vacation>
     */
    #[Computed]
    public function legacyRows(): Collection
    {
        return Vacation::query()->where('tabel_no', $this->personnelModel)->orderByDesc('year')->get();
    }

    #[Computed]
    public function canManageOpening(): bool
    {
        return Gate::allows('access-admin');
    }

    #[Computed]
    public function monthOptions(): array
    {
        return collect($this->months)
            ->map(fn ($value, $label) => ['id' => $value, 'label' => $label])
            ->values()
            ->all();
    }
}
