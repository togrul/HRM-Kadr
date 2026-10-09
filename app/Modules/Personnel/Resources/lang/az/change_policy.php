<?php

return [
    'title' => 'Dəyişiklik siyasəti',
    'modes' => [
        'free' => 'Sərbəst',
        'journal' => 'Jurnal',
        'order' => 'Yalnız əmrlə',
    ],
    'mode_hints' => [
        'free' => 'Sahə istənilən vaxt dəyişdirilir.',
        'journal' => 'Dəyişiklik sərbəstdir, amma səbəb yazılır və audit jurnalına düşür.',
        'order' => 'Sahə yalnız təsdiqlənmiş əmrlə dəyişir; formada kilidlidir.',
    ],
    'groups' => [
        'assignment' => [
            'label' => 'Struktur bölmə və vəzifə',
            'description' => 'Köçürmə və işə qəbul əmrləri ilə dəyişir.',
        ],
        'salary' => [
            'label' => 'Əmək haqqı',
            'description' => 'Kompensasiya bölməsində təyinat; əmək haqqının dəyişdirilməsi və işə qəbul əmrləri.',
        ],
        'surname' => [
            'label' => 'Soyad',
            'description' => 'Soyadın dəyişdirilməsi əmri ilə dəyişir.',
        ],
        'employment_dates' => [
            'label' => 'İşə qəbul və xitam tarixləri',
            'description' => 'İşə qəbul və əmək müqaviləsinə xitam əmrləri ilə yazılır.',
        ],
        'contact' => [
            'label' => 'Əlaqə məlumatları',
            'description' => 'Telefon, mobil, e-poçt, faktiki və qeydiyyat ünvanı.',
        ],
        'family' => [
            'label' => 'Ailə üzvləri',
            'description' => 'Qohumluq əlaqələri (forma addımı «Ailə»).',
        ],
        'documents' => [
            'label' => 'Sənədlər',
            'description' => 'Şəxsiyyət vəsiqəsi, xidməti vəsiqə və pasportlar.',
        ],
        'photo_notes' => [
            'label' => 'Şəkil və qeydlər',
            'description' => 'Əməkdaşın şəkli və əlavə vacib məlumat.',
        ],
    ],
    'badges' => [
        'order' => '(əmrlə)',
        'journal' => '(jurnal)',
    ],
    'hints' => [
        'order_only' => ':group yalnız əmrlə dəyişir.',
        'create_order' => 'Əmr yarat',
    ],
    'reason' => [
        'label' => 'Dəyişikliyin səbəbi',
        'placeholder' => 'Məs.: sənəddəki yazı səhvinin düzəldilməsi',
        'hint' => 'Jurnal rejimli sahələr dəyişib (:groups) — səbəb audit jurnalına yazılacaq.',
    ],
    'validation' => [
        'order_only' => ':group yalnız əmrlə dəyişdirilir.',
        'reason_required' => 'Bu dəyişiklik jurnala yazılır: səbəbi ən azı :min simvolla göstərin.',
    ],
];
