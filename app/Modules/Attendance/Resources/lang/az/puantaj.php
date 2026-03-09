<?php

return [
    'title' => 'Puantaj cədvəli',
    'headers' => [
        'personnel' => 'Tabel no / Ad soyad',
        'total_hours' => 'Cəmi saat',
        'total_days' => 'Cəmi gün',
    ],
    'search' => [
        'label' => 'Axtarış (ad və ya tabel nömrəsi)',
        'placeholder' => 'məs. 12345 və ya Əliyev',
    ],
    'scope' => [
        'badge' => 'Struktur scope',
        'description' => 'Yalnız seçilmiş struktur ağacındakı əməkdaşlar göstərilir.',
    ],
    'tooltips' => [
        'worked' => 'İşlənib: :hours saat',
        'status' => 'Status: :status',
        'absence' => 'Yoxluq: :code',
    ],
    'statuses' => [
        'present' => 'işdə',
        'manual_present' => 'manual işdə',
        'holiday_worked' => 'bayramda işləyib',
        'weekend_worked' => 'həftəsonu işləyib',
        'absent' => 'yoxdur',
        'manual_absence' => 'manual yoxluq',
        'weekend' => 'həftəsonu',
        'holiday' => 'bayram',
        'none' => 'yoxdur',
    ],
];
