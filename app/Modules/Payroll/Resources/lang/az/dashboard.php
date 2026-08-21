<?php

return [
    'kicker' => 'Əmək haqqı',
    'title' => 'Əmək haqqı (Payroll)',
    'description' => 'Dövrlər yaradın, hesablama run-ları açın, payslip-ləri nəzərdən keçirin, təsdiqləyin və kilidləyin.',

    'tabs' => [
        'runs' => 'Run-lar',
        'payslips' => 'Payslip-lər',
        'loans' => 'Kredit/avans',
    ],

    'loans' => [
        'title' => 'Kredit / avans təyini',
        'list' => 'Kreditlər',
        'empty' => 'Kredit yoxdur',
        'types' => [
            'loan' => 'Kredit',
            'advance' => 'Avans',
        ],
        'statuses' => [
            'active' => 'Aktiv',
            'closed' => 'Bağlanıb',
        ],
    ],

    'summary' => [
        'periods' => 'Dövrlər',
        'runs' => 'Run-lar',
        'locked' => 'Kilidlənmiş',
        'payslips' => 'Payslip-lər',
    ],

    'periods' => [
        'title' => 'Yeni dövr',
        'list' => 'Dövrlər',
        'empty' => 'Hələ dövr yoxdur',
    ],

    'runs' => [
        'new' => 'Yeni run',
        'title' => 'Hesablama run-ları',
        'employees' => 'Əməkdaş',
        'empty' => 'Hələ run yoxdur',
        'type' => 'Run növü',
        'forecast' => 'Proqnoz aylıq baza payroll',
    ],

    'payslips' => [
        'select_run' => 'Payslip-ləri görmək üçün run seçin.',
        'run' => 'Run',
        'title' => 'Payslip-lər',
        'empty' => 'Bu run-da payslip yoxdur',
        'detail' => 'Payslip detalı',
    ],

    'fields' => [
        'year' => 'İl',
        'month' => 'Ay',
        'period' => 'Dövr',
        'regime' => 'Rejim',
        'all_regimes' => 'Bütün rejimlər',
        'net' => 'Net',
        'gross' => 'Brüt',
        'deductions' => 'Tutulmalar',
        'proration' => 'Proporsiya (davamiyyət)',
        'retro' => 'Gözləyən retro düzəliş',
        'loan_type' => 'Növ',
        'principal' => 'Əsas məbləğ',
        'monthly_installment' => 'Aylıq ödəniş',
        'start_on' => 'Başlama tarixi',
        'remaining' => 'Qalıq',
        'status' => 'Status',
    ],

    'actions' => [
        'create_period' => 'Dövr yarat',
        'create_run' => 'Run yarat',
        'view_payslips' => 'Payslip-lər',
        'calculate' => 'Hesabla',
        'approve' => 'Təsdiqlə',
        'lock' => 'Kilidlə',
        'reopen' => 'Yenidən aç',
        'close' => 'Bağla',
        'delete' => 'Sil',
    ],

    'status' => [
        'draft' => 'Qaralama',
        'calculated' => 'Hesablanıb',
        'approved' => 'Təsdiqlənib',
        'locked' => 'Kilidlənib',
    ],

    'kinds' => [
        'earning' => 'Əlavə',
        'deduction' => 'Tutulma',
        'employer' => 'İşəgötürən',
    ],

    'loan' => [
        'line' => 'Kredit/avans tutulması',
    ],

    'run_types' => [
        'regular' => 'Adi',
        'off_cycle' => 'Off-cycle',
    ],

    'statutory' => [
        'income_tax' => 'Gəlir vergisi',
        'dsmf' => 'DSMF',
        'unemployment' => 'İşsizlik sığortası',
        'medical' => 'İcbari tibbi sığorta',
    ],

    'export' => [
        'title' => 'İxrac',
        'actions' => [
            'bank' => 'Bank faylı',
            'bank_csv' => 'Bank faylı (CSV)',
            'gl' => 'GL (baş kitab)',
            'state' => 'Dövlət hesabatı',
        ],
        'cols' => [
            'tabel_no' => 'Tabel №',
            'full_name' => 'Ad Soyad',
            'iban' => 'IBAN',
            'bank_name' => 'Bank',
            'amount' => 'Məbləğ',
            'currency' => 'Valyuta',
            'gl_code' => 'GL kodu',
            'code' => 'Kod',
            'name' => 'Ad',
            'kind' => 'Növ',
            'pin' => 'FİN',
        ],
    ],

    'confirm' => [
        'lock' => 'Run kilidlənəcək və payslip-lər dondurulacaq. Davam edilsin?',
        'reopen' => 'Run yenidən açılacaq. Davam edilsin?',
        'delete' => 'Bu qeydi silmək istədiyinizə əminsiniz?',
    ],

    'messages' => [
        'period_created' => 'Dövr yaradıldı',
        'run_created' => 'Run yaradıldı',
        'calculated' => 'Hesablandı',
        'approved' => 'Təsdiqləndi',
        'locked' => 'Kilidləndi',
        'reopened' => 'Yenidən açıldı',
        'deleted' => 'Silindi',
        'saved' => 'Yadda saxlanıldı',
    ],
];
