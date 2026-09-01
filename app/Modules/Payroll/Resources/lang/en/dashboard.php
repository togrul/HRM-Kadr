<?php

return [
    'kicker' => 'Payroll',
    'title' => 'Payroll',
    'description' => 'Create periods, open calculation runs, review payslips, approve and lock.',

    'tabs' => [
        'runs' => 'Runs',
        'payslips' => 'Payslips',
        'loans' => 'Loans/advances',
    ],

    'loans' => [
        'title' => 'Loan / advance assignment',
        'list' => 'Loans',
        'empty' => 'No loans',
        'active' => ':count active',
        'select_personnel' => 'Select an employee to manage loans.',
        'types' => [
            'loan' => 'Loan',
            'advance' => 'Advance',
        ],
        'statuses' => [
            'active' => 'Active',
            'closed' => 'Closed',
        ],
    ],

    'summary' => [
        'periods' => 'Periods',
        'runs' => 'Runs',
        'locked' => 'Locked',
        'payslips' => 'Payslips',
    ],

    'periods' => [
        'title' => 'New period',
        'list' => 'Periods',
        'empty' => 'No periods yet',
    ],

    'runs' => [
        'new' => 'New run',
        'title' => 'Calculation runs',
        'employees' => 'Employees',
        'empty' => 'No runs yet',
        'type' => 'Run type',
        'forecast' => 'Projected monthly base payroll',
    ],

    'payslips' => [
        'select_run' => 'Select a run to view payslips.',
        'run' => 'Run',
        'title' => 'Payslips',
        'empty' => 'No payslips in this run',
        'detail' => 'Payslip detail',
    ],

    'fields' => [
        'year' => 'Year',
        'month' => 'Month',
        'period' => 'Period',
        'regime' => 'Regime',
        'all_regimes' => 'All regimes',
        'net' => 'Net',
        'gross' => 'Gross',
        'deductions' => 'Deductions',
        'proration' => 'Proration (attendance)',
        'retro' => 'Pending retro adjustment',
        'loan_type' => 'Type',
        'principal' => 'Principal',
        'monthly_installment' => 'Monthly installment',
        'start_on' => 'Start date',
        'remaining' => 'Remaining',
        'status' => 'Status',
        'currency' => 'Currency',
    ],

    'columns' => [
        'employee' => 'Employee',
        'actions' => 'Actions',
    ],

    'actions' => [
        'create_period' => 'Create period',
        'create_run' => 'Create run',
        'view_payslips' => 'Payslips',
        'calculate' => 'Calculate',
        'approve' => 'Approve',
        'lock' => 'Lock',
        'reopen' => 'Reopen',
        'close' => 'Close',
        'save' => 'Save',
        'delete' => 'Delete',
    ],

    'status' => [
        'draft' => 'Draft',
        'calculated' => 'Calculated',
        'approved' => 'Approved',
        'locked' => 'Locked',
    ],

    'kinds' => [
        'earning' => 'Earning',
        'deduction' => 'Deduction',
        'employer' => 'Employer',
    ],

    'loan' => [
        'line' => 'Loan/advance repayment',
    ],

    'run_types' => [
        'regular' => 'Regular',
        'off_cycle' => 'Off-cycle',
    ],

    'statutory' => [
        'title' => 'Statutory deductions',
        'empty' => 'No deductions for this period',
        'income_tax' => 'Income tax',
        'dsmf' => 'DSMF',
        'unemployment' => 'Unemployment insurance',
        'medical' => 'Mandatory medical insurance',
    ],

    'export' => [
        'title' => 'Export',
        'actions' => [
            'bank' => 'Bank file',
            'bank_csv' => 'Bank file (CSV)',
            'gl' => 'GL (ledger)',
            'state' => 'State report',
        ],
        'cols' => [
            'tabel_no' => 'Staff no.',
            'full_name' => 'Full name',
            'iban' => 'IBAN',
            'bank_name' => 'Bank',
            'amount' => 'Amount',
            'currency' => 'Currency',
            'gl_code' => 'GL code',
            'code' => 'Code',
            'name' => 'Name',
            'kind' => 'Kind',
            'pin' => 'PIN',
        ],
    ],

    'confirm' => [
        'lock' => 'The run will be locked and payslips frozen. Continue?',
        'reopen' => 'The run will be reopened. Continue?',
        'delete' => 'Are you sure you want to delete this record?',
    ],

    'messages' => [
        'period_created' => 'Period created',
        'run_created' => 'Run created',
        'calculated' => 'Calculated',
        'approved' => 'Approved',
        'locked' => 'Locked',
        'reopened' => 'Reopened',
        'deleted' => 'Deleted',
        'saved' => 'Saved',
    ],
];
