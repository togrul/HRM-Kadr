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
        'regimes' => 'Rejim və sektor',
        'reliefs' => 'Vergi güzəştləri',
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
        'default_regime' => 'Bütün rejimlər (standart)',
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
        'effective' => ':date tarixindən',
    ],

    'exemptions' => [
        'code_reserved' => 'Bu kod kart tələb edən və ya komponent növlü qaydaya aiddir; sabit məbləğli forma ilə əvəz edilmir. Məbləği «Vergi güzəştləri» bölməsindən dəyişin.',
        'delete_reserved' => 'Kart tələb edən və ya komponent növlü qayda bu ekrandan silinmir; silinsə hamıdan götürülərdi.',
        'title' => 'Gəlir vergisi azadolmaları (VM m.102)',
        'hint' => 'Pillələr tətbiq edilməzdən əvvəl vergi tutulan gəlirdən çıxılır. Yeni sətir öz tarixindən tətbiq olunur; kilidlənmiş aylar köhnə qayda ilə qalır.',
        'panel_hint' => 'Məs. VM m.102.1.6: əsas iş yerində aylıq gəlir 2500 AZN-dək olduqda 200 AZN azaddır. Eyni kodlu əvvəlki sətir yeni tarixdən bir gün əvvəl bağlanır.',
        'add' => 'Azadolma əlavə et',
        'empty' => 'Azadolma yoxdur',
        'code' => 'Kod',
        'legal_basis' => 'Hüquqi əsas',
        'amount' => 'Azad məbləğ (AZN)',
        'income_up_to' => 'Yalnız aylıq gəlir bu məbləğədək olduqda (AZN)',
        'income_limit' => 'aylıq gəlir :amount AZN-dək',
        'no_income_limit' => 'gəlir həddi yoxdur',
        'main_workplace_only' => 'yalnız əsas iş yerində',
        'taxable_only' => 'Vergiyə yalnız bu komponentlər cəlb olunur: :codes',
        'base_only' => ':component bazasına yalnız bu komponentlər daxildir: :codes',
        'personal' => 'işçinin güzəşt kartı ilə (Kompensasiya → Vergi güzəştləri)',
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
        'military_taxed' => 'Hərbçidə vergiyə cəlb olunur',
        'military_exempt' => 'Hərbçidə vergidən azad',
        'military_no_dsmf' => 'Hərbçidə DSMF bazasına daxil deyil',
        'list' => 'Komponentlər',
        'empty' => 'Komponent yoxdur',
    ],

    'assignments' => [
        'current' => 'Cari maaş',
        'title' => 'Yeni təyinat',
        'lines' => 'Əlavə / tutulma sətirləri',
        'order_only' => 'Dəyişiklik siyasətinə görə əmək haqqı yalnız «Əmək haqqının dəyişdirilməsi» əmri ilə dəyişir. İlk təyinat (aktiv maaş yoxdursa) buradan edilə bilər.',
        'create_order' => 'Əmr yarat',
        'reason' => 'Dəyişikliyin səbəbi',
        'reason_hint' => 'Əmək haqqı jurnal rejimindədir: səbəb audit jurnalına yazılacaq.',
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
        'currency_legacy' => ':code (dəstəklənməyən kod)',
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
        'add_relief' => 'Güzəşt kartı əlavə et',
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
