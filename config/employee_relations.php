<?php

return [
    /*
    | Hər mərhələ üçün SLA (təqvim günü). Mərhələ açılanda `stage_due_at` buradan
    | hesablanır; iş növündə `investigation_sla_days` verilibsə, «araşdırılır» mərhələsi
    | üçün o üstünlük təşkil edir. Qərardan sonrakı apellyasiya pəncərəsi SLA deyil.
    |
    | Qeyd: intizam tənbehi xətanın aşkar edildiyi gündən bir ay ərzində verilə bilər
    | (ƏM Md.187.2) — araşdırma SLA-sı bu müddətdən qısa saxlanmalıdır.
    */
    'sla_days' => [
        'open' => (int) env('EMPLOYEE_RELATIONS_SLA_OPEN_DAYS', 3),
        'investigating' => (int) env('EMPLOYEE_RELATIONS_SLA_INVESTIGATING_DAYS', 20),
        'appealed' => (int) env('EMPLOYEE_RELATIONS_SLA_APPEALED_DAYS', 3),
        'appeal_review' => (int) env('EMPLOYEE_RELATIONS_SLA_APPEAL_REVIEW_DAYS', 10),
        'final' => (int) env('EMPLOYEE_RELATIONS_SLA_FINAL_DAYS', 5),
    ],

    /* İşçinin qərara Şəxsi kabinetdən etiraz edə biləcəyi müddət (təqvim günü). */
    'appeal_days' => (int) env('EMPLOYEE_RELATIONS_APPEAL_DAYS', 10),

    /* Qərar ekranında hüquqi müddətin bitməsinə neçə gün qalmış xəbərdarlıq edilsin. */
    'deadline_warning_days' => (int) env('EMPLOYEE_RELATIONS_DEADLINE_WARNING_DAYS', 7),

    'statistics' => [
        /* Eyni işçi barəsində bu qədər və daha çox iş «təkrarlanan hal» sayılır. */
        'repeat_threshold' => (int) env('EMPLOYEE_RELATIONS_REPEAT_THRESHOLD', 2),
        /* Bu saydan kiçik qruplar gizlədilir (şəxsin tanınmaması üçün). */
        'min_group_size' => (int) env('EMPLOYEE_RELATIONS_MIN_GROUP_SIZE', 3),
    ],

    'evidence' => [
        'max_kb' => (int) env('EMPLOYEE_RELATIONS_EVIDENCE_MAX_KB', 10240),
        'directory' => 'employee-relations/evidence',
    ],

    'reminders' => [
        'schedule_enabled' => (bool) env('EMPLOYEE_RELATIONS_SLA_REMINDERS_ENABLED', true),
        'daily_at' => env('EMPLOYEE_RELATIONS_SLA_REMINDERS_DAILY_AT', '06:10'),
        'cooldown_hours' => (int) env('EMPLOYEE_RELATIONS_SLA_REMINDERS_COOLDOWN_HOURS', 24),
        'max_per_run' => (int) env('EMPLOYEE_RELATIONS_SLA_REMINDERS_MAX', 200),
    ],
];
