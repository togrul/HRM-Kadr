<?php

return [
    'title' => 'Ana səhifə',
    'breadcrumb' => 'Ana səhifə',
    'greeting' => 'Xoş gəldiniz, :name',
    'subtitle' => 'Diqqət tələb edən işlər və bu həftənin mənzərəsi.',
    'empty' => 'Göstəriləcək məlumat yoxdur.',
    'panel_subtitle' => 'Bugünkü vəzifələr',

    'greetings' => [
        'morning' => 'Sabahınız xeyir, :name',
        'afternoon' => 'Günortanız xeyir, :name',
        'evening' => 'Axşamınız xeyir, :name',
    ],

    'actions' => [
        'new_employee' => 'Yeni əməkdaş',
    ],

    'today' => [
        'title' => 'Bu gün',
        'empty' => 'Bu gün gözləyən iş yoxdur.',
        'items' => [
            'attendance_pending' => ':count əməkdaş təsdiq gözləyir',
            'unsigned_orders' => ':count əmr imza gözləyir',
            'birthdays' => 'Bugün :count ad günü',
            'vacations_starting' => ':count məzuniyyət bu həftə başlayır',
        ],
        'notes' => [
            'attendance_pending' => 'Əl ilə daxil edilmiş davamiyyət qeydləri',
            'unsigned_orders' => 'Təsdiq gözləyən əmrlər',
            'vacations_starting' => 'Növbəti 7 gün ərzində',
        ],
    ],

    'quick' => [
        'title' => 'Tez keçid',
        'new_employee' => 'Yeni əməkdaş əlavə et',
        'new_order' => 'Əmr yarat',
        'vacation_request' => 'Məzuniyyət sorğusu',
        'export_report' => 'Aylıq hesabatı ixrac et',
        'today_attendance' => 'Bugünkü davamiyyət',
    ],

    'attention' => [
        'title' => 'Diqqət tələb edir',
        'all_clear' => 'Gözləyən iş yoxdur.',
        'open' => 'Aç',
        'waiting' => 'ən köhnəsi :days gündür',
        'waiting_today' => 'bugün daxil olub',
        'cards' => [
            'attendance_pending' => [
                'label' => 'Təsdiq gözləyən əməkdaş qeydi',
                'hint' => 'Əl ilə daxil edilmiş davamiyyət qeydləri',
                'action' => 'Baxışa keç',
            ],
            'unsigned_orders' => [
                'label' => 'İmzasız əmr',
                'hint' => 'Hazırlanıb, hələ təsdiqlənməyib',
                'action' => 'Əmrləri aç',
            ],
            'vacation_requests' => [
                'label' => 'Məzuniyyət sorğusu',
                'hint' => 'Rəhbər təsdiqi gözləyir',
                'action' => 'Sorğulara keç',
            ],
            'expiring_documents' => [
                'label' => 'Vaxtı bitən sənəd',
                'hint' => '30 gün ərzində bitir və ya bitib',
                'action' => 'Sənədlərə keç',
            ],
        ],
    ],

    'attendance' => [
        'title' => 'Bu həftə davamiyyət',
        'subtitle' => 'Son 7 gün',
        'average' => 'Orta',
        'headcount' => 'Günlük iştirak faizi · :count əməkdaş',
        'rate_hint' => ':date · iştirak :rate%',
        'present' => 'İştirak',
        'absent' => 'Qayıb',
        'no_data' => 'Bu həftə üçün davamiyyət yığımı yoxdur.',
        'weekdays' => [
            1 => 'B.e',
            2 => 'Ç.a',
            3 => 'Ç',
            4 => 'C.a',
            5 => 'C',
            6 => 'Ş',
            7 => 'B',
        ],
    ],

    'activity' => [
        'title' => 'Son əməliyyatlar',
        'view_all' => 'Hamısına bax',
        'system_actor' => 'Sistem',
        'no_data' => 'Hələ qeyd yoxdur.',
        'events' => [
            'created' => 'yaratdı',
            'updated' => 'yenilədi',
            'deleted' => 'sildi',
            'restored' => 'bərpa etdi',
            'login' => 'sistemə daxil oldu',
            'logout' => 'sistemdən çıxdı',
            'default' => 'dəyişdi',
        ],
    ],

    'structure' => [
        'title' => 'Struktur üzrə doluluq',
        'subtitle' => 'Ştat cədvəlinə görə',
        'manage' => 'Ştat cədvəli',
        'filled' => 'Dolu',
        'vacant' => 'Vakant',
        'no_data' => 'Ştat cədvəli doldurulmayıb.',
    ],
];
