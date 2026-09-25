<?php

return [
    'kicker' => 'Əmək haqqı',
    'title' => 'Əmək haqqı',
    'description' => 'Dövrlər yaradın, hesablamalar aparın, maaş vərəqələrini nəzərdən keçirin, təsdiqləyin və kilidləyin.',

    'tabs' => [
        'runs' => 'Hesablamalar',
        'payslips' => 'Maaş vərəqələri',
        'loans' => 'Kredit/avans',
    ],

    'loans' => [
        'title' => 'Kredit / avans təyini',
        'list' => 'Kreditlər',
        'empty' => 'Kredit yoxdur',
        'active' => ':count aktiv',
        'select_personnel' => 'Kreditləri idarə etmək üçün əməkdaş seçin.',
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
        'runs' => 'Hesablamalar',
        'locked' => 'Kilidlənmiş',
        'payslips' => 'Maaş vərəqələri',
    ],

    'periods' => [
        'title' => 'Yeni dövr',
        'list' => 'Dövrlər',
        'empty' => 'Hələ dövr yoxdur',
    ],

    'runs' => [
        'new' => 'Yeni hesablama',
        'title' => 'Hesablamalar',
        'employees' => 'Əməkdaş',
        'empty' => 'Hələ hesablama yoxdur',
        'type' => 'Hesablama növü',
        'forecast' => 'Proqnoz aylıq əmək haqqı fondu',
    ],

    'payslips' => [
        'select_run' => 'Maaş vərəqələrini görmək üçün hesablama seçin.',
        'run' => 'Hesablama',
        'title' => 'Maaş vərəqələri',
        'empty' => 'Bu hesablamada maaş vərəqəsi yoxdur',
        'detail' => 'Maaş vərəqəsi',
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
        'currency' => 'Valyuta',
    ],

    'columns' => [
        'employee' => 'Əməkdaş',
        'actions' => 'Əməliyyatlar',
    ],

    'actions' => [
        'create_period' => 'Dövr yarat',
        'create_run' => 'Hesablama yarat',
        'view_payslips' => 'Maaş vərəqələri',
        'calculate' => 'Hesabla',
        'approve' => 'Təsdiqlə',
        'lock' => 'Kilidlə',
        'reopen' => 'Yenidən aç',
        'close' => 'Bağla',
        'save' => 'Yadda saxla',
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
        'title' => 'Qanunvericilik tutulmaları',
        'empty' => 'Bu dövr üçün tutulma yoxdur',
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
        'lock' => 'Hesablama kilidlənəcək və maaş vərəqələri dondurulacaq. Davam edilsin?',
        'reopen' => 'Hesablama yenidən açılacaq. Davam edilsin?',
        'delete' => 'Bu qeydi silmək istədiyinizə əminsiniz?',
    ],

    'messages' => [
        'period_created' => 'Dövr yaradıldı',
        'run_created' => 'Hesablama yaradıldı',
        'calculated' => 'Hesablandı',
        'approved' => 'Təsdiqləndi',
        'locked' => 'Kilidləndi',
        'reopened' => 'Yenidən açıldı',
        'deleted' => 'Silindi',
        'saved' => 'Yadda saxlanıldı',
        'recalculate_first' => 'Hesablama aparılandan sonra birdəfəlik ödənişlər dəyişib — kilidləmədən əvvəl yenidən hesablayın',
    ],
];
