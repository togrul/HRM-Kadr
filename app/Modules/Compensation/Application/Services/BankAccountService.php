<?php

namespace App\Modules\Compensation\Application\Services;

use App\Models\EmployeeBankAccount;
use Illuminate\Support\Facades\DB;

class BankAccountService
{
    /**
     * Create or update an employee's bank account. An employee has at most one primary
     * account: saving one as primary demotes every other account of the same employee.
     *
     * @param  array<string,mixed>  $data
     */
    public function save(string $tabelNo, array $data, ?int $accountId = null): EmployeeBankAccount
    {
        $data['tabel_no'] = $tabelNo;

        return DB::transaction(function () use ($tabelNo, $data, $accountId): EmployeeBankAccount {
            if (! empty($data['is_primary'])) {
                EmployeeBankAccount::query()
                    ->where('tabel_no', $tabelNo)
                    ->when($accountId, fn ($q) => $q->whereKeyNot($accountId))
                    ->update(['is_primary' => false]);
            }

            if ($accountId) {
                $account = EmployeeBankAccount::query()->where('tabel_no', $tabelNo)->findOrFail($accountId);
                $account->update($data);

                return $account;
            }

            return EmployeeBankAccount::create($data);
        });
    }

    public function delete(int $accountId): void
    {
        EmployeeBankAccount::whereKey($accountId)->delete();
    }
}
