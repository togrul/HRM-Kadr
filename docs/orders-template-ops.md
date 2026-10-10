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

Köhnə köçürmələrin ilkin, yalnız oxuma inventarı:

```bash
php artisan orders:audit-transfer-timing
php artisan orders:audit-transfer-timing --order-id=123
```

Komanda təsdiqlənmiş əmrləri (soft-delete daxil) 200-lük paketlərlə oxuyur və JSON
Lines verir: hər tapıntı ayrıca sətir, sonda `summary` və təsnifat üzrə saylar.
`confirmed_early_application` yalnız saxlanmış `transfer_applied_at` vaxtının
tətbiqin yerli saat qurşağında `effective_date` günündən əvvəl olduğunu bildirir.
Tarix artıq keçmiş olsa da bu sübut hesabatda qalır. Həmin günün tətbiqi erkən sayılmır.

Tətbiq vaxtı olmayan əvvəlki snapshot-lar `legacy_requires_history_review` kimi
çıxır; bu təsnifat erkən köçürməni sübut etmir. Sonradan dəyişmiş işçi kartından,
əmrin yaradılma/redaktə vaxtından tətbiq vaxtı təxmin edilmir. Qüvvəyə minmə tarixi
olmayan köhnə snapshot-da əmrin tarixi götürülür. Yanlış tarix və vaxtlar, həmçinin
tətbiq vaxtı yazılmış pending vəziyyəti ayrıca göstərilir. Normal pending və
vaxtında tətbiq edilmiş əmrlər hesabatdan çıxarılır.

Hesabat heç bir kartı, əmri və audit jurnalını dəyişmir. Tam tarixçə yoxlamasını,
silinmiş/mövcud olmayan snapshot-ların araşdırılmasını və istehsalatda icranı əvəz
etmir. `legacy_requires_history_review` üzrə qərar üçün işçi tarixçəsi və backup
ilə uyğunlaşdırma tələb olunur; avtomatik geri çevirmə nəzərdə tutulmayıb.

## Render benchmark testlərinin vaxt ölçüsü

`tests/Feature/Console/*RenderBenchmarkCommandTest.php` funksional yoxlamadır:
Livewire komponentləri və qarşılıqlı əməliyyatlar həqiqətən icra edilir, cavab
ölçüsü və render xətaları yoxlanılır. `UsesDeterministicRenderTiming` yalnız testdə
qaytarılan vaxtı 10 ms edir. Paylaşılan CPU yükü, ilk Blade/Blaze kompilyasiyası və
prosesin planlaşdırılması həmin testlərin uğurunu dəyişmir. Bu 10 ms performans
nəticəsi deyil. `RenderBenchmarkBudgetTest` vaxt/cavab həddinin aşılması, sərhəd
dəyəri və render xətasının uğursuz çıxış kodu yaratmasını ayrıca yoxlayır.

Real vaxt ölçmək üçün `orders:list-render-benchmark --json` kimi Artisan
komandalarını test runner-dən kənar, nəzarət olunan yük altında icra etmək lazımdır.
İstehsalat komandaları və onların vaxt/payload hədləri dəyişdirilməyib.
