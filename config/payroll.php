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

    /**
     * ƏM m.176.1: all deductions together at most 20 % of the wage due per payment. The
     * recovery shares that room with the loan instalment and voluntary deductions of the
     * same payment. Values above 0.20 are clamped to 0.20 (DeductionCap).
     */
    'recovery_cap_ratio' => (float) env('PAYROLL_DEDUCTION_CAP_RATIO', env('PAYROLL_FINAL_SETTLEMENT_LOAN_CAP_RATIO', 0.20)),

    /*
    |--------------------------------------------------------------------------
    | Tutulmaların ümumi həddi — ƏM m.176.1
    |--------------------------------------------------------------------------
    |
    | «Hər dəfə əmək haqqı verilərkən tutulan bütün məbləğlərin ümumi miqdarı
    | işçiyə verilməli olan əmək haqqının iyirmi faizindən ... artıq ola
    | bilməz.» Verilməli olan əmək haqqı = hesablanmış − vergi və məcburi sosial
    | ödənişlər. Hədd bütün qeyri-statutor tutulmalara birlikdə tətbiq olunur:
    |
    |  - adi run-da aylıq kredit/avans taksiti (işçinin ərizəsi ilə olsa da);
    |    sığmayan hissə kreditdə qalır, qrafik uzanır (növbəti ay yenə adi taksit);
    |  - işçi bu ay çıxırsa onun son əmək haqqını ödəyən run QALAN borcu tutur,
    |    sığmayan hissə borc olaraq qalır;
    |  - off-cycle run taksit tutmur (ikiqat tutulma olmasın).
    |
    | Qanunda işçinin razılığı ilə həddin aşılmasına icazə yoxdur; ona görə dəyər
    | yalnız aşağı salına bilər (0.20-dən böyük dəyər 0.20-yə sıxılır). Bax:
    | docs/payroll-legal-basis.md §4.
    |
    */
    'deduction_cap_ratio' => (float) env('PAYROLL_DEDUCTION_CAP_RATIO', env('PAYROLL_FINAL_SETTLEMENT_LOAN_CAP_RATIO', 0.20)),

];
