<?php

namespace App\Modules\Payroll\Livewire\Tabs;

use App\Models\EmployeeLoan;
use App\Modules\Payroll\Application\Services\LoanService;
use App\Support\Livewire\SearchesPersonnel;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * Loans and salary advances of one employee. The shell can open it on an employee
 * (from the runs overview) by passing tabelNo + label.
 */
class LoansTab extends PayrollTab
{
    use SearchesPersonnel;

    public array $loanForm = [
        'type' => 'loan',
        'principal' => '',
        'monthly_installment' => '',
        'currency' => 'AZN',
        'start_on' => '',
        'note' => '',
    ];

    public function mount(?string $tabelNo = null, ?string $label = null): void
    {
        parent::mount();

        if ($tabelNo !== null) {
            $this->selectPersonnel($tabelNo, (string) $label);
        }
    }

    protected function viewName(): string
    {
        return 'loans';
    }

    #[Computed]
    public function loans(): Collection
    {
        if (! $this->selectedTabelNo) {
            return collect();
        }

        return EmployeeLoan::query()
            ->where('tabel_no', $this->selectedTabelNo)
            ->orderByDesc('id')
            ->get();
    }

    public function saveLoan(LoanService $service): void
    {
        abort_unless($this->canManage(), 403);
        abort_unless($this->selectedTabelNo !== null, 422);

        $data = $this->validate([
            'loanForm.type' => 'required|in:loan,advance',
            'loanForm.principal' => 'required|numeric|min:0.01',
            'loanForm.monthly_installment' => 'required|numeric|min:0.01',
            'loanForm.currency' => 'required|string|size:3',
            'loanForm.start_on' => 'required|date',
            'loanForm.note' => 'nullable|string|max:2000',
        ], attributes: $this->fieldLabels([
            'loanForm.type' => 'loan_type',
            'loanForm.principal' => 'principal',
            'loanForm.monthly_installment' => 'monthly_installment',
            'loanForm.currency' => 'currency',
            'loanForm.start_on' => 'start_on',
        ]))['loanForm'];

        $service->createLoan($this->selectedTabelNo, $data);

        $this->loanForm = ['type' => 'loan', 'principal' => '', 'monthly_installment' => '', 'currency' => 'AZN', 'start_on' => '', 'note' => ''];
        $this->announce('saved');
    }

    public function deleteLoan(int $loanId): void
    {
        abort_unless($this->canManage(), 403);
        EmployeeLoan::whereKey($loanId)->delete();
        $this->announce('deleted');
    }
}
