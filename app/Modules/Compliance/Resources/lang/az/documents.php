<?php

return [
    'kicker' => 'Sənəd uyğunluğu',
    'title' => 'Sənəd bitmə və uyğunluq mərkəzi',
    'description' => 'Etibarlılıq tarixi bitmiş və yaxın müddətdə bitəcək əməkdaş sənədlərini bir paneldə izləyin.',
    'summary' => [
        'total' => 'Ümumi sənəd',
        'expired' => 'Vaxtı bitib',
        'expiring_30' => '30 günə bitir',
        'expiring_60' => '60 günə bitir',
        'valid' => 'Qüvvədədir',
        'missing' => 'Çatışmayan',
        'compliance_score' => 'Uyğunluq balı',
    ],
    'fields' => [
        'search' => 'Axtarış',
        'document_type' => 'Sənəd tipi',
        'status' => 'Status',
    ],
    'placeholders' => [
        'search' => 'Əməkdaş, tabel, struktur və ya sənəd nömrəsi...',
    ],
    'filters' => [
        'all_types' => 'Bütün tiplər',
        'all_statuses' => 'Bütün statuslar',
    ],
    'types' => [
        'service_card' => 'Xidməti vəsiqə',
        'passport' => 'Pasport',
        'contract' => 'Əmək müqaviləsi',
    ],
    'status' => [
        'expired' => 'Vaxtı bitib',
        'expiring_30' => 'Təcili yenilənməlidir',
        'expiring_60' => 'Yaxınlaşır',
        'valid' => 'Qüvvədədir',
        'missing' => 'Sənəd yoxdur',
    ],
    'actions' => [
        'reset_filters' => 'Filterləri sıfırla',
        'reset_short' => 'Sıfırla',
        'export_csv' => 'CSV-yə ixrac et',
    ],
    'labels' => [
        'unassigned' => 'Təyin edilməyib',
        'result_count' => ':count nəticə',
        'required_document' => 'Tələb olunan sənəd əlavə edilməyib',
        'not_available' => 'Yoxdur',
        'indefinite' => 'Müddətsiz',
        'contract_from' => ':date tarixindən',
        'contract_duration' => ':months ay',
        'structure_score' => 'Struktur uyğunluğu',
        'risk_summary' => ':missing çatışmır · :expired vaxtı bitib',
    ],
    'columns' => [
        'employee' => 'Əməkdaş',
        'document' => 'Sənəd',
        'expires_at' => 'Bitmə tarixi',
        'days_left' => 'Qalan gün',
        'status' => 'Status',
        'structure' => 'Struktur',
        'position' => 'Vəzifə',
        'document_number' => 'Sənəd nömrəsi',
        'score' => 'Bal',
        'risk' => 'Risk',
    ],
    'sections' => [
        'structure_scores' => 'Strukturlar üzrə uyğunluq riski',
        'document_register' => 'Tələb olunan sənəd reyestri',
    ],
    'reminders' => [
        'notification_title' => 'Sənəd uyğunluğu riski: :count qeyd',
        'notification_created' => 'Bildiriş kampaniyası yaradıldı: #:id',
        'more_items' => 'Əlavə :count riskli qeyd var.',
    ],
    'empty' => 'Uyğun sənəd tapılmadı.',
];
