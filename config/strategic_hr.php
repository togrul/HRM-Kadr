<?php

return [
    /*
    |--------------------------------------------------------------------------
    | HR Command Center (Ana səhifə — «diqqət tələb edənlər» panelləri)
    |--------------------------------------------------------------------------
    |
    | Hər panel ən çox `rows` sətir göstərir; sayğac tam saydır. Pəncərələr gün ilə.
    |
    */
    'command_center' => [
        'rows' => (int) env('HR_COMMAND_CENTER_ROWS', 5),
        'probation_window_days' => 14,
        'document_window_days' => 30,
        'requisition_stale_days' => 14,
    ],

    /*
    |--------------------------------------------------------------------------
    | Karyera hazırlıq skoru (0–100)
    |--------------------------------------------------------------------------
    |
    | Skor komponentlərin çəkili cəmidir: hər komponent 0–100 arası normallaşdırılır və
    | çəkisinə vurulur; çəkilərin cəmi 100 olmasa, nisbətə görə normallaşdırılır. Çəki 0
    | olan komponent hesablamaya daxil olmur.
    |
    */
    'career_readiness' => [
        'weights' => [
            'performance' => 35,
            'training' => 20,
            'kpi' => 20,
            'portfolio' => 10,
            'tenure' => 15,
        ],

        /* Performans forması final_score şkalasının yuxarı həddi (bu dəyər = 100 bal). */
        'performance_scale_max' => 100,

        /* KPI kartı yekun balı (hədəfin %-i): bu dəyər və yuxarısı tam bal verir. */
        'kpi_full_at' => 100,

        /* Son neçə ildəki tamamlanmış təlimlər sayılır və neçəsi tam bal verir. */
        'training_lookback_years' => 3,
        'training_target_count' => 4,

        /* Portfolio: neçə qeyd (sertifikat, layihə, mükafat və s.) tam bal verir. */
        'portfolio_target_count' => 5,

        /* Staj: neçə il tam bal verir. */
        'tenure_target_years' => 5,

        /* Skor zolaqları: hazırdır / inkişafdadır / erkən mərhələ. */
        'bands' => [
            'ready' => 75,
            'developing' => 50,
        ],
    ],
];
