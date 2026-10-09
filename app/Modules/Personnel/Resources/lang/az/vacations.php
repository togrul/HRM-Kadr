<?php

return [
    'titles' => [
        'vacations_for' => 'Məzuniyyətlər - :name',
        'opening' => 'Açılış qalığı',
        'legacy' => 'Köhnə təqvim ili balansları',
    ],
    'messages' => [
        'updated' => 'Məzuniyyət yeniləndi!',
        'empty' => 'Məlumat əlavə olunmayıb',
        'opening_added' => 'Açılış qalığı əlavə olundu.',
    ],
    'hints' => [
        'work_year' => 'Məzuniyyət iş ili üzrə uçot olunur (iş ili işə qəbul günündən başlayır). İstifadə ən köhnə iş ilindən çıxılır, istifadə olunmamış günlər növbəti illərə keçir.',
        'opening' => 'Sistemə keçiddən əvvəlki iş illərinin istifadə olunmamış günləri. Səhv daxil edilmiş qalıq redaktə olunmur — silib yenidən əlavə edin.',
        'legacy' => 'Əvvəlki uçot təqvim ili üzrə idi. Son ilin qalığı keçid günündə davam edən iş ilinə köçürülüb; bu sətirlər yalnız tarixçədir.',
    ],
    'labels' => [
        'work_year' => 'İş ili',
        'entitlement' => 'Hüquq',
        'days' => 'gün',
        'opening_days' => 'Gün sayı',
        'note' => 'Qeyd',
        'available_from' => ':date tarixindən istifadə oluna bilər',
        'compensated' => 'o cümlədən kompensasiya: :days',
        'open_vacations' => 'Bu iş ilinin məzuniyyətləri',
        'legacy_row' => 'cəmi :total, qalıq :remaining gün',
    ],
    'breakdown' => [
        'base' => 'Əsas :days',
        'seniority' => 'Staj +:days (:years il)',
        'children' => 'Uşaq +:days',
        'conditions' => 'Şərait +:days',
        'opening' => 'Açılış +:days',
    ],
    'strategies' => [
        'legacy' => 'Köhnə balans',
        'opening' => 'Açılış qalığı',
        'ranked' => 'Rütbə kateqoriyası',
    ],
    'actions' => [
        'add_opening' => 'Açılış qalığı əlavə et',
    ],
    'confirm' => [
        'delete_opening_title' => 'Açılış qalığı silinsin?',
        'delete_opening_text' => 'Bu iş ilinin qalığı həmin gün sayı qədər azalacaq.',
    ],
];
