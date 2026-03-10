# Attendance Ops / Commands Guide

Bu sənəd Attendance modulunun texniki əməliyyatları, command-ləri və planlı iş axınları üçün hazırlanıb.

Bu guide kim üçündür:

- texniki operator
- devops / support engineer
- HR sistem owner
- incident araşdıran developer

Bu sənədin məqsədi:

- hansı command-in nə etdiyini sabitləmək
- hansı halda hansı command-in işləndiyini göstərmək
- təhlükəsiz işlətmə sırasını vermək
- incident zamanı sürətli qərar ağacı yaratmaq

## 1. Attendance əməliyyat məntiqi

Attendance texniki axını belədir:

1. raw punch gəlir və ya override yaranır
2. processing pipeline ledger yaradır və ya yeniləyir
3. exceptions və overtime sync olunur
4. overview və puantaj nəticəsi görünür
5. ay sonunda snapshot və export alınır

Bu səbəbdən ops tərəfdə əsas qayda budur:

- source dəyişibsə, ya process, ya recalculate, ya da snapshot lazımdır

## 2. Əsas command-lər

### 2.1 `attendance:punches:process`

Məqsəd:

- raw punch-ları normallaşdırmaq
- giriş/çıxış cütlərini qurmaq
- günlük ledger yaratmaq

Əsas istifadə:

```bash
php artisan attendance:punches:process
```

Tarix aralığı ilə:

```bash
php artisan attendance:punches:process --from=2026-03-01 --to=2026-03-10
```

Queue ilə:

```bash
php artisan attendance:punches:process --from=2026-03-01 --to=2026-03-10 --queue
```

Nə zaman işlədilir:

- punch ingest-dən sonra
- gecikmiş device import-dan sonra
- source filter ilə tək axını yenidən işlətmək lazım olanda

Nəticə:

- attendance_daily_ledgers yenilənir
- exceptions və overtime sync olunur

### 2.2 `attendance:recalculate`

Məqsəd:

- müəyyən tarix aralığı üçün ledger-ləri yenidən hesablamaq

Əsas istifadə:

```bash
php artisan attendance:recalculate --from=2026-03-01 --to=2026-03-31
```

Structure scope ilə:

```bash
php artisan attendance:recalculate --from=2026-03-01 --to=2026-03-31 --structure_id=12
```

Tabel no scope ilə:

```bash
php artisan attendance:recalculate --from=2026-03-01 --to=2026-03-31 --tabel_no=10001 --tabel_no=10002
```

Nə zaman işlədilir:

- shift policy dəyişəndə
- shift assignment düzələndə
- calendar rule dəyişəndə
- leave/vacation/business trip sync-də şübhə olanda
- manual data geriyə dönük dəyişəndə

Nəticə:

- seçilən scope üçün ledger yenidən qurulur
- puantaj və overview nəticəsi düzəlir

### 2.3 `attendance:monthly-snapshot`

Məqsəd:

- aylıq yekun summary yaratmaq
- istəyə görə ayı lock etmək

Əsas istifadə:

```bash
php artisan attendance:monthly-snapshot --year=2026 --month=3
```

Əvvəlki ay üçün:

```bash
php artisan attendance:monthly-snapshot --previous-month
```

Lock ilə:

```bash
php artisan attendance:monthly-snapshot --previous-month --lock
```

Nə zaman işlədilir:

- ay sonu payroll/export hazırlığında
- lock öncəsi yekun sabitləmə zamanı

Nəticə:

- monthly summary yaranır
- `--lock` verilmişsə ay bağlanır

### 2.4 `attendance:query-budget`

Məqsəd:

- overview, daily monitor və puantaj üçün query sayını ölçmək
- performans regressiyasını erkən tutmaq

Əsas istifadə:

```bash
php artisan attendance:query-budget --json --allow-empty
```

Custom budget ilə:

```bash
php artisan attendance:query-budget --year=2026 --month=3 --overview-budget=15 --daily-budget=10 --puantaj-budget=8
```

Nə zaman işlədilir:

- böyük attendance refactor-dan sonra
- query optimizasiyasından sonra
- release öncəsi health check kimi

Nəticə:

- flow üzrə query sayı görünür
- budget aşılırsa failure qaytarır

### 2.5 `attendance:calendars:seed-weekends`

Məqsəd:

- seçilən ay üçün həftəsonlarını global calendar kimi seed etmək

Əsas istifadə:

```bash
php artisan attendance:calendars:seed-weekends
```

Növbəti ay üçün:

```bash
php artisan attendance:calendars:seed-weekends --next-month
```

Tarixlə:

