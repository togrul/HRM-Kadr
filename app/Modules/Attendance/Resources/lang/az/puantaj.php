<?php

return [
    'title' => 'Puantaj cədvəli',
    'headers' => [
        'personnel' => 'Tabel no / Ad soyad',
        'total_hours' => 'Cəmi saat',
        'total_days' => 'Cəmi gün',
        'workday_override' => 'İş günü override',
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
        'leave_type' => 'İcazə növü: :type',
        'calendar' => 'İş rejimi: :type',
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
        'workday' => 'iş günü',
        'none' => 'yoxdur',
    ],
    'short_labels' => [
        'vacation' => 'MZN',
        'business_trip' => 'EZM',
    ],
    'legend' => [
        'title' => 'Rənglər və işarələr',
        'unknown_leave' => 'İcazə',
        'leave_description' => 'Bu işarə həmin gün üçün təsdiqlənmiş icazə növünü göstərir.',
        'leave_description_with_code' => 'Bu işarə həmin gün üçün təsdiqlənmiş icazə növünü göstərir. Kod: :code.',
        'sections' => [
            'colors' => 'Rənglərin mənası',
            'leave_types' => 'İcazə növləri',
            'calendar' => 'İş rejimi təqvim override-ları',
        ],
        'items' => [
            'full_day' => 'Tam iş günü',
            'partial_day' => 'Natamam iş günü',
            'absence' => 'Yoxluq',
            'weekend' => 'Həftəsonu',
            'holiday' => 'Bayram günü',
        ],
        'descriptions' => [
            'full_day' => '9 saat işlənən günlər ağ fonda qara yazı ilə göstərilir.',
            'partial_day' => 'İş saatı normadan aşağı olan iş günlərini göstərir.',
            'absence' => 'İş günü üçün yoxluq və ya manual yoxluq qeydidir.',
            'weekend' => 'Həftəsonu günləri legenddə bir təqvim işarəsi ilə göstərilir.',
            'holiday' => 'Bayram günləri təqvim ikonu ilə göstərilir.',
        ],
        'calendar_description' => ':type, :scope, :paid',
    ],
    'calendar' => [
        'global_scope' => 'Ümumi scope',
        'paid' => 'ödənişli',
        'unpaid' => 'ödənişsiz',
    ],
];
