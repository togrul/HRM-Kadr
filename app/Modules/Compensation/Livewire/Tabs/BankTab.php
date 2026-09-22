<?php

namespace App\Modules\Compensation\Livewire\Tabs;

use App\Models\EmployeeBankAccount;
use App\Modules\Compensation\Application\Services\BankAccountService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;

/**
 * Salary bank accounts of the employee picked in the workspace shell.
 */
class BankTab extends CompensationTab
{
    #[Reactive]
    public ?string $tabelNo = null;

    #[Locked]
    public ?int $editingBankId = null;

    public array $bankForm = [
        'iban' => '',
        'bank_name' => '',
        'account_no' => '',
        'is_primary' => true,
        'is_active' => true,
    ];

    protected function viewName(): string
    {
        return 'bank';
    }

    protected function panels(): array
    {
        return ['bank'];
    }

    protected function resetPanel(string $panel): void
    {
        $this->cancelBank();
    }

    #[Computed]
    public function bankAccounts(): Collection
    {
        if (! $this->tabelNo) {
            return collect();
        }

        return EmployeeBankAccount::query()
            ->where('tabel_no', $this->tabelNo)
            ->orderByDesc('is_primary')
            ->orderByDesc('id')
            ->get();
    }

    public function editBank(int $id): void
    {
        $account = EmployeeBankAccount::query()->where('tabel_no', $this->tabelNo)->findOrFail($id);
        $this->editingBankId = $account->id;
        $this->panel = 'bank';
        $this->bankForm = [
            'iban' => $account->iban,
            'bank_name' => $account->bank_name ?? '',
            'account_no' => $account->account_no ?? '',
            'is_primary' => (bool) $account->is_primary,
            'is_active' => (bool) $account->is_active,
        ];
        $this->resetValidation();
    }

    public function saveBank(BankAccountService $service): void
    {
        $this->guardManage();
        abort_unless($this->tabelNo !== null, 422);

        $data = $this->validate([
            'bankForm.iban' => 'required|string|max:34',
            'bankForm.bank_name' => 'nullable|string|max:255',
            'bankForm.account_no' => 'nullable|string|max:64',
            'bankForm.is_primary' => 'boolean',
            'bankForm.is_active' => 'boolean',
        ], attributes: $this->fieldLabels([
            'bankForm.iban' => 'iban',
            'bankForm.bank_name' => 'bank_name',
            'bankForm.account_no' => 'account_no',
            'bankForm.is_primary' => 'is_primary',
            'bankForm.is_active' => 'is_active',
        ]))['bankForm'];

        $service->save($this->tabelNo, $data, $this->editingBankId);

        $this->cancelBank();
        $this->announce('saved');
    }

    public function deleteBank(int $id, BankAccountService $service): void
    {
        $this->guardManage();
        $service->delete($id);

        if ($this->editingBankId === $id) {
            $this->cancelBank();
        }

        $this->announce('deleted');
    }

    public function cancelBank(): void
    {
        $this->editingBankId = null;
        $this->bankForm = [
            'iban' => '', 'bank_name' => '', 'account_no' => '', 'is_primary' => true, 'is_active' => true,
        ];
        $this->panel = '';
        $this->resetValidation();
    }
}
