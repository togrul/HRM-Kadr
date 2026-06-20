<?php

return [
    'title' => 'Günlük monitor',
    'filters' => [
        'title' => 'Filtrlər',
        'description' => 'Seçilmiş scope üzrə bugünkü davamiyyət statusunu, gecikmələri və çatışmayan qeydləri nəzərdən keçirin.',
        'date' => 'Tarix',
        'status' => 'Status',
        'search' => 'Axtarış',
        'search_placeholder' => 'Ad və ya tabel nömrəsi',
    ],
    'scope' => [
        'badge' => 'Struktur scope',
        'description' => 'Yalnız seçilmiş struktur ağacındakı əməkdaşlar göstərilir.',
    ],
    'breakdown' => [
        'title' => 'Günlük status bölgüsü',
        'description' => 'Seçilmiş tarix və struktur scope üçün canlı sayğaclar.',
    ],
    'cards' => [
        'present' => 'İşdə',
        'late' => 'Gecikib',
        'absent' => 'Yoxdur',
        'missing' => 'Gündəlik qeyd çatışmır',
    ],
    'table' => [
        'title' => 'Əməkdaş status siyahısı',
        'tabel_no' => 'Tabel nömrəsi',
        'full_name' => 'Tam ad',
        'status' => 'Status',
        'worked_hours' => 'İşlənmiş (saat)',
        'late_minutes' => 'Gecikmə (dəq)',
        'early_minutes' => 'Tez çıxış (dəq)',
    ],
    'statuses' => [
        'all' => 'hamısı',
        'present' => 'işdə',
        'late' => 'gecikib',
        'absent' => 'yoxdur',
        'missing' => 'gündəlik qeyd çatışmır',
        'manual_present' => 'manual işdə',
        'holiday_worked' => 'bayramda işləyib',
        'weekend_worked' => 'həftəsonu işləyib',
        'manual_absence' => 'manual yoxluq',
        'unknown' => 'naməlum',
    ],
];
