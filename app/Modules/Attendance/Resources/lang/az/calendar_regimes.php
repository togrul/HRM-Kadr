<?php

return [
    'title' => 'İş rejimi təqvimi',
    'description' => 'Ümumi və struktur səviyyəli iş günü, həftəsonu və bayram override-larını idarə edin.',
    'cards' => [
        'create' => 'Yeni təqvim qaydası',
        'edit' => 'Təqvim qaydasını redaktə et',
        'list' => 'Təqvim qaydaları',
    ],
    'fields' => [
        'date' => 'Tarix',
        'day_type' => 'Gün növü',
        'name' => 'Ad',
        'is_paid' => 'Ödənişli gün',
        'scope_type' => 'Əhatə növü',
        'structure' => 'Struktur',
        'scope' => 'Əhatə',
    ],
    'options' => [
        'workday' => 'İş günü',
        'weekend' => 'Həftəsonu',
        'holiday' => 'Bayram',
        'global' => 'Ümumi',
        'structure' => 'Xüsusi struktur',
        'select_structure' => 'Struktur seçin',
        'yes' => 'Bəli',
        'no' => 'Xeyr',
    ],
    'actions' => [
        'save' => 'Yadda saxla',
        'cancel' => 'Ləğv et',
    ],
    'auto_labels' => [
        'weekend' => 'Avto həftəsonu',
    ],
    'messages' => [
        'saved' => 'Təqvim qaydası yadda saxlanıldı.',
        'deleted' => 'Təqvim qaydası silindi.',
        'delete_confirmation_required' => 'Silinmə əməliyyatı üçün təsdiq tələb olunur.',
        'duplicate_scope_date' => 'Bu tarix və əhatə üçün artıq təqvim qaydası mövcuddur.',
    ],
    'confirmations' => [
        'delete' => 'Bu təqvim qaydası silinsin?',
    ],
];
