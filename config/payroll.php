<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Recovering pay for a revoked order
    |--------------------------------------------------------------------------
    |
    | When a rest-day work or substitution order is revoked after its month was
    | paid, the retro engine knows the net that was overpaid. Taking it back from
    | the next payslip is a deduction from wages, and ƏM m.175 allows that only
    | with the employee's WRITTEN consent (m.175.1) — an overpayment that is not
    | an arithmetic error may not be withheld otherwise (m.175.5). So it is off
    | by default; switch it on only where that consent is part of the employment
    | or collective agreement. Every deduction is capped at 20 % of the wage due
    | for that payment (m.176.1); what is left stays pending for later runs.
    |
    | See docs/payroll-legal-basis.md.
    |
    */
    'recover_revoked_order_pay' => (bool) env('PAYROLL_RECOVER_REVOKED_ORDER_PAY', false),

    /** ƏM m.176.1: all deductions together at most 20 % of the wage due per payment. */
    'recovery_cap_ratio' => 0.20,

    /*
    |--------------------------------------------------------------------------
    | Kredit/avans borcu son haqq-hesabda
    |--------------------------------------------------------------------------
    |
    | Aylıq taksit yalnız adi (regular) run-da tutulur; off-cycle run-da tutulmur
    | (ikiqat tutulma olmasın). İşçi ayın içində işdən çıxırsa, onun son əmək
    | haqqını ödəyən run (adi run və ya `final_settlement` run-u) taksit yox,
    | QALAN borcu tutur — amma hər ödənişdə bütün tutulmaların cəmi işçiyə
    | verilməli olan əmək haqqının bu payını keçmir (ƏM m.176.1 — 20 %). Qalan
    | hissə kreditdə borc olaraq qalır. Bax: docs/payroll-legal-basis.md §4.
    |
    */
    'final_settlement_loan_cap_ratio' => (float) env('PAYROLL_FINAL_SETTLEMENT_LOAN_CAP_RATIO', 0.20),

];
