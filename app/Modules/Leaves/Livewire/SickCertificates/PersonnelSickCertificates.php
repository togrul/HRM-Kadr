<?php

namespace App\Modules\Leaves\Livewire\SickCertificates;

use App\Models\LeaveSickCertificate;
use App\Modules\Leaves\Application\Services\SickCertificateRegister;
use App\Modules\Leaves\Livewire\Concerns\ManagesSickCertificates;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The «Xəstəlik» section of the employee card: that person's certificates with the same
 * actions as the register, and «Yeni vərəqə» with the employee already chosen.
 */
#[On(['sick-certificates-changed'])]
class PersonnelSickCertificates extends Component
{
    use AuthorizesRequests;
    use ManagesSickCertificates;

    #[Locked]
    public string $tabelNo = '';

    public function mount(string $tabelNo): void
    {
        $this->authorize('viewAny', LeaveSickCertificate::class);
        $this->tabelNo = $tabelNo;
    }

    /**
     * @return Collection<int, LeaveSickCertificate>
     */
    #[Computed]
    public function certificates(): Collection
    {
        return app(SickCertificateRegister::class)->listing(['tabel_no' => $this->tabelNo])->limit(200)->get();
    }

    /**
     * @return array{total: int, open: int, closed: int, cancelled: int, days: int}
     */
    #[Computed]
    public function stats(): array
    {
        return app(SickCertificateRegister::class)->stats(['tabel_no' => $this->tabelNo]);
    }

    public function render(): View
    {
        return view('leaves::livewire.sick-certificates.personnel');
    }
}
