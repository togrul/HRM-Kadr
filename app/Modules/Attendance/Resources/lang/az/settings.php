<?php

return [
    'title' => 'Davamiyyət tənzimləmələri',
    'description' => 'Davamiyyət üçün gecikmə/erkən çıxış güzəştlərini və hesablama siyasətlərini konfiqurasiya edin.',
    'global_policy' => 'Qlobal siyasət',
    'default_shift' => [
        'current_title' => 'Cari default növbə',
        'current_description' => 'Əməkdaş üçün aktiv təyinat olmadıqda bu növbə istifadə olunur.',
        'none_badge' => 'Default növbə təyin edilməyib',
        'none_description' => 'Manual hesablamalar əməkdaşa xüsusi təyinat olmadan işləməlidirsə, burada default növbə seçin.',
        'option_none' => 'Default növbə yoxdur',
    ],
    'fields' => [
        'timezone' => 'Saat qurşağı',
        'default_shift' => 'Default növbə',
        'late_grace' => 'Gecikmə güzəşti (dəqiqə)',
        'early_grace' => 'Erkən çıxış güzəşti (dəqiqə)',
        'rounding_policy' => 'Yuvarlaqlaşdırma siyasəti',
        'rounding_step' => 'Yuvarlaqlaşdırma addımı (dəqiqə)',
        'overtime_policy' => 'Əlavə iş siyasəti',
    ],
    'options' => [
        'none' => 'yoxdur',
        'floor' => 'aşağı yuvarlaqla',
        'ceil' => 'yuxarı yuvarlaqla',
        'nearest' => 'ən yaxına yuvarlaqla',
        'by_approval' => 'təsdiqə görə',
        'all_worked' => 'bütün işlənən vaxt',
        'after_shift' => 'növbədən sonra',
    ],
    'actions' => [
        'save' => 'Tənzimləmələri yadda saxla',
    ],
    'messages' => [
        'saved' => 'Davamiyyət tənzimləmələri yadda saxlanıldı.',
    ],
];
