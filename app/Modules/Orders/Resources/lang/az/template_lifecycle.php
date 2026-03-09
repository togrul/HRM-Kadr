<?php

return [
    'defaults' => [
        'auto_created_from_onboarding_wizard' => 'Onboarding wizard tərəfindən avtomatik yaradılıb',
    ],
    'messages' => [
        'no_order_types_for_selected_template' => 'Seçilmiş şablon üçün sifariş tipi tapılmadı.',
        'order_type_not_found' => 'Sifariş tipi tapılmadı.',
        'selected_version_invalid_for_order_type' => 'Seçilmiş versiya bu sifariş tipi üçün keçərli deyil.',
        'selected_version_invalid' => 'Seçilmiş versiya keçərli deyil.',
        'version_not_found' => 'Versiya tapılmadı.',
        'run_preview_before_publishing' => 'Dərc etməzdən əvvəl preview render edin.',
        'template_coverage_not_inspectable' => 'Şablon coverage inspect edilə bilmir. Əvvəlcə DOCX yükləyin.',
        'cannot_publish_missing_mappings_exist' => 'Çatışmayan mapping olduğu üçün dərc etmək olmur.',
        'could_not_publish_selected_version' => 'Seçilmiş versiya dərc edilə bilmədi.',
        'template_coverage_unavailable' => 'Şablon coverage mövcud deyil. DOCX yükləyin və coverage işə salın.',
        'cannot_run_preview_missing_mappings' => 'Preview işə düşmür. Çatışmayan mapping-lər: :placeholders',
        'cannot_publish_missing_mappings' => 'Dərc etmək olmur. Çatışmayan mapping-lər: :placeholders',
        'selected_template_version_missing' => 'Seçilmiş şablon versiyası mövcud deyil.',
        'single_active_version_invariant_failed' => 'Tək aktiv versiya invariantı uğursuz oldu: template_set=:template_set_id',
    ],
];
