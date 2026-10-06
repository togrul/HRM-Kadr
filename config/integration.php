<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Who computes payroll
    |--------------------------------------------------------------------------
    |
    | 'self'    — this system does it (a standalone HR installation).
    | 'finance' — the finance system does it; this side supplies the conditions.
    |
    | ## Why this is a decision and not a merge
    |
    | HR does not hold the data a payroll calculation needs. It knows the
    | *conditions*: who is employed, on what base pay, with which allowances,
    | for how many days. It does not hold the progressive tax brackets, the
    | social-insurance rates by sector, the average-earnings rules, the
    | garnishment ceilings, or the accounting periods those figures must be
    | posted into. The finance system does.
    |
    | So when both are installed the split is not negotiable: this side states
    | the conditions, the other side applies the law to them. Running both
    | engines would produce two answers to the same question, and the only way
    | to find out they disagreed would be an employee noticing their payslip.
    |
    | Set to 'finance' only when the finance system is actually connected.
    |
    */
    'payroll_owner' => env('INTEGRATION_PAYROLL_OWNER', 'self'),

    /*
    |--------------------------------------------------------------------------
    | Reading back from the finance system
    |--------------------------------------------------------------------------
    |
    | The finance system publishes four things this side needs: payslips (so an
    | employee can see what they were paid), the accounting period state (so a
    | month it has already paid from is not silently reopened here), the
    | production calendar (so norm days agree), and business trips (which it
    | owns, and whose days must appear in attendance).
    |
    | This direction is a **pull** as well: in some installations only one side
    | can reach the other, so the protocol has to work either way round.
    |
    | Leave the URL empty to disable the return channel entirely.
    |
    */
    'finance' => [
        'base_url' => env('INTEGRATION_FINANCE_URL', ''),
        'token' => env('INTEGRATION_FINANCE_TOKEN', ''),
        'timeout' => (int) env('INTEGRATION_FINANCE_TIMEOUT', 30),
    ],

];
