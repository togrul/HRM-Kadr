<?php

/*
 * maatwebsite/excel paketinin yalnız dəyişdirilmiş açarları. Qalan açarlar paketin öz
 * konfiqurasiyasından birləşdirilir (mergeConfigFrom üst səviyyə açarları birləşdirir).
 */
return [
    'value_binder' => [
        /*
         * Bütün ixraclar mətni formula kimi yox, mətn kimi yazır; təhlükəli prefikslər
         * (=, +, -, @, TAB, CR) apostrofla zərərsizləşdirilir. Bax: SafeValueBinder.
         */
        'default' => App\Support\Exports\SafeValueBinder::class,
    ],
];
