<?php

return [
    'kicker' => 'Maaş və kompensasiya',
    'title' => 'Kompensasiya',
    'description' => 'Maaş şkalaları, komponent kataloqu, işçi maaşları və bank rekvizitlərini buradan idarə edin.',

    'tabs' => [
        'scales' => 'Şkalalar',
        'components' => 'Kataloq',
        'assignments' => 'İşçi maaşları',
        'bank' => 'Bank',
        'history' => 'Tarixçə',
        'statutory' => 'Vergi/sığorta dərəcələri',
    ],

    'statutory' => [
        'title' => 'Qanuni dərəcə əlavə et',
        'list' => 'Mövcud dərəcələr',
        'empty' => 'Dərəcə yoxdur',
        'add_bracket' => 'Pillə əlavə et',
        'component' => 'Komponent',
        'payer' => 'Ödəyən',
        'base' => 'Baza',
        'up_to' => 'Həddə qədər',
        'rate' => 'Faiz (%)',
        'brackets' => 'Pillələr',
        'default_regime' => 'Bütün rejimlər (default)',
        'bracket_count' => ':count pillə',
        'top_rate' => '(maksimum)',
        'components' => [
            'income_tax' => 'Gəlir vergisi',
            'dsmf' => 'DSMF',
            'unemployment' => 'İşsizlik sığortası',
            'medical' => 'İcbari tibbi sığorta',
        ],
        'payers' => [
            'ee' => 'İşçi',
            'er' => 'İşəgötürən',
        ],
        'bases' => [
            'taxable' => 'Vergi bazası',
            'social' => 'Sosial baza',
        ],
    ],

    'summary' => [
        'scales' => 'Şkalalar',
        'grades' => 'Pillələr',
        'components' => 'Aktiv komponentlər',
        'assignments' => 'Aktiv maaşlar',
    ],

    'scales' => [
        'title' => 'Maaş şkalaları',
        'list' => 'Şkalalar',
        'empty' => 'Hələ şkala yoxdur',
        'subtitle' => 'Pillə üzrə minimum, orta nöqtə və maksimum',
        'meta' => ':scales şkala · :grades pillə',
    ],

    'grades' => [
        'title' => 'Pillələr',
        'label' => 'pillə',
        'select_scale' => 'Pillələri görmək üçün soldan şkala seçin.',
        'empty' => 'Bu şkalada pillə yoxdur',
    ],

    'components' => [
        'title' => 'Komponent kataloqu',
        'list' => 'Komponentlər',
        'empty' => 'Komponent yoxdur',
    ],

    'assignments' => [
        'current' => 'Cari maaş',
        'title' => 'Yeni təyinat',
        'lines' => 'Əlavə / tutulma sətirləri',
    ],

    'bank' => [
        'title' => 'Bank rekviziti',
        'list' => 'Bank hesabları',
        'empty' => 'Bank hesabı yoxdur',
    ],

    'history' => [
        'title' => 'Maaş tarixçəsi',
        'empty' => 'Tarixçə yoxdur',
        'ongoing' => 'davam edir',
    ],

    'fields' => [
        'name' => 'Ad',
        'regime' => 'Rejim',
        'currency' => 'Valyuta',
        'effective_from' => 'Qüvvəyə minmə',
        'effective_to' => 'Bitmə',
        'code' => 'Kod',
        'base_amount' => 'Baza məbləğ',
        'rank_category' => 'Rütbə kateqoriyası',
        'position' => 'Vəzifə',
        'type' => 'Növ',
        'calc_type' => 'Hesablama növü',
        'taxable' => 'Vergiyə cəlb',
        'affects_social' => 'Sosial bazaya təsir',
        'is_statutory' => 'Qanuni',
        'personnel' => 'Əməkdaş',
        'order_no' => 'Əmr nömrəsi',
        'note' => 'Qeyd',
        'component' => 'Komponent',
        'amount' => 'Məbləğ',
        'percent' => 'Faiz',
        'iban' => 'IBAN',
        'bank_name' => 'Bank adı',
        'account_no' => 'Hesab nömrəsi',
        'is_primary' => 'Əsas',
        'is_active' => 'Aktiv',
        'sort' => 'Sıra',
        'description' => 'Təsvir',
        'gl_code' => 'Mühasibat kodu',
    ],

    'columns' => [
        'scale' => 'Şkala',
        'grade_range' => 'Pillə',
        'min' => 'Minimum',
        'midpoint' => 'Orta nöqtə',
        'max' => 'Maksimum',
        'regime' => 'Rejim',
        'grade' => 'Pillə',
        'amount' => 'Məbləğ',
        'position' => 'Vəzifə',
        'component' => 'Komponent',
        'type' => 'Növ',
        'calc_type' => 'Hesablama',
        'flags' => 'Xüsusiyyətlər',
        'actions' => 'Əməliyyat',
    ],

    'types' => [
        'earning' => 'Əlavə',
        'deduction' => 'Tutulma',
    ],

    'calc_types' => [
        'fixed' => 'Sabit',
        'percent' => 'Faiz',
        'formula' => 'Formula',
        'per_diem' => 'Gündəlik',
        'rate' => 'Dərəcə',
    ],

    'status' => [
        'draft' => 'Qaralama',
        'active' => 'Aktiv',
        'ended' => 'Bitmiş',
    ],

    'actions' => [
        'save' => 'Yadda saxla',
        'cancel' => 'Ləğv et',
        'delete' => 'Sil',
        'search' => 'Axtar',
        'clear' => 'Təmizlə',
        'search_personnel' => 'Ad və ya tabel nömrəsi ilə axtar',
        'add_line' => 'Sətir əlavə et',
        'assign' => 'Maaşı təyin et',
        'add_scale' => 'Şkala əlavə et',
        'add_grade' => 'Pillə əlavə et',
        'add_component' => 'Komponent əlavə et',
        'add_bank' => 'Bank hesabı əlavə et',
        'add_rate' => 'Dərəcə əlavə et',
        'open_catalog' => 'Kataloq',
        'edit' => 'Redaktə et',
        'close' => 'Bağla',
    ],

    'confirm' => [
        'delete' => 'Bu qeydi silmək istədiyinizə əminsiniz?',
    ],

    'messages' => [
        'saved' => 'Yadda saxlanıldı',
        'deleted' => 'Silindi',
    ],
];
