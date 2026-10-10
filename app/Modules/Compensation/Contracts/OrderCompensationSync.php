<?php

namespace App\Modules\Compensation\Contracts;

use App\Models\EmployeeCompensation;
use Illuminate\Support\Carbon;

/**
 * Sanctioned cross-module surface through which approved/reversed orders (hire,
 * transfer, termination, salary change, substitution) keep compensation in step. Consumers depend on THIS
 * interface — never on the concrete Compensation service.
 *
 * @see \App\Modules\Compensation\Application\Services\CompensationService
 */
interface OrderCompensationSync
{
    public function createDraftForHire(string $tabelNo, float $baseAmount, ?string $currency, ?Carbon $effectiveFrom, ?string $orderNo): ?EmployeeCompensation;

    public function discardHireDraft(string $tabelNo, ?string $orderNo): void;

    public function suggestRegradeFromTransfer(string $tabelNo, int $positionId, ?string $orderNo): ?EmployeeCompensation;

    public function removeTransferSuggestion(?string $orderNo): void;

    public function endActiveForTermination(string $tabelNo, ?Carbon $date = null): ?EmployeeCompensation;

    public function reactivateAfterTermination(string $tabelNo): ?EmployeeCompensation;

    /**
     * A salary-change order: assign a new active compensation with $baseAmount from
     * $effectiveFrom (regime, pay grade and component lines carried over from the current
     * one), ending the active ones the day before. Returns the state reverse needs, or
     * null when no compensation regime exists to assign under.
     *
     * @return array{compensation_id:int,ended:list<array{id:int,status:string,effective_to:?string}>}|null
     */
    public function changeSalaryFromOrder(string $tabelNo, float $baseAmount, Carbon $effectiveFrom, ?string $orderNo): ?array;

    /**
     * Undo changeSalaryFromOrder(): remove the assigned compensation and put the ended
     * ones back as they were.
     *
     * @param  array{compensation_id?:int,ended?:list<array{id:int,status:string,effective_to:?string}>}  $state
     */
    public function revertSalaryChange(array $state): void;

    /**
     * A substitution order: record that the employee performs a colleague's duties for
     * the period, with the extra pay agreed. Returns the record id, or null when the
     * substitution register is not installed.
     *
     * @param  array{substituted_tabel_no?:?string,substituted_name?:?string,substituted_position_id?:?int,start_date:string,end_date?:?string,extra_pay_percent?:?float,extra_pay_amount?:?float,order_no?:?string}  $terms
     */
    public function recordSubstitution(string $tabelNo, array $terms, string $sourceKey): ?int;

    /**
     * Remove the substitution recorded under $sourceKey. Safe when it is already gone.
     */
    public function removeSubstitution(string $sourceKey): void;
}
