<?php

namespace App\Modules\Compensation\Contracts;

use App\Models\EmployeeCompensation;
use Illuminate\Support\Carbon;

/**
 * Sanctioned cross-module surface through which approved/reversed orders (hire,
 * transfer, termination) keep compensation in step. Consumers depend on THIS
 * interface — never on the concrete Compensation service.
 *
 * @see \App\Modules\Compensation\Application\Services\CompensationService
 */
interface OrderCompensationSync
{
    public function createDraftForHire(string $tabelNo, float $baseAmount, ?string $currency, ?Carbon $effectiveFrom, ?string $orderNo): ?EmployeeCompensation;

    public function suggestRegradeFromTransfer(string $tabelNo, int $positionId, ?string $orderNo): ?EmployeeCompensation;

    public function removeTransferSuggestion(?string $orderNo): void;

    public function endActiveForTermination(string $tabelNo, ?Carbon $date = null): ?EmployeeCompensation;

    public function reactivateAfterTermination(string $tabelNo): ?EmployeeCompensation;
}
