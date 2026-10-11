<?php

/*
|--------------------------------------------------------------------------
| İmtiyazlar (Benefits) modulu
|--------------------------------------------------------------------------
|
| Modulun standart vəziyyəti şirkət növündən (quraşdırma profili, APP_TYPE → config/profiles.php
| «features.benefits») gəlir; admin onu Admin → HR siyasəti diaqnostikası ekranında əl ilə açıb
| bağlaya bilər (settings cədvəlində saxlanır — BenefitsAvailability).
|
| İmtiyaz növləri və onların standart vergi rejimi. Hər paket yaradılanda bu dəyərlər forma
| doldurulur; mühasib paket səviyyəsində dəyişə bilər. Hüquqi əsas: docs/payroll-legal-basis.md, «İmtiyazlar — natura formasında gəlir» bölməsi.
|
|   taxable        — işəgötürənin ödədiyi hissə gəlir vergisinə cəlb olunan gəlirdir (VM m.98)
|   affects_social — DSMF / işsizlik / icbari tibbi sığorta bazasına daxildir
|                    («Sosial sığorta haqqında» Qanun m.15)
|   basis          — benefits::dashboard.tax_basis.<basis> açarı (maddə + izah)
|   exclusive      — işçinin eyni növdən yalnız bir aktiv paketi ola bilər (məs. tibbi A və ya B)
|   exempt_daily_norm — (ixtiyari) işçi üzrə gündəlik azad norma, AZN. Təyin olunubsa `taxable` /
|                    `affects_social` bayraqları nəzərə alınmır: norma × iş günü daxilindəki hissə nə
|                    vergiyə, nə sığortaya cəlb olunmur; artıq hissə gəlir vergisinə və YALNIZ DSMF-ə
|                    cəlb olunur (işsizlik və icbari tibbi sığorta haqqına heç vaxt).
|                    Yemək kartı: VM m.119.2 + DVX izahı 10.07.2026 (5 AZN/gün).
|
*/

return [
    'types' => [
        'medical_insurance' => ['taxable' => false, 'affects_social' => false, 'basis' => 'vm_102_1_8_medical', 'exclusive' => true, 'allows_dependents' => true],
        'life_insurance' => ['taxable' => true, 'affects_social' => true, 'basis' => 'vm_98_2_5', 'exclusive' => true, 'allows_dependents' => false],
        'life_savings_insurance' => ['taxable' => false, 'affects_social' => false, 'basis' => 'vm_102_1_8_savings', 'exclusive' => true, 'allows_dependents' => false],
        'meal_card' => ['taxable' => false, 'affects_social' => true, 'basis' => 'vm_98_5_meal', 'exclusive' => true, 'allows_dependents' => false, 'exempt_daily_norm' => 5.0],
        'transport' => ['taxable' => true, 'affects_social' => true, 'basis' => 'vm_98_2_3', 'exclusive' => true, 'allows_dependents' => false],
        'mobile' => ['taxable' => false, 'affects_social' => false, 'basis' => 'business_expense', 'exclusive' => true, 'allows_dependents' => false],
        'gym' => ['taxable' => false, 'affects_social' => true, 'basis' => 'vm_98_5_social', 'exclusive' => true, 'allows_dependents' => true],
        'other' => ['taxable' => true, 'affects_social' => true, 'basis' => 'vm_98_1', 'exclusive' => false, 'allows_dependents' => false],
    ],

    /*
     * Payroll ilə əlaqə (Payroll/Compensation koduna toxunmadan):
     *  - işçinin payı Compensation komponenti kimi işçinin aktiv kompensasiyasına sətir olaraq
     *    yazılır (təkrarlanan tutulma);
     *  - işəgötürənin ödədiyi və vergiyə/sosial sığortaya cəlb olunan hissə hər ay birdəfəlik
     *    gəlir (PayrollOneOffEarnings) kimi verilir və eyni məbləğdə mənfi, vergisiz sətirlə
     *    əvəzlənir — işçiyə nağd pul verilmir, yalnız vergi/sığorta bazası artır.
     */
    'payroll' => [
        'employee_share_component' => 'benefit_employee_share',
        'imputed_code' => 'benefit_in_kind',
        'offset_code' => 'benefit_in_kind_offset',
        // Norma üzrə artıq hissə: gəlir vergisinə və yalnız DSMF bazasına daxildir. Kod Payroll-da
        // `StatutoryEngine::DSMF_ONLY_CODES` ilə eyni olmalıdır (dəyişdirilmir).
        'imputed_dsmf_code' => 'benefit_in_kind_dsmf',
    ],

    'renewal' => [
        // Müqavilə bitməzdən neçə gün əvvəl seçim pəncərəsi açılır (paketdə dəyişdirilə bilər).
        'window_days' => (int) env('BENEFITS_RENEWAL_WINDOW_DAYS', 30),
    ],

    'schedule' => [
        'enabled' => (bool) env('BENEFITS_SCHEDULE_ENABLED', true),
        'daily_at' => (string) env('BENEFITS_SCHEDULE_DAILY_AT', '05:10'),
    ],
];
