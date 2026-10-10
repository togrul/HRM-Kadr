<?php

namespace App\Modules\Payroll\Application\Services;

use App\Models\PayrollRun;
use App\Models\User;
use App\Services\StructureService;
use Illuminate\Database\Eloquent\Builder;

class PayrollRunReadService
{
    /** @return Builder<PayrollRun> */
    public function visibleQuery(?User $user): Builder
    {
        $query = PayrollRun::query();
        $scope = app(StructureService::class)->scopeFor($user);
        if ($scope->isAll()) {
            return $query;
        }
        if ($scope->isNone() || $user === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $runs) use ($scope, $user): void {
            $runs->where(function (Builder $populated) use ($scope): void {
                $populated->whereHas('payslips')
                    ->whereDoesntHave('payslips', fn (Builder $payslips) => $payslips
                        ->whereDoesntHave('personnel', fn (Builder $personnel) => $personnel->whereIn('structure_id', $scope->ids())));
            })->orWhere(fn (Builder $empty) => $empty->whereDoesntHave('payslips')->where('created_by', $user->getKey()));
        });
    }
}
