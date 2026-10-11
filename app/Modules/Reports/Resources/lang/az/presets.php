<?php

return [
    'title' => 'Saxlanmış filtrlər',
    'empty' => 'Bu bölmə üçün saxlanmış filtr yoxdur.',
    'fields' => [
        'name' => 'Şablonun adı',
        'name_placeholder' => 'Məs.: Aylıq kadr hesabatı',
        'shared' => 'Bütün hesabat istifadəçiləri ilə paylaş',
    ],
    'labels' => [
        'shared' => 'Paylaşılan',
        'personal' => 'Şəxsi',
    ],
    'actions' => [
        'save' => 'Cari filtrləri saxla',
        'apply' => 'Tətbiq et',
        'delete' => 'Şablonu sil',
    ],
    'messages' => [
        'saved' => 'Filtr şablonu saxlanıldı.',
        'deleted' => 'Filtr şablonu silindi.',
        'applied' => 'Filtr şablonu tətbiq edildi.',
        'structure_dropped' => 'Şablondakı struktur sizin görünürlüyünüzdən kənardadır — hesabat sizin strukturlarınız üzrə göstərilir.',
        'delete_confirmation_required' => 'Silməni təsdiqləyin.',
    ],
    'confirmations' => [
        'delete' => 'Bu filtr şablonu silinsin?',
    ],
    'errors' => [
        'invalid_name' => 'Şablonun adı boş ola bilməz və :max simvoldan uzun olmamalıdır.',
        'invalid_period' => 'Hesabat dövrü yanlışdır.',
        'structure_out_of_scope' => 'Seçilmiş struktur sizin görünürlüyünüzdən kənardadır.',
        'report_not_allowed' => 'Bu hesabata baxmaq icazəniz yoxdur.',
        'invalid_selection' => 'Hesabat seçimi yanlışdır.',
        'tab_not_supported' => 'Filtr şablonu yalnız standart və dinamik hesabatlar üçün saxlanılır.',
    ],
];
