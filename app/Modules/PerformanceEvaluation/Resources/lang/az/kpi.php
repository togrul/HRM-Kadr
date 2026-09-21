<?php

return [
    'sections' => [
        'kpis' => 'KPI kitabxanası',
        'templates' => 'Vəzifə şablonları',
    ],

    'fields' => [
        'code' => 'Kod',
        'name' => 'Ad',
        'description' => 'Təsvir',
        'type' => 'Tip',
        'direction' => 'İstiqamət',
        'unit' => 'Ölçü vahidi',
        'frequency' => 'Tezlik',
        'aggregation' => 'Dövr üzrə toplama',
        'perspective' => 'Perspektiv',
        'indicator_kind' => 'Göstərici növü',
        'evidence_required' => 'Manual dəyər üçün sübut faylı məcburidir',
        'status' => 'Status',
        'version' => 'Versiya',
        'period_type' => 'Dövr tipi',
        'kpi_weight_share' => 'KPI payı, %',
        'competency_weight_share' => 'Kompetensiya payı, %',
        'positions' => 'Vəzifələr',
        'items' => 'KPI bəndləri',
        'choose_kpi' => 'KPI seçin',
        'weight' => 'Çəki, %',
        'target' => 'Hədəf',
        'range_min' => 'Diapazon: alt',
        'range_max' => 'Diapazon: üst',
        'threshold' => 'Threshold, %',
        'stretch' => 'Stretch, %',
        'cap' => 'Cap, %',
        'band' => 'Threshold / Stretch / Cap',
        'target_editable' => 'Rəhbər hədəfi dəyişə bilər',
        'kpi' => 'KPI',
        'actual' => 'Faktiki',
        'achievement' => 'Nəticə',
        'score' => 'Bal',
        'kpi_score' => 'KPI balı',
        'final_score' => 'Yekun bal',
        'personnel' => 'Əməkdaş',
        'position' => 'Vəzifə',
        'manager' => 'Rəhbər',
        'prorata' => 'Pro-rata',
        'note' => 'Qeyd',
        'evidence' => 'Sübut faylı',
    ],

    'columns' => [
        'kpi' => 'KPI',
        'measure' => 'Vahid · tezlik',
    ],

    'directions_short' => [
        'higher_better' => 'Artım yaxşıdır',
        'lower_better' => 'Azalma yaxşıdır',
        'range' => 'Diapazonda',
    ],

    'types' => [
        'quantitative' => 'Kəmiyyət',
        'qualitative' => 'Keyfiyyət (1–5)',
        'binary' => 'Binar (bəli/xeyr)',
    ],

    'directions' => [
        'higher_better' => 'Çox olması yaxşıdır',
        'lower_better' => 'Az olması yaxşıdır',
        'range' => 'Diapazon',
    ],

    'units' => [
        'percent' => 'Faiz',
        'currency' => 'Məbləğ',
        'count' => 'Say',
        'days' => 'Gün',
        'hours' => 'Saat',
        'score' => 'Bal',
    ],

    'frequencies' => [
        'monthly' => 'Aylıq',
        'quarterly' => 'Rüblük',
        'semiannual' => 'Yarımillik',
        'annual' => 'İllik',
    ],

    'aggregations' => [
        'sum' => 'Cəm',
        'avg' => 'Orta',
        'last' => 'Sonuncu dəyər',
        'min' => 'Minimum',
        'max' => 'Maksimum',
    ],

    'perspectives' => [
        'financial' => 'Maliyyə',
        'customer' => 'Müştəri',
        'process' => 'Daxili proses',
        'growth' => 'İnkişaf',
    ],

    'indicator_kinds' => [
        'lead' => 'Qabaqlayıcı (lead)',
        'lag' => 'Nəticə (lag)',
    ],

    'statuses' => [
        'draft' => 'Qaralama',
        'active' => 'Aktiv',
        'archived' => 'Arxiv',
    ],

    'card_statuses' => [
        'draft' => 'Qaralama',
        'active' => 'Aktiv',
        'manager_review' => 'Rəhbər qiymətləndirməsi',
        'closed' => 'Bağlanıb',
    ],

    'transitions' => [
        'activate' => 'Kartı aktivləşdir',
        'submit' => 'Qiymətləndirməyə göndər',
        'return' => 'Geri qaytar',
        'close' => 'Təsdiqlə və bağla',
    ],

    'confirm_transition' => [
        'activate' => 'Kart aktivləşəcək və hədəflər kilidlənəcək. Davam edilsin?',
        'submit' => 'Kart rəhbər qiymətləndirməsinə göndəriləcək; faktiki dəyər daxil etmək bağlanacaq. Davam edilsin?',
        'return' => 'Kart yenidən aktiv vəziyyətə qaytarılacaq. Davam edilsin?',
        'close' => 'Kart yekun hesablanıb bağlanacaq və yalnız oxunacaq. Davam edilsin?',
    ],

    'ratings' => [
        'not_meeting' => 'Gözləntilərə cavab vermir',
        'partially_meeting' => 'Qismən cavab verir',
        'meeting' => 'Cavab verir',
        'exceeding' => 'Aşır',
        'far_exceeding' => 'Əhəmiyyətli dərəcədə aşır',
    ],

    'actions' => [
        'add_kpi' => 'Yeni KPI',
        'edit_kpi' => 'KPI-ı redaktə et',
        'add_template' => 'Yeni şablon',
        'edit_template' => 'Şablonu redaktə et',
        'add_item' => 'Bənd əlavə et',
        'add_actual' => 'Faktiki dəyər',
        'generate_cards' => 'Kartları yarat',
        'back_to_list' => 'Siyahıya qayıt',
        'approve' => 'Təsdiqlə',
        'archive' => 'Arxivləşdir',
        'edit' => 'Redaktə',
        'delete' => 'Sil',
        'save' => 'Yadda saxla',
        'clear' => 'Təmizlə',
        'remove' => 'Çıxar',
        'cancel' => 'Ləğv et',
    ],

    'messages' => [
        'kpi_saved' => 'KPI yadda saxlanıldı.',
        'kpi_deleted' => 'KPI silindi.',
        'template_saved' => 'Şablon yadda saxlanıldı.',
        'template_deleted' => 'Şablon silindi.',
        'cards_generated' => ':count yeni KPI kartı yaradıldı.',
        'status_changed' => 'Kartın statusu dəyişdi.',
        'actual_saved' => 'Faktiki dəyər qeydə alındı.',
    ],

    'errors' => [
        'weights_sum_invalid' => 'KPI çəkilərinin cəmi 100% olmalıdır.',
        'shares_sum_invalid' => 'KPI və kompetensiya paylarının cəmi 100% olmalıdır.',
        'items_required' => 'Şablonda ən azı bir KPI olmalıdır.',
        'threshold_order_invalid' => 'Threshold 100%-dən kiçik, stretch və cap 100%-dən az olmamalı, stretch cap-dan böyük olmamalıdır.',
        'target_required' => 'Bu KPI üçün hədəf dəyəri daxil edin.',
        'range_invalid' => 'Diapazon KPI-ı üçün alt və üst sərhəd daxil edin (alt ≤ üst).',
        'kpi_missing' => 'Seçilmiş KPI tapılmadı.',
        'duplicate_kpi' => 'Eyni KPI şablonda bir dəfədən çox ola bilməz.',
        'position_taken' => 'Seçilmiş vəzifələrdən biri artıq başqa şablona bağlıdır.',
        'kpi_in_use' => 'Bu KPI şablonda və ya kartda istifadə olunur — silmək olmaz, arxivləşdirin.',
        'invalid_transition' => 'Kartın cari statusundan bu keçid mümkün deyil.',
        'scorecard_locked' => 'Kart kilidlidir: hədəf yalnız qaralama mərhələsində dəyişdirilə bilər.',
        'scorecard_not_active' => 'Faktiki dəyər yalnız aktiv kartda daxil edilir.',
        'evidence_required' => 'Bu KPI üçün sübut faylı məcburidir.',
    ],

    'warnings' => [
        'item_count' => 'Tövsiyə: şablonda :min–:max KPI olsun.',
        'item_weight' => 'Tövsiyə: hər KPI-ın çəkisi :min–:max% aralığında olsun.',
    ],

    'template_weight' => 'Çəki cəmi: :sum%',
    'no_positions' => 'Vəzifəyə bağlanmayıb',
    'positions_hint' => 'Hər vəzifə yalnız bir şablona bağlana bilər. Solğun görünən vəzifələr artıq başqa şablondadır.',
    'band_hint' => 'Threshold, stretch və cap nəticə faizidir (məs. 80 / 110 / 120). Hədəf və diapazon KPI-ın öz vahidindədir.',
    'version_hint' => 'Tip, istiqamət, vahid, toplama və ya şkala dəyişsə yeni versiya yaranır; açıq kartlar köhnə versiyada qalır.',
    'confirm_delete_kpi' => 'KPI silinsin? İstifadədədirsə, silinməyəcək.',
    'confirm_delete_template' => 'Şablon silinsin? Artıq yaradılmış kartlar dəyişməyəcək.',
    'all_statuses' => 'Bütün statuslar',
    'actual_approved' => 'təsdiqlənib',
    'actual_pending' => 'təsdiq gözləyir',
    'actual_pending_hint' => 'Daxil etdiyiniz dəyər rəhbər təsdiqləyəndən sonra hesablamaya düşəcək.',
    'empty_kpis' => 'Kitabxanada hələ KPI yoxdur.',
    'empty_templates' => 'Hələ şablon yoxdur. KPI-ları vəzifələrə bağlamaq üçün şablon yaradın.',
    'empty_cards' => 'Bu dövr üçün kart yoxdur. "Kartları yarat" şablonu olan vəzifələr üçün kart açır.',
    'no_cycle' => 'Əvvəlcə qiymətləndirmə dövrü yaradın.',
    'empty_kpis_hint' => 'Başlamaq üçün «Yeni KPI» düyməsini basın — məsələn, satış planı, müştəri məmnuniyyəti və ya tapşırıqların vaxtında icrası.',
    'split_kpi' => 'KPI',
    'split_competency' => 'Kompetensiya',
    'more_items' => 'və daha :count KPI',
    'weight_ok' => 'Çəkilər balanslıdır — cəmi 100%',
    'weight_off' => 'Çəki cəmi :sum% — 100% olmalıdır',
    'positions_selected' => 'seçilib',
    'search_positions' => 'Vəzifə axtar…',
    'position_taken_by' => '«:template» şablonuna bağlıdır',
    'no_results' => 'Heç nə tapılmadı',
];
