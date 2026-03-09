<?php

return [
    'title' => 'İstisnalar qutusu',
    'filters' => [
        'title' => 'Növbə filtrləri',
        'description' => 'Həll edilməmiş davamiyyət anomaliyalarına fokuslanmaq üçün istisna növü və tarix aralığından istifadə edin.',
        'status' => 'Status',
        'type' => 'Növ',
        'from' => 'Başlanğıc',
        'to' => 'Son',
    ],
    'scope' => [
        'badge' => 'Struktur scope',
        'description' => 'Yalnız seçilmiş struktur ağacındakı əməkdaşlar göstərilir.',
    ],
    'table' => [
        'title' => 'Açıq qeydlər',
        'date' => 'Tarix',
        'tabel_no' => 'Tabel nömrəsi',
        'personnel' => 'Əməkdaş',
        'type' => 'Növ',
        'message' => 'Mesaj',
        'status' => 'Status',
        'action' => 'Əməliyyat',
    ],
    'statuses' => [
        'open' => 'açıq',
        'resolved' => 'həll edildi',
        'all' => 'hamısı',
    ],
    'types' => [
        'all' => 'hamısı',
        'missing_in' => 'giriş yoxdur',
        'missing_out' => 'çıxış yoxdur',
        'unmatched_punch' => 'uyğunsuz punch',
    ],
    'actions' => [
        'resolve' => 'Həll et',
        'reopen' => 'Yenidən aç',
    ],
    'messages' => [
        'resolution_note' => 'İstisnalar qutusundan həll edildi.',
        'resolved_description' => 'Davamiyyət istisnası qutudan həll edildi.',
        'reopened_description' => 'Davamiyyət istisnası qutudan yenidən açıldı.',
        'resolved' => 'İstisna həll edildi.',
        'reopened' => 'İstisna yenidən açıldı.',
    ],
];
