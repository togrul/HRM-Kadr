<?php

namespace App\Modules\Compensation\Contracts;

/**
 * Sanctioned read surface of the substitution register (əvəzetmə): payroll pays the extra
 * pay from it and the finance feed hands it over as a pay condition. Rows are written and
 * removed only by the substitution order (OrderCompensationSync), so a revoked order
 * simply stops appearing here.
 *
 * @see \App\Modules\Compensation\Application\Services\SubstitutionRegisterService
 */
interface SubstitutionRegister
{
    /**
     * Substitutions whose period overlaps [$from, $to] (an open end runs on), per staff
     * number of the substituting employee, oldest first.
     *
     * @param  list<string>  $tabelNos
     * @return array<string,list<array{id:int,substituted_tabel_no:?string,substituted_name:?string,substituted_position_id:?int,start_date:string,end_date:?string,extra_pay_percent:?float,extra_pay_amount:?float,order_no:?string}>>
     */
    public function overlapping(array $tabelNos, string $from, string $to): array;
}
