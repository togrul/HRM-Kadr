<?php

return [
    'title' => 'Düz 15 il düzəlişi',
    'subtitle' => '11.10.2026-dan əvvəl başlamış iş illərində stajı iş ilinin başlanğıcında düz 15 il olan işçilərə staj əlavəsi 4 gün hesablanıb; ƏM m.116.1 üzrə :law gün düzgündür. Sistem həmin iş illərini tapır, işləyən işçilərə fərqi düzəliş kimi yazır (bir dəfə). Xitam olunmuş işçilər ayrıca siyahıdadır.',
    'actions' => [
        'scan' => 'Təsirlənənləri tap',
        'apply' => 'Düzəlişi yaz',
    ],
    'sections' => [
        'active' => 'İşləyən işçilər — balansa düzəliş yazılır',
        'terminated' => 'Xitam olunmuş işçilər — kompensasiya fərqi (mühasibatlıq üçün)',
    ],
    'hints' => [
        'terminated' => 'Balansa avtomatik yazılmır. Fərq günləri üçün kompensasiya (ƏM m.144.2, KMQ16) mühasibatlıq tərəfindən kompensasiya əmri ilə rəsmiləşdirilir; bir günlük məbləğ — orta aylıq əmək haqqı ÷ 30,4 (m.140.3).',
    ],
    'table' => [
        'employee' => 'İşçi',
        'work_year' => 'İş ili',
        'seniority_days' => 'Staj əlavəsi (hesablanıb)',
        'missing' => 'Düzəliş',
        'status' => 'Vəziyyət',
        'leave_date' => 'Xitam tarixi',
        'compensation_days' => 'Fərq (gün)',
        'note' => 'Qeyd',
    ],
    'status' => [
        'applied' => 'Yazılıb',
        'pending' => 'Gözləyir',
    ],
    'notes' => [
        'compensate' => 'Kompensasiya fərqi ödənilməlidir (m.144.2, KMQ16).',
        'before_kmq16' => 'Xitam 18.02.2026-dan (KMQ16) əvvəldir: o vaxt m.144.3 əlavə məzuniyyətin kompensasiyasını istisna edirdi — hüquqşünas qərar verməlidir.',
    ],
    'empty' => [
        'title' => 'Təsirlənən iş ili yoxdur',
        'active' => 'İşləyən işçilər arasında düzəliş tələb edən iş ili tapılmadı.',
        'terminated' => 'Xitam olunmuş işçilər arasında fərq yoxdur.',
    ],
    'confirm' => [
        'title' => 'Düzəliş yazılsın?',
        'text' => ':count iş ilinə staj əlavəsinin fərqi düzəliş kimi yazılacaq (ƏM m.116.1). Təkrar işə salınanda ikinci dəfə yazılmır.',
    ],
    'messages' => [
        'nothing' => 'Yazılacaq düzəliş yoxdur.',
        'applied' => 'Düzəliş yazıldı: :count iş ili.',
    ],
    'entry_note' => 'ƏM m.116.1: düz 15 il staj — :law gün (+:days), düz 15 il düzəlişi',
];
