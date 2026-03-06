# Attendance Ops Guide

Bu sənəd Attendance modulunda **1) acceptance closure**, **3) scheduler/ops**, **4) performance tuning/ops** axınını standartlaşdırır.

## 1) Acceptance closure

Release-dən əvvəl minimum manual checks:

1. `attendance.view` icazəsi olan istifadəçi `/attendance` səhifəsini açır.
2. `daily-monitor` tab-da bugünkü `present/late/absent` statistikası görünür.
3. `puantaj` tab-da ay üzrə grid yüklənir, gün hüceyrə tooltip-ləri işləyir.
4. `manual` tab-da entry əlavə olunur və approval axını tamamlanır.
5. `exceptions` tab-da bir exception resolve edilir.
6. `overtime` tab-da bir request approve/reject edilir.
7. `month-close` tab-da:
   - close
   - snapshot
   - unlock
   - payroll export (xlsx/csv)
   uğurla işləyir.
8. Lock edilmiş ay üçün manual/overtime edit guard aktivdir.

Local smoke/acceptance command set:

- `php artisan attendance:query-budget --json --allow-empty`
- `php artisan test tests/Unit/Modules/Attendance`
- `php artisan test tests/Unit/Architecture/AttendanceLivewireReadBoundaryTest.php tests/Feature/Console/AttendanceQueryBudgetCommandTest.php`

## 3) Scheduler / Ops

`app/Console/Kernel.php` daxilində Attendance üçün planlı job-lar:

1. Punch process:
   - command: `attendance:punches:process`
   - config: `attendance.processing.schedule_enabled`, `attendance.processing.schedule_every_minutes`
2. Monthly snapshot:
   - command: `attendance:monthly-snapshot --previous-month [--lock]`
   - config: `attendance.snapshot.*`
3. Query budget observability:
   - command: `attendance:query-budget --json --allow-empty`
   - daily + weekly schedule
   - config: `attendance.observability.reports.*`

### Env ayarları

- `ATTENDANCE_PROCESS_SCHEDULE_ENABLED`
- `ATTENDANCE_PROCESS_SCHEDULE_EVERY_MINUTES`
- `ATTENDANCE_SNAPSHOT_SCHEDULE_ENABLED`
- `ATTENDANCE_SNAPSHOT_SCHEDULE_DAY`
- `ATTENDANCE_SNAPSHOT_SCHEDULE_AT`
- `ATTENDANCE_SNAPSHOT_SCHEDULE_LOCK`
- `ATTENDANCE_REPORTS_ENABLED`
- `ATTENDANCE_REPORT_DAILY_AT`
- `ATTENDANCE_REPORT_WEEKLY_DAY`
- `ATTENDANCE_REPORT_WEEKLY_AT`
- `ATTENDANCE_REPORT_APPEND_OUTPUT`
- `ATTENDANCE_REPORT_OUTPUT_FILE`

## 4) Performance tuning / Ops

### A) Query budget gate

Perf regressions üçün primary gate:

- `php artisan attendance:query-budget --json --allow-empty`

Probe flows:

1. `overview_build`
2. `daily_monitor_load`
3. `puantaj_grid_load`

Budget limitləri:

- `ATTENDANCE_QUERY_BUDGET_OVERVIEW`
- `ATTENDANCE_QUERY_BUDGET_DAILY_MONITOR`
- `ATTENDANCE_QUERY_BUDGET_PUANTAJ`

### B) Cache strategy

Overview üçün cache key pattern:

- `attendance:{org}:{year}:{month}:{structure}`

Invalidation trigger-lər:

1. punch pipeline process
2. recalculate
3. month close/unlock
4. monthly snapshot refresh

### C) Pre-aggregation

`attendance_daily_structure_summaries` cədvəli Overview yükünü azaltmaq üçün istifadə olunur.

Rebuild entry-points:

- punch pipeline post-process
- month close/snapshot axını

### D) CI script

Composer script:

- `composer ci:attendance-gate`

Bu script query budget + architecture/read-boundary testlərini bir yerdə yoxlayır.
