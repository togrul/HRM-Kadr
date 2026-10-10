<?php

namespace App\Modules\Personnel\Livewire\MyHr;

use App\Models\Payslip;
use App\Models\Personnel;
use App\Modules\Payroll\Domain\Contracts\PayslipReadRepository;
use App\Modules\Personnel\Livewire\MyHr\Concerns\ResolvesOwnPersonnel;
use App\Modules\Personnel\Support\MyHr\MyHrAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class MyHrPayslips extends Component
{
    use ResolvesOwnPersonnel;

    public ?int $selectedPayslipId = null;

    public function mount(MyHrAccess $access, ?int $personnelId = null): void
    {
        $access->authorize(Auth::user());
        $this->bindOwnPersonnel($personnelId);
    }

    protected function tabelNo(): ?string
    {
        return Personnel::query()->whereKey($this->personnelId)->value('tabel_no');
    }

    /**
     * @return Collection<int,Payslip>
     */
    #[Computed]
    public function payslips(): Collection
    {
        $tabelNo = $this->tabelNo();

        if (! $tabelNo) {
            return collect();
        }

        return app(PayslipReadRepository::class)->lockedPayslipsFor($tabelNo);
    }

    #[Computed]
    public function selectedPayslip(): ?Payslip
    {
        $tabelNo = $this->tabelNo();

        if (! $this->selectedPayslipId || ! $tabelNo) {
            return null;
        }

        return app(PayslipReadRepository::class)->payslipFor($this->selectedPayslipId, $tabelNo);
    }

    public function viewPayslip(int $payslipId): void
    {
        $tabelNo = $this->tabelNo();

        // Yalnız öz (kilidlənmiş) vərəqəsi açılır — başqasının id-si 404 verir.
        abort_unless($tabelNo && app(PayslipReadRepository::class)->payslipFor($payslipId, $tabelNo), 404);

        $this->selectedPayslipId = $payslipId;
    }

    public function closePayslip(): void
    {
        $this->selectedPayslipId = null;
    }

    public function render(): View
    {
        return view('personnel::livewire.personnel.my-hr.payslips');
    }
}
