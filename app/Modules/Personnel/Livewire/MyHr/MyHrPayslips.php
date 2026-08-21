<?php

namespace App\Modules\Personnel\Livewire\MyHr;

use App\Models\Payslip;
use App\Models\Personnel;
use App\Modules\Payroll\Domain\Contracts\PayslipReadRepository;
use App\Modules\Personnel\Support\MyHr\MyHrAccess;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class MyHrPayslips extends Component
{
    public int $personnelId;

    public ?int $selectedPayslipId = null;

    public function mount(MyHrAccess $access, int $personnelId): void
    {
        $access->authorize(Auth::user());
        abort_if($personnelId <= 0, 404);

        $this->personnelId = $personnelId;
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
        $this->selectedPayslipId = $payslipId;
    }

    public function closePayslip(): void
    {
        $this->selectedPayslipId = null;
    }

    public function render()
    {
        return view('personnel::livewire.personnel.my-hr.payslips');
    }
}
