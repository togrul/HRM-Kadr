# Orders Template Ops Guide

## 1) CI quality gate

This repo provides a dedicated quality gate workflow:

- File: `.github/workflows/orders-template-quality-gate.yml`
- Local equivalent: `composer ci:orders-gate`

Gate commands:

1. `php artisan orders:templates:metrics --json --min-total=1`
2. `php artisan orders:templates:query-budget --json --allow-empty`

`metrics` command can enforce thresholds with options:

- `--max-error-rate`
- `--max-p95`
- `--max-p99`
- `--min-total`

## 2) Scheduled reports

Command:

- `php artisan orders:templates:report --days=1 --allow-empty-budget --json`

Scheduler wiring is in `app/Console/Kernel.php`.

Enable via env:

- `ORDERS_TEMPLATE_REPORTS_ENABLED=true`
- `ORDERS_TEMPLATE_REPORT_CHANNELS=log,slack,telegram`
- `ORDERS_TEMPLATE_REPORT_LOG_FILE=logs/orders-template-metrics.log`
- `ORDERS_TEMPLATE_REPORT_SLACK_WEBHOOK=...`
- `ORDERS_TEMPLATE_REPORT_TELEGRAM_BOT_TOKEN=...`
- `ORDERS_TEMPLATE_REPORT_TELEGRAM_CHAT_ID=...`

Optional report thresholds (applied through `metrics`):

- `ORDERS_TEMPLATE_REPORT_METRICS_MAX_ERROR_RATE`
- `ORDERS_TEMPLATE_REPORT_METRICS_MAX_P95`
- `ORDERS_TEMPLATE_REPORT_METRICS_MAX_P99`
- `ORDERS_TEMPLATE_REPORT_METRICS_MIN_TOTAL`

## 3) No-legacy freeze audit

Audit command:


Tracks:

- strict mode status
- template-set/version readiness counts
- legacy footprint counters (`orders.content`, `components.dynamic_fields`, legacy snapshot usage)

Use this before any destructive schema cleanup.


## Gələcək tarixli köçürmələr

Yeni təsdiqlənən köçürmə əmri gələcək qüvvəyə minmə tarixi göstərirsə cari struktur
və vəzifə həmin günə qədər dəyişmir. Hədəf sahələr əmrin snapshot-unda saxlanır.
Maaş üzrə regrade təklifi də yalnız faktiki tətbiq zamanı yaranır.

`orders:apply-due-transfers` planlaşdırılmış və vaxtı çatmış köçürmələri tarix, sonra
əmr ID-si ardıcıllığında tətbiq edir. Kernel onu beş dəqiqədən bir,
`withoutOverlapping()` ilə çağırır; Orders modulu bağlıdırsa iş planlaşdırılmır.
Hər əmrin və işçinin sətri DB kilidi ilə oxunur. Tətbiq tamamlandıqdan sonra pending
bayrağı götürülür, buna görə təkrar icra ikinci dəyişiklik yaratmır. Bir əmr xətalıdırsa
digərləri işlənir, əmr ID-si server jurnalına yazılır və komanda uğursuz çıxış kodu verir.

```bash
php artisan orders:apply-due-transfers
```

Əmr JSON sayları qaytarır: `applied`, `skipped`, `failed`. İstehsalatda adi Laravel
`schedule:run` mexanizmi işləməlidir; kod və testlər onun real serverdə işlədiyini
təsdiqləmir. Server dayanıbsa növbəti icra vaxtı keçmiş gözləyən əmrləri də tətbiq edir.

Tətbiqdən əvvəl ləğv cari işçi kartına toxunmur. Tətbiqdən sonra geri çevirmə faktiki
icra zamanı saxlanmış əvvəlki struktur/vəzifədən istifadə edir. Gələcək tarix üçün
məzuniyyət hesabının vəzifə faktları gözləyən köçürməni də nəzərə alır.

Əvvəlki kodla artıq vaxtından əvvəl tətbiq edilmiş köçürmələr pending bayrağı daşımır.
Onlar avtomatik geri çevrilmir: cari vəziyyət, sonrakı dəyişikliklər və əmrin tarixçəsi
istehsalat backup-u ilə birlikdə ayrıca yoxlanmalıdır. Çoxşəxsli köçürmə bu paketdə
aktivləşdirilməyib; həmin effekt əvvəlki kimi tək işçi üçündür.
