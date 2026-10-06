<?php

/*
|--------------------------------------------------------------------------
| Pulsuz demo rejimi
|--------------------------------------------------------------------------
|
| Yalnız demo serverdə `DEMO_MODE=true` yazılır. Bayraq sönülüdürsə Demo modulu
| heç nə qeydiyyatdan keçirmir: middleware, əmrlər və baza dəyişdirmə yoxdur.
|
| Hər demo müştərinin öz MySQL bazası (və öz audit bazası) olur. Bazalar demo
| serverin əsas bazasından ("şablon") struktur kimi kopyalanır; məlumat isə
| yalnız aşağıdakı `reference_tables` cədvəllərindən köçürülür — qalanlar boşdur.
|
*/

return [

    'enabled' => (bool) env('DEMO_MODE', false),

    /** Yeni demo neçə gün aktiv qalır (`demo:create --days` ilə dəyişir). */
    'default_days' => (int) env('DEMO_DEFAULT_DAYS', 3),

    /** Müştəri bazalarının adı: {prefix}{açar} və {prefix}{açar}_audit. */
    'database_prefix' => env('DEMO_DATABASE_PREFIX', 'hrm_demo_'),

    /** Demo müştəriyə verilən rol (şablon bazada mövcud olmalıdır). */
    'role' => env('DEMO_ROLE', 'Admin'),

    /** Müddəti bitmiş demo üçün göstərilən əlaqə məlumatı. */
    'contact' => env('DEMO_CONTACT', ''),

    /**
     * Şablondan MƏLUMATI ilə köçürülən cədvəllər — hamıya aid soraqlar.
     * Siyahıda olmayan hər cədvəl yeni bazada boş yaranır (işçilər, əmrlər,
     * struktur, tabel, maaş və s.).
     */
    'reference_tables' => [
        // Sistem — bunlarsız proqram işləmir
        'migrations',
        'menus',
        'permissions',
        'roles',
        'role_has_permissions',
        'settings',

        // Ölkə, şəhər, dil
        'countries',
        'country_translations',
        'cities',
        'languages',

        // Şəxsi məlumat soraqları
        'kinships',
        'social_origins',
        'disabilities',
        'education_degrees',
        'education_document_types',
        'education_forms',
        'education_types',
        'educational_institutions',
        'scientific_degree_and_names',

        // Təltif və cəza kataloqları
        'award_types',
        'awards',
        'punishment_types',
        'punishments',

        // Məzuniyyət, müraciət
        'leave_types',
        'appeal_statuses',

        // Əmrlər — kateqoriya, növ, şablonlar
        'order_statuses',
        'order_categories',
        'order_types',
        'orders',
        'order_word_templates',
        'order_word_template_versions',

        // Əmək haqqı və kompensasiya qaydaları
        'statutory_rates',
        'work_norms',
        'compensation_regimes',
        'compensation_components',

        // İşə qəbul soraqları
        'candidate_sources',
        'candidate_rejection_reasons',

        // Təlim səviyyələri və işə qəbul / ayrılma plan şablonları
        'training_levels',
        'employee_lifecycle_plan_templates',
        'employee_lifecycle_task_templates',
    ],

];
