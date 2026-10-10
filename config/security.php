<?php

/*
 * Tətbiq səviyyəsində təhlükəsizlik parametrləri (proxy/host etibarı, HTTP başlıqları,
 * xarici URL-lərə sorğu siyasəti).
 */
return [

    /*
     * Etibar edilən reverse proxy-lər (X-Forwarded-* başlıqları yalnız bunlardan qəbul olunur).
     * Vergüllə ayrılmış IP/CIDR siyahısı; '*' — hər mənbəyə etibar (yalnız origin birbaşa
     * internetdən əlçatan deyilsə). Standart: loopback + özəl şəbəkələr (Docker/Coolify proxy-si).
     */
    'trusted_proxies' => env('TRUSTED_PROXIES', '127.0.0.1,::1,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16,fc00::/7'),

    /*
     * Əlavə etibarlı host-lar (vergüllə, regex deyil — dəqiq ad). APP_URL host-u və onun
     * subdomenləri həmişə etibarlıdır. Saxta Host / X-Forwarded-Host ilə gələn sorğu rədd
     * edilir ki, şifrə bərpası linkləri yad domenə yönəldilə bilməsin.
     */
    'trusted_hosts' => env('TRUSTED_HOSTS', ''),

    /* HSTS yalnız production-da və HTTPS sorğularında göndərilir. */
    'hsts' => [
        'enabled' => (bool) env('SECURITY_HSTS_ENABLED', true),
        'max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),
        'include_subdomains' => (bool) env('SECURITY_HSTS_INCLUDE_SUBDOMAINS', false),
    ],

    /*
     * Server tərəfindən açılan xarici URL-lər (KPI REST konnektoru, portfel link yoxlaması).
     * Daxili/özəl ünvanlar (loopback, 10/8, 169.254/16 metadata…) həmişə bloklanır;
     * yalnız buradakı host-lara (vergüllə) istisna verilir — məs. daxili şəbəkədəki KPI sistemi.
     */
    'outbound' => [
        'allowed_hosts' => env('OUTBOUND_ALLOWED_HOSTS', ''),
        'require_https_in_production' => (bool) env('OUTBOUND_REQUIRE_HTTPS', true),
    ],
];
