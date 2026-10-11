<?php

namespace App\Modules\Vacation\Livewire;

use App\Modules\Vacation\Application\Services\FifteenYearSeniorityFix;
use App\Services\StructureService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Admin → Məzuniyyət normaları → «Staja görə» → «Düz 15 il düzəlişi» (ƏM m.116.1,
 * docs/vacation-legal-basis.md, (b-3)): təsirlənən iş illərini tapır, önizləmə göstərir, HR
 * təsdiqləyəndə işləyən işçilərə fərqi «düzəliş» kimi yazır. Xitam olunmuş işçilər ayrıca
 * siyahıdadır (kompensasiya fərqi — mühasibatlıq üçün). Yalnız istifadəçinin struktur
 * görünürlüyündəki işçilər.
 */
class SeniorityFifteenFix extends Component
{
    /** @var array{active: list<array<string, mixed>>, terminated: list<array<string, mixed>>}|null */
    #[Locked]
    public ?array $preview = null;

    public function mount(): void
    {
        Gate::authorize('access-admin');
    }

    public function scan(FifteenYearSeniorityFix $fix, StructureService $structures): void
    {
        Gate::authorize('access-admin');

        $this->preview = $fix->preview($structures->scopeFor(auth()->user()));
    }

    /** Asks the global confirm modal; on confirm it calls apply(). */
    public function confirmApply(): void
    {
        Gate::authorize('access-admin');

        $pending = collect($this->preview['active'] ?? [])->where('applied', false)->count();

        if ($pending === 0) {
            $this->dispatch('notify', type: 'info', message: __('vacation::seniority_fix.messages.nothing'));

            return;
        }

        $this->dispatch(
            'confirm-action',
            title: __('vacation::seniority_fix.confirm.title'),
            message: __('vacation::seniority_fix.confirm.text', ['count' => $pending]),
            confirmText: __('vacation::seniority_fix.actions.apply'),
            tone: 'amber',
            wireId: $this->getId(),
            method: 'apply',
        );
    }

    public function apply(FifteenYearSeniorityFix $fix, StructureService $structures): void
    {
        Gate::authorize('access-admin');

        $scope = $structures->scopeFor(auth()->user());
        $tabelNos = collect($this->preview['active'] ?? [])->where('applied', false)->pluck('tabel_no')->unique()->values()->all();
        $written = $tabelNos === [] ? 0 : $fix->apply($scope, auth()->id(), $tabelNos);

        $this->preview = $fix->preview($scope);
        $this->dispatch('notify', type: 'success', message: __('vacation::seniority_fix.messages.applied', ['count' => $written]));
    }

    public function render(): View
    {
        return view('vacation::livewire.vacation.seniority-fifteen-fix', [
            'lawDays' => FifteenYearSeniorityFix::lawDays(),
        ]);
    }
}