```bash
php artisan attendance:calendars:seed-weekends --year=2026 --month=4
```

Nə zaman işlədilir:

- ay əvvəlində avtomatik
- yeni mühit qurulanda
- tarixi bərpa və ya data seed ssenarisində

Nəticə:

- həftəsonu günləri calendar qatında görünür
- mövcud manual holiday/workday rule-lar override olunmur

## 3. Təhlükəsiz işlətmə sırası

### Ssenari 1. Punch import gəldi

1. `attendance:punches:process`
2. `Daily Monitor` və `Puantaj` yoxla
3. ehtiyac varsa `attendance:query-budget`

### Ssenari 2. Calendar qaydası dəyişdi

1. UI save sonrası auto-recalc işləyir
2. şübhə varsa `attendance:recalculate --from=... --to=...`
3. puantaj və overview nəticəsini yoxla

### Ssenari 3. Shift assignment düzəldi

1. assignment save et
2. təsirlənən tarix aralığı üçün `attendance:recalculate`
3. daily monitor və puantaj yoxla

### Ssenari 4. Month close hazırlanır

1. open queue-ları təmizlə
2. lazım gələrsə `attendance:recalculate`
3. `attendance:monthly-snapshot`
4. sonra `--lock`

## 4. Scheduler necə işləyir

Planlı işləyən attendance cron-ları:

- `attendance:punches:process`
- `attendance:monthly-snapshot`
- `attendance:calendars:seed-weekends`
- `attendance:query-budget --json --allow-empty`

Schedule konfiqurasiyası:

- `[Kernel.php](/Users/togruljalalli/Desktop/projects/HRM/app/Console/Kernel.php)`
- `[config/attendance.php](/Users/togruljalalli/Desktop/projects/HRM/config/attendance.php)`

Əsas prinsip:

- process periodik işləyir
- snapshot ay əvvəli əvvəlki ay üçün işləyə bilər
- weekend seed hər ayın 1-də işləyir
- query-budget observability üçün gündəlik/həftəlik işləyə bilər

## 5. Nə vaxt hansı command lazımdır

`Punch görünmür`:

- əvvəl `attendance:punches:process`

`UI nəticəsi source ilə uyğun gəlmir`:

- `attendance:recalculate`

`Payroll üçün ayı sabitləmək lazımdır`:

- `attendance:monthly-snapshot`

`Performans pisləşib`:

- `attendance:query-budget`

`Yeni ay başladı`:

- `attendance:calendars:seed-weekends`

## 6. Incident ssenariləri

### Incident 1. Leave approve olundu, Attendance dəyişmədi

Yoxlanacaq:

1. leave status həqiqətən approved-durmu
2. observer və sync işləyibmi
3. lazımdırsa təsirlənən aralıq üçün `attendance:recalculate`

### Incident 2. Bayram əlavə olundu, puantaj dəyişmədi

Yoxlanacaq:

1. calendar scope və date doğrudurmu
2. auto-recalc işləyibmi
3. lazımdırsa `attendance:recalculate --from=... --to=...`

### Incident 3. Query sayı kəskin artdı

Yoxlanacaq:

1. `attendance:query-budget --json`
2. overview, daily və puantaj nəticələrindən hansının aşıldığı
3. son deploy-da read-service və ya Livewire komponent dəyişiklikləri

### Incident 4. Month close sonrası səhv tapıldı

Axın:

1. səbəbi müəyyən et
2. unlock et
3. düzəlişi et
4. `attendance:recalculate`
5. yenidən snapshot və lock

## 7. Performans nəzarəti

Hazırkı əsas budget-lər:

- overview: `15`
- daily monitor: `10`
- puantaj: `8`

Bu rəqəmlər:

- query sayı
- render/read path yükü
- real runtime benchmark

əsasında sıxlaşdırılıb.

Release öncəsi minimum yoxlama:

1. `attendance:query-budget --json --allow-empty`
2. əsas attendance feature testləri
3. böyük data dəyişikliyindən sonra puantaj render yoxlaması

## 8. Tövsiyə olunan release checklist

1. migration-lar tamdır
2. scheduler aktivdir
3. query-budget green-dir
4. punch process işləyir
5. weekend seed işləyir
6. month snapshot axını smoke test-dən keçib

## 9. Bu sənədlə birlikdə baxılmalı sənədlər

- `[Attendance User Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/attendance-user-guide.md)`
- `[Attendance Admin Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/attendance-admin-guide.md)`
- `[Attendance Approval Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/attendance-approval-guide.md)`
- `[Attendance Permission Matrix](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/attendance-permission-matrix.md)`
