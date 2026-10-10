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
        'search_personnel' => 'Kredit və ya avans üçün əməkdaş axtarın',
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
        'closed' => 'bağlı',
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
        'reopen_reason' => 'Yenidən açma səbəbi',
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
        'retro_recovery' => 'Ləğv olunmuş əmr üzrə artıq ödənişin tutulması (retro)',
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
        'reopen_period' => 'Dövrü yenidən aç',
        'create_period' => 'Dövr yarat',
        'create_run' => 'Hesablama yarat',
        'view_payslips' => 'Maaş vərəqələri',
        'calculate' => 'Hesabla',
        'approve' => 'Təsdiqlə',
        'lock' => 'Kilidlə',
        'reopen' => 'Yenidən aç',
        'close' => 'Bağla',
        'clear' => 'Təmizlə',
        'print' => 'Çap et',
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

    'order_earnings' => [
        'rest_day_work' => 'Qeyri-iş günü işə cəlb (ikiqat, :days gün, :hours saat)',
        'rest_day_work_mixed' => 'Qeyri-iş günü işə cəlb (:days gün, :hours saat; normadaxili :within saat bir qat, qalanı ikiqat)',
        'substitution' => 'Əvəzetməyə görə əlavə ödəniş (:days iş günü)',
        'substitution_for' => 'Əvəzetməyə görə əlavə ödəniş — :name (:days iş günü)',
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
        'reopen_period' => 'Bağlanmış əmək haqqı ayı yenidən açılacaq və bu, audit jurnalına yazılacaq. Davam edilsin?',
        'approve' => ':period dövrü üzrə hesablama təsdiqlənəcək — :count işçi, xalis cəmi :total :currency. Davam edilsin?',
        'approve_masked' => ':period dövrü üzrə hesablama təsdiqlənəcək — :count işçi. Davam edilsin?',
        'lock' => 'Hesablama kilidlənəcək və maaş vərəqələri dondurulacaq. Davam edilsin?',
        'reopen' => 'Hesablama yenidən açılacaq. Davam edilsin?',
        'delete' => 'Bu qeydi silmək istədiyinizə əminsiniz?',
    ],

    'messages' => [
        'pay_changed' => 'Hesablama aparılandan sonra əmək haqqı məlumatları (əmək haqqı, davamiyyət, tutulmalar, geriyə ödəniş və ya işçi tərkibi) dəyişib — hesablamanı yenidən açıb yenidən hesablayın',
        'period_closed' => 'Bu əmək haqqı ayı bağlanıb — əvvəlcə dövrü səbəb göstərərək yenidən açın',
        'regular_run_exists' => 'Bu dövr və rejim üçün müntəzəm hesablama artıq var — əlavə ödəniş üçün qeyri-müntəzəm (off-cycle) hesablama yaradın',
        'period_delete_blocked' => 'Bağlanmış dövr və ya təsdiqlənmiş/kilidlənmiş hesablaması olan dövr silinə bilməz',
        'period_reopened' => 'Dövr yenidən açıldı',
        'reopen_reason_required' => 'Dövrü yenidən açmaq üçün səbəbi ən azı :min simvolla göstərin',
        'period_not_closed' => 'Yalnız bağlanmış dövr yenidən açıla bilər',
        'period_created' => 'Dövr yaradıldı',
        'run_created' => 'Hesablama yaradıldı',
        'calculated' => 'Hesablandı',
        'approved' => 'Təsdiqləndi',
        'locked' => 'Kilidləndi',
        'reopened' => 'Yenidən açıldı',
        'deleted' => 'Silindi',
        'saved' => 'Yadda saxlanıldı',
        'not_editable' => 'Təsdiqlənmiş və ya kilidlənmiş hesablama yenidən hesablana və silinə bilməz',
        'approve_requires_calculated' => 'Yalnız hesablanmış hesablama təsdiqlənə bilər',
        'lock_requires_approval' => 'Kilidləmədən əvvəl hesablama təsdiqlənməlidir',
        'reopen_not_allowed' => 'Yalnız təsdiqlənmiş və ya kilidlənmiş hesablama yenidən açıla bilər',
        'recalculate_first' => 'Hesablama aparılandan sonra birdəfəlik ödənişlər dəyişib — hesablamanı yenidən açıb yenidən hesablayın',
        'order_earnings_changed' => 'Hesablama aparılandan sonra əmrdən irəli gələn ödənişlər (qeyri-iş gününə cəlb, əvəzetmə) dəyişib — hesablamanı yenidən açıb yenidən hesablayın',
    ],
];
