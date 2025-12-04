<?php

return [
    // Aktif profil (env ile de ayarlanabilir: APP_PROFILE)
    'active' => env('APP_PROFILE', 'default'),

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
                'weapons' => true,
                'vacation_tracking' => true,
                'contracts' => true,
                'pension_cards' => true,
                'disposal' => true,
                'education' => true,
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
                'weapons' => true,
                'vacation_tracking' => true,
                'contracts' => true,
                'pension_cards' => true,
                'disposal' => true,
                'education' => true,
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
                'military_service' => false,
                'weapons' => false,
                'vacation_tracking' => true,
                'contracts' => false,
                'pension_cards' => false,
                'disposal' => false,
                'education' => true,
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
                'weapons' => false,
                'vacation_tracking' => true,
                'contracts' => false,
                'pension_cards' => false,
                'disposal' => false,
                'education' => true,
            ],
        ],
    ],
];
