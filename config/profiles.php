<?php

return [
    // Aktif profil (env ile de ayarlanabilir: APP_TYPE)
    'active' => env('APP_TYPE', 'default'),

    // Profil tanımları
    'profiles' => [
        'default' => [
            'modules' => [
                // override örneği: 'business-trips' => true,
            ],
            'features' => [
                // Varsayılan feature set (tümü açık)
                'ranks' => true,
                'rank_categories' => true,
                'service_cards' => true,
                'military_service' => true,
                'captivity' => true,
                'criminal_records' => true,
                'weapons' => true,
                'vacation_tracking' => true,
                'contracts' => true,
                'pension_cards' => true,
                'disposal' => true,
                'education' => true,
                // İmtiyazlar modulu (Benefits): özəl/korporativ — açıq; admin Admin → HR siyasəti ekranında dəyişə bilər.
                'benefits' => true,
            ],
        ],
        'military' => [
            'modules' => [
                // tüm modüller açık varsayım; gerekirse ekle
            ],
            'features' => [
                'ranks' => true,
                'rank_categories' => true,
                'service_cards' => true,
                'military_service' => true,
                'captivity' => true,
                'criminal_records' => true,
                'weapons' => true,
                'vacation_tracking' => true,
                'contracts' => true,
                'pension_cards' => true,
                'disposal' => true,
                'education' => true,
                // Hərbi/dövlət qulluğunda imtiyaz paketləri qanunla müəyyən olunur — standart olaraq bağlı.
                'benefits' => false,
            ],
        ],
        'public' => [
            'modules' => [
                // 'business-trips' => false,
            ],
            'features' => [
                'ranks' => false,
                'rank_categories' => false,
                'service_cards' => false,
                'military_service' => true,
                'captivity' => false,
                'criminal_records' => false,
                'weapons' => false,
                'vacation_tracking' => true,
                'contracts' => false,
                'pension_cards' => false,
                'disposal' => false,
                'education' => true,
                // Dövlət (Publik) profili: imtiyaz paketləri qanunla müəyyən olunur — standart olaraq bağlı.
                'benefits' => false,
            ],
        ],
        'private' => [
            'modules' => [
                'business-trips' => false,
            ],
            'features' => [
                'ranks' => false,
                'rank_categories' => false,
                'service_cards' => false,
                'military_service' => false,
                'captivity' => false,
                'criminal_records' => false,
                'weapons' => false,
                'vacation_tracking' => true,
                'contracts' => false,
                'pension_cards' => false,
                'disposal' => false,
                'education' => true,
                // Özəl şirkət: imtiyaz paketləri (tibbi sığorta, yemək kartı…) standart olaraq açıq.
                'benefits' => true,
            ],
        ],
    ],
];
