# Hesabatlar Modulu Master To-Do

> **Status (10.10.2026):** bu sənədin işarələri fevral–may aylarından sonra yenilənməyib; açıq qalan bəndlərin çoxu real vəziyyəti əks etdirməyə bilər. Aktiv iş siyahısı `~/Desktop/projects/ISLER_PLANI.md`-dir. Bəndi icra etməzdən əvvəl kodda yoxlayın.
>
> **Tutuşdurma (11.10.2026):** 64/64 bənd kodla yoxlanıb: 32 `[x]`, 32 açıq. Əsas modul (standart/dinamik/müqayisəli hesabat, Excel/CSV/print) hazırdır; preset, audit, konsolidə/executive paket, scheduled/queue export, unit testlər və 7 permission modeli yoxdur.
>
> **Yeniləmə (11.10.2026, sonra):** rol matrisi, audit, saved presets, planlı hesabatlar (email) və server-side PDF quruldu — 38 `[x]`, 26 açıq. Hələ açıq: konsolidə/executive paket, queue-based export center, məzuniyyət və əmək haqqı qruplarının öz hesabatları (icazələr hazırdır, hesabat yoxdur).

Bu sənəd müştərinin verdiyi aşağıdakı tələb əsasında hazırlanıb:

- İR prosesləri üzrə bütün məlumatların sistemləşdirilmiş formada çıxarılması
- rəhbərlik üçün analitik hesabatların hazırlanması
- standart hesabatlar
- dinamik filtrli hesabatlar
- müqayisəli hesabatlar
- qrafik və diaqramlarla vizuallaşdırma
- Excel / PDF / CSV ixracı
- illik və rüblük konsolidə və müqayisəli hesabatlar

Bu sənədin məqsədi yeni `Hesabatlar` modulunu sıfırdan yazmağa başlamazdan əvvəl:

1. mövcud modullardan nəyi reuse edə biləcəyimizi göstərmək
2. nəyin ayrıca tikilməli olduğunu ayırmaq
3. MVP -> Phase 2 -> Phase 3 icra sırasını vermək

---

## 1. Modulun məqsədi

`Hesabatlar` modulu HRM daxilində artıq mövcud olan əməliyyat məlumatlarını vahid analitik qatda toplamalıdır.

Məqsəd:

1. rəhbərlik üçün vahid hesabat mərkəzi yaratmaq
2. HR istifadəçisi üçün standart və sərbəst filtrli hesabat çıxarmaq
3. müxtəlif modullardakı göstəriciləri eyni ekran və eyni export dili ilə təqdim etmək
4. aylıq, rüblük və illik müqayisəli analitikanı sabit və performanslı şəkildə göstərmək

---

## 2. Müştəri tələbinin biznes mənası

Müştərinin tələbi faktiki olaraq 3 səviyyəli report sistemi istəyir:

### A. Standart hesabatlar

Hazır KPI və cədvəllər:

1. işçi sayı
2. gender bölgüsü
3. yaş bölgüsü
4. təcrübə bölgüsü
5. işə qəbul statistikası
6. xitam statistikası
7. vəzifə dəyişikliyi statistikası
8. davamiyyət və tabel hesabatları
9. təlim hesabatları
10. performans nəticələri

### B. Dinamik hesabatlar

İstifadəçi filtrlə öz reportunu qurur:

1. tarix
2. struktur / şöbə / fakültə
3. vəzifə
4. status
5. digər seçilmiş sahələr

### C. İdarəetmə analitikası

1. illər üzrə müqayisə
2. struktur üzrə müqayisə
3. vəzifə üzrə müqayisə
4. qrafik / diaqram
5. konsolidə olunmuş rüblük / illik hesabat

---

## 3. Mövcud sistemdə reuse edilə biləcək data mənbələri

Yeni modulun hamısını sıfırdan yazmağa ehtiyac yoxdur. Mövcud modullarda artıq kifayət qədər hesabat dataları var.

### 3.1 Personnel

Hazır məlumatlar:

1. ümumi işçi siyahısı
2. struktur, vəzifə, status, gender kimi əsas HR atributları
3. join / leave tarixləri
4. pending və active status məntiqi
5. excel export bazası

Buradan çıxacaq hesabatlar:

1. işçi sayı
2. gender bölgüsü
3. yaş bölgüsü
4. struktur üzrə say
5. vəzifə üzrə say
6. işə qəbul / xitam trendi

### 3.2 Attendance

Hazır məlumatlar:

1. `attendance_daily_ledgers`
2. `attendance_monthly_summaries`
3. puantaj
4. manager summary
5. month close snapshot

Buradan çıxacaq hesabatlar:

1. davamiyyət xülasəsi
2. tabel bazalı aylıq hesabat
3. gecikmə / erkən çıxış
4. overtime
5. open exceptions
6. struktur üzrə attendance müqayisəsi

### 3.3 Training Needs

Hazır məlumatlar:

1. executive reports
2. annual / quarterly rows
3. employee hours rows
4. delivery type rows
5. outcome rows
6. coverage summary

Buradan çıxacaq hesabatlar:

1. təlim coverage
2. rüblük / illik təlim icrası
3. iştirak saatları
4. təlim növü bölgüsü
5. nəticə və outcome göstəriciləri

### 3.4 Performance Evaluation

Hazır məlumatlar:

1. forms report
2. summary report
3. weak links report
4. weak pivot report
5. audit report
6. test sessions / attempts / answers report
7. reviewer turnaround
8. personnel outcomes

Buradan çıxacaq hesabatlar:

1. performans xülasəsi
2. nəticə kateqoriyaları
3. zəif sahə analitikası
4. test nəticəsi və skor göstəriciləri
5. struktur və period üzrə performans trendi

---

## 4. Mövcud sistemdə boş qalan hissələr

Ayrıca `Hesabatlar` modulu üçün çatmayan əsas qatlar bunlardır:

1. bütün modulları bir ekranda birləşdirən vahid hesabat mərkəzi yoxdur
2. istifadəçi seçdiyi sütun və filtrlərlə dinamik report qura bilmir
3. müqayisəli HR analytics vahid modeldə yoxdur
4. Excel / CSV export var, amma PDF bütün report növlərində standart deyil
5. illik / rüblük konsolidə report paketi yoxdur
6. chart / visualization qatları ayrı modul kimi qurulmayıb
7. rəhbərlik üçün “executive dashboard” vahid şəkildə mövcud deyil

---

## 5. Modulun scope-u

### In scope

1. standart HR hesabat kataloqu
2. filterable dinamik hesabat qurucusu
3. müqayisəli hesabatlar
4. chart və diaqram vizuallaşdırmaları
5. Excel / CSV / PDF export
6. illik və rüblük konsolidə hesabatlar
7. permission və audit izləri

### Out of scope

1. real-time BI engine
2. Excel-like custom formula builder
3. external BI tools ilə canlı bidirectional sync
4. predictive analytics və AI insight engine

---

## 6. Standart hesabat kataloqu

MVP-də aşağıdakı hazır hesabatlar olmalıdır.

### 6.1 Headcount report

Filtrlər:

1. tarix
2. struktur
3. vəzifə
4. status
5. gender

Göstəricilər:

1. ümumi işçi sayı
2. struktur üzrə say
3. fakültə / şöbə üzrə say
4. vəzifə üzrə say

### 6.2 Demographics report

1. gender bölgüsü
2. yaş bandları
3. əmək stajı / təcrübə bandları
4. struktur üzrə demographic breakdown

### 6.3 Workforce movement report

1. işə qəbul
2. xitam
3. struktur daxili keçid
4. vəzifə dəyişikliyi
5. period üzrə müqayisə

### 6.4 Attendance and tabel report

1. aylıq attendance xülasəsi
2. tabel nəticələri
3. gecikmə
4. erkən çıxış
5. overtime
6. yoxluq və səbəb bölgüsü

### 6.5 Training report

1. təlim sayı
2. iştirak edən əməkdaş sayı
3. saatlar
4. completion rate
5. outcome / coverage göstəriciləri

### 6.6 Performance report

1. performans form sayı
2. average score
3. high / medium / weak bölgüsü
4. test nəticələri
5. weak area strukturu

---

## 7. Dinamik hesabat qurucusu

Bu hissə modulu dəyərli edən əsas qatdır.

İstifadəçi aşağıdakı seçimlərlə öz hesabatını qura bilməlidir:

### 7.1 Report source

1. Personnel
2. Attendance
3. Training
4. Performance
5. Cross-module consolidated

### 7.2 Filter builder

1. tarix aralığı
2. il / rüb / ay
3. struktur
4. vəzifə
5. status
6. gender
7. təcrübə bandı
8. user-defined əlavə filterlər

### 7.3 Grouping

1. struktura görə
2. vəzifəyə görə
3. aya görə
4. rübə görə
5. ilə görə
6. genderə görə

### 7.4 Metrics

1. count
2. sum
3. average
4. percentage
5. rate

### 7.5 Output modes

1. table
2. bar chart
3. line chart
4. donut / pie
5. stacked comparison

Qayda:

1. dinamik report builder yalnız əvvəlcədən whitelist edilmiş sütun və metriklərlə işləməlidir
2. istifadəçi arbitrary SQL və ya sərbəst expression yaza bilməməlidir

---

## 8. Müqayisəli hesabatlar

Bu hissə ayrıca ekran kimi planlanmalıdır.

Minimum müqayisə növləri:

1. il-üzrə müqayisə
2. rüb-üzrə müqayisə
3. struktur-üzrə müqayisə
4. vəzifə-üzrə müqayisə
5. status-üzrə müqayisə

MVP müqayisə paketi:

1. headcount year-over-year
2. hiring vs termination
3. attendance month-over-month
4. training quarter-over-quarter
5. performance category comparison

---

## 9. İxrac imkanları

### MVP

1. Excel
2. CSV
3. PDF

### Qaydalar

1. hər report ekranında export permission ayrıca yoxlanmalıdır
2. PDF üçün iki yol:
   - Phase 1: print-friendly Blade + browser-to-PDF
   - Phase 2: server-side PDF export
3. export audit log-a düşməlidir
4. böyük export-lar queue job ilə hazırlanmalıdır

---

## 10. Təklif olunan əsas ekranlar

### 10.1 Reports dashboard

Məqsəd:

1. standart report kartları
2. son export-lar
3. quick filters
4. rəhbərlik KPI blokları

### 10.2 Standard reports

1. hazır report kataloqu
2. hər report üçün filter paneli
3. cədvəl + chart görünüşü

### 10.3 Dynamic report builder

1. source seçimi
2. filter builder
3. metric builder
4. preview result
5. save as preset

### 10.4 Comparative reports

1. iki və ya daha çox period müqayisəsi
2. struktur və vəzifə üzrə müqayisə
3. chart overlays

### 10.5 Export center

1. export queue
2. generated files
3. status
4. failed export retry

---

## 11. Texniki arxitektura

Yeni modul aşağıdakı pattern ilə qurulmalıdır.

### 11.1 Modul skeleti

1. `app/Modules/Reports`
2. `ReportsServiceProvider`
3. `Routes/web.php`
4. `Resources/views/reports::...`
5. `Resources/lang/az|en`
6. explicit Livewire alias map

### 11.2 Livewire komponentləri

1. `reports.dashboard`
2. `reports.standard-reports`
3. `reports.dynamic-builder`
4. `reports.comparisons`
5. `reports.export-center`

İkinci səviyyə island-lar:

1. `reports.widgets.headcount`
2. `reports.widgets.attendance`
3. `reports.widgets.training`
4. `reports.widgets.performance`
5. `reports.widgets.chart-panel`

### 11.3 Service layer

1. `ReportsAccessService`
2. `StandardReportCatalogService`
3. `HeadcountReportService`
4. `AttendanceReportService`
5. `TrainingReportService`
6. `PerformanceReportService`
7. `DynamicReportBuilderService`
8. `ComparativeReportService`
9. `ReportExportService`
10. `ReportSnapshotService`

### 11.4 Read model prinsipi

Ağır report-lar birbaşa Livewire içində join olunmamalıdır.

Qaydalar:

1. hər report source üçün ayrıca read service olmalıdır
2. lazım olduqda aylıq / rüblük snapshot cədvəlləri istifadə olunmalıdır
3. chart dataları ayrıca lightweight DTO kimi qaytarılmalıdır
4. export query ilə UI query ayrılmalıdır

---

## 12. Təklif olunan data source xəritəsi

### Personnel source

Mövcud əsas cədvəllər:

1. `personnels`
2. identity / card / education / labor activity əlaqələri

Yeni read model ehtiyacı:

1. `hr_headcount_snapshots`
2. `hr_workforce_movements`

### Attendance source

Mövcud əsas cədvəllər:

1. `attendance_daily_ledgers`
2. `attendance_monthly_summaries`
3. `attendance_daily_structure_summaries`

Yeni read model ehtiyacı:

1. `attendance_report_snapshots` yalnız əlavə performans ehtiyacı yaranarsa

### Training source

Mövcud əsas servis qatları:

1. `TrainingExecutiveReportingService`
2. `TrainingNeedCoverageService`

Yeni read model ehtiyacı:

1. ilk fazada ayrıca cədvəl məcburi deyil
2. sonradan ağır executive history üçün quarterly snapshot düşünülə bilər

### Performance source

Mövcud əsas servis qatları:

1. `PerformanceEvaluationReportingService`

Yeni read model ehtiyacı:

1. phase 2-də performance executive snapshot lazım ola bilər

---

## 13. Permission modeli

Minimum permission set:

1. `view-reports-dashboard`
2. `view-standard-reports`
3. `build-dynamic-reports`
4. `view-comparative-reports`
5. `export-reports`
6. `manage-report-presets`
7. `view-report-audit`

Rollar:

1. HR Admin
2. HR Specialist
3. Executive / rəhbərlik
4. Auditor

Employee roluna bu modul bütöv şəkildə verilməməlidir.

---

## 14. Audit və təhlükəsizlik

Audit olunmalı hadisələr:

1. report açıldı
2. export edildi
3. preset yaradıldı
4. preset dəyişdirildi
5. preset silindi
6. comparative export hazırlandı

Security qaydaları:

1. PII olan report-larda field-level masking tətbiq oluna bilməlidir
2. hər export istifadəçi və tarixlə audit olunmalıdır
3. query builder yalnız whitelist field-lərlə işləməlidir

---

## 15. Performans prinsipləri

Bu modul başlanğıcdan performanslı qurulmalıdır.

1. böyük report-lar queue-based export ilə getməlidir
2. dashboard və chart dataları snapshot və aggregate cədvəllərlə sürətlənməlidir
3. hər əsas ekran üçün query-budget command olmalıdır
4. hər əsas ekran üçün render benchmark command olmalıdır
5. report cache key-ləri filter signature ilə qurulmalıdır
6. PDF generasiyası request thread-i bloklamamalıdır

---

## 16. Fazalı icra planı

### Phase 0. Kəşf və data müqaviləsi

- [x] standard report kataloqunu təsdiqlə — kod: app/Modules/Reports/Application/Services/StandardReportCatalogService.php::all() (6 hesabat: headcount, demographics, movements, attendance, training, performance)
- [ ] hər report üçün source module və owner təyin et — yoxlanmadı (kodda hesabat→mənbə modul/owner xəritəsi yoxdur; sənəd qərarıdır)
- [ ] eyni anlayışlar üçün canonical metric dictionary qur — qismən: app/Modules/Reports/Application/Services/DynamicReportBuilderService.php::metricOptions() mənbə üzrə metrikləri sadalayır; vahid metric lüğəti yoxdur
- [ ] period anlayışlarını sabitlə: gün / ay / rüb / il — qismən: ay/il filtri var (Livewire/Dashboard.php), app/Modules/Reports/Application/Services/ReportsSqlDialectService.php::quarterExpression() var, amma rüb/gün periodu UI-da yoxdur
- [x] headcount, attendance, training, performance üçün ortaq filter dilini yaz — kod: ortaq year/month/structure_id filtri — app/Modules/Reports/Livewire/Dashboard.php (panel dövrü Comparisons/Standard/Dynamic-ə ötürülür), tests/Feature/Reports/ReportsDashboardTest.php::test_comparisons_take_the_panel_period

### Phase 1. MVP standard reports

- [x] yeni `Reports` modul skeletini yarat — kod: app/Modules/Reports/ modulu
- [x] provider, routes, alias map əlavə et — kod: app/Modules/Reports/Providers/ReportsServiceProvider.php::componentMap(), app/Modules/Reports/Routes/web.php
- [x] reports dashboard qur — kod: app/Modules/Reports/Livewire/Dashboard.php, Livewire/Overview.php, marşrut reports
- [x] standard reports ekranını qur — kod: app/Modules/Reports/Livewire/StandardReports.php
- [x] headcount report servisini yaz — kod: app/Modules/Reports/Application/Services/StandardReportService.php::headcount()
- [x] demographics report servisini yaz — kod: StandardReportService::demographics()
- [x] workforce movement report servisini yaz — kod: StandardReportService::movements()
- [x] attendance summary report servisini yaz — kod: StandardReportService::attendance()
- [x] training summary report servisini yaz — kod: StandardReportService::training()
- [x] performance summary report servisini yaz — kod: StandardReportService::performance()
- [x] Excel export əlavə et — kod: app/Modules/Reports/Exports/ReportsTableExport.php, StandardReports::exportExcel()
- [x] CSV export əlavə et — kod: StandardReports::exportCsv(), DynamicBuilder::exportCsv()
- [x] print-friendly PDF çıxışı əlavə et — kod: app/Modules/Reports/Resources/views/print/report.blade.php, marşrut reports.print-standard/reports.print-dynamic (brauzer print; server-side PDF mühərriki yoxdur)
- [x] permission seed və role matrix yaz — kod: app/Modules/Reports/Support/ReportPermissions.php (tək mənbə), miqrasiya 2026_10_11_100000_seed_report_group_permissions.php: qrup üzrə baxış (`show-{personnel,attendance,leave,payroll,training,performance}-reports`; maaş qrupu əlavə `show-payroll` istəyir), `manage-report-presets`, `schedule-reports`; köhnə `show-reports`/`export-reports` rolları və birbaşa icazəli istifadəçilər qrup icazələrini avtomatik alır. Test: tests/Feature/Reports/ReportsPermissionMatrixTest.php. `view-report-audit` ayrıca yaradılmayıb — audit `show-audit-logs` ilə Audit jurnalında görünür
- [x] audit log hadisələrini əlavə et — kod: Application/Services/ReportsAuditLogger.php (log adı `reports`): hesabat açıldı, ixrac (xlsx/csv/print/pdf), preset yaradıldı/dəyişdi/silindi/tətbiq edildi, planlı hesabat yaradıldı/dəyişdi/silindi/göndərildi/uğursuz

### Phase 2. Dynamic builder və müqayisə

- [x] dynamic report builder ekranını qur — kod: app/Modules/Reports/Livewire/DynamicBuilder.php
- [x] source / metric / grouping whitelist-i yaz — kod: DynamicReportBuilderService::sourceOptions()/groupOptions()/metricOptions() (match ilə whitelist)
- [x] comparative report ekranını qur — kod: app/Modules/Reports/Livewire/Comparisons.php, Application/Services/ComparativeReportService.php
- [x] year-over-year müqayisələri əlavə et — kod: ComparativeReportService::headcountYearComparison()/trainingYearComparison(), attendanceMonthComparison()
- [ ] structure comparison əlavə et — qismən: ComparativeReportService::build() structureId süzgəci var, strukturlar arası müqayisə kartı yoxdur; DynamicBuilder 'structure' üzrə qruplaşdırır
- [x] saved presets əlavə et — kod: `report_presets` cədvəli, Domain/Models/ReportPreset.php, Policies/ReportPresetPolicy.php, Application/Services/ReportPresetService.php; panelin «Saxlanmış filtrlər» bölməsi (şəxsi və `manage-report-presets` ilə paylaşılan); tətbiqdə struktur görünürlüyü yenidən yoxlanılır. Test: tests/Feature/Reports/ReportPresetsTest.php
- [ ] chart panel komponentlərini yaz — qismən: app/Modules/Reports/Resources/views/livewire/reports/overview.blade.php-də inline bar chart var; ayrıca chart komponenti yoxdur
- [ ] chart export / print uyğunluğu əlavə et — kodda tapılmadı

### Phase 3. Konsolidə və rəhbərlik paketi

- [ ] rüblük konsolidə hesabat — kodda tapılmadı
- [ ] illik konsolidə hesabat — kodda tapılmadı
- [ ] rəhbərlik executive dashboard — qismən: Overview dashboard (Livewire/Overview.php) var; ayrıca executive dashboard yoxdur
- [x] cross-module combined KPI — kod: app/Modules/Reports/Application/Services/ReportsOverviewService.php::build() (headcount + attendanceReportKpis + trainingKpis + performanceKpis)
- [ ] department scorecard — kodda tapılmadı
- [ ] queue-based heavy export center — kodda tapılmadı (export sinxrondur, queue job yoxdur)

### Phase 4. Advanced maturity

- [x] scheduled report generation — kod: `report_schedules` + `report_schedule_recipients`, «Planlı hesabatlar» tabı (Livewire/Schedules.php), `reports:send-scheduled` (Kernel, hər 5 dəq.), Application/Jobs/SendScheduledReportJob.php (sync-də də işləyir), sahibin icazə və strukturları ilə qurulur. Test: tests/Feature/Reports/ReportSchedulesTest.php
- [x] email / notification-based report delivery — kod: Application/Mail/ScheduledReportMail.php; alıcılar yalnız hesabata baxa bilən aktiv sistem istifadəçiləridir
- [x] server-side PDF engine — kod: Application/Pdf/LibreOfficeReportPdfRenderer.php (başsız LibreOffice, DejaVu Sans gömülü), StandardReports/DynamicBuilder/Dashboard `exportPdf`, planlı hesabatın PDF formatı. Test: tests/Feature/Reports/ReportPdfExportTest.php (Azərbaycan hərfləri pdftotext ilə yoxlanılır)
- [ ] deeper snapshot strategy — kodda tapılmadı
- [ ] anomaly / trend insight layer — kodda tapılmadı

---

## 17. UX to-do

- [ ] reports dashboard üçün ayrıca vizual istiqamət seç — yoxlanmadı (dizayn qərarı, kodla yoxlanmır)
- [ ] standart report kartlarını vahid dizayn dilinə sal — yoxlanmadı
- [ ] chart komponentləri üçün reusable card pattern qur — kodda tapılmadı (ayrıca reusable chart card yoxdur)
- [x] filter panelini bütün report ekranlarında eyni saxla — kod: app/Modules/Reports/Livewire/Dashboard.php (ortaq panel: dövr/struktur), tests/Feature/Reports/ReportsDashboardTest.php::test_header_print_and_export_follow_the_tabs_current_selection
- [x] empty state və no-data state-ləri ayrıca dizayn et — kod: x-ui.empty-state `reports::dashboard.empty.no_report_data` — standard-reports.blade.php, dynamic-builder.blade.php, comparisons.blade.php, overview.blade.php
- [ ] export progress və file-ready notification göstər — kodda tapılmadı (export sinxron yükləmədir)

---

## 18. Test plan

### Unit tests

- [ ] metric formatter — kodda tapılmadı
- [ ] filter normalization — kodda tapılmadı (normalizeFilters ayrıca test olunmur)
- [ ] grouping builder — kodda tapılmadı
- [ ] comparison period resolver — qismən: tests/Feature/Reports/ReportsDashboardTest.php::test_comparisons_take_the_panel_period (feature səviyyəsində)
- [ ] export payload builder — kodda tapılmadı

### Feature tests

- [x] headcount report render — kod: tests/Feature/Reports/ReportsServiceRegressionTest.php::test_personnel_reports_use_selected_report_date_snapshot (headcount build; ekran render testi yoxdur)
- [ ] attendance report render — kodda tapılmadı (attendance standart hesabatı üçün test yoxdur; yalnız query-budget probe)
- [x] training report render — kod: tests/Feature/Reports/ReportsServiceRegressionTest.php::test_standard_training_report_does_not_duplicate_participants_or_hours_when_session_has_multiple_feedback_rows
- [x] performance report render — kod: tests/Feature/Reports/ReportsServiceRegressionTest.php::test_performance_reports_respect_selected_period_overlap
- [x] dynamic builder filter scenario — kod: tests/Feature/Reports/ReportsDashboardTest.php::test_dynamic_builder_accepts_preset_filters_from_parent_route_context, tests/Feature/Security/ScopeReportsTest.php
- [x] export permission checks — kod: tests/Feature/Reports/ReportsDashboardTest.php::test_header_export_requires_export_permission

### Performance tests

- [x] `reports:query-budget` — kod: app/Modules/Reports/Console/Commands/ReportsQueryBudgetCommand.php (`reports:query-budget`), tests/Feature/Console/ReportsQueryBudgetCommandTest.php
- [x] `reports:render-benchmark` — kod: app/Modules/Reports/Console/Commands/ReportsRenderBenchmarkCommand.php (`reports:render-benchmark`), tests/Feature/Console/ReportsRenderBenchmarkCommandTest.php
- [ ] böyük filter kombinasiyaları üçün regression — kodda tapılmadı

### Smoke checks

- [x] standard report açılır — kod: tests/Feature/Reports/ReportsDashboardTest.php::test_standard_reports_component_renders_catalog_and_table_view
- [x] filter dəyişir — kod: tests/Feature/Reports/ReportsDashboardTest.php::test_dynamic_builder_accepts_preset_filters_from_parent_route_context, test_comparisons_take_the_panel_period
- [ ] chart görünür — yoxlanmadı (chart görünüşü üçün test yoxdur)
- [x] export başlayır — kod: tests/Feature/Reports/ReportsDashboardTest.php::test_header_export_downloads_the_report_behind_the_active_tab
- [ ] PDF print görünüşü işləyir — yoxlanmadı (print görünüşü üçün test yoxdur; marşrut reports.print-standard var)

---

## 19. Açıq suallar

Bu suallar implementasiyadan əvvəl dəqiqləşdirilməlidir:

1. `Hesabatlar` ayrıca modul olacaq, yoxsa yalnız vahid menü altında mövcud modulların report-larını birləşdirəcək?
2. rəhbərlik ekranında yalnız xülasə KPI-lar olacaq, yoxsa drill-down cədvəllər də?
3. PDF export ilk fazada brauzer print ilə yetərlidirmi?
4. saved preset-lər istifadəçi-bazalı olacaq, yoxsa ortaq komanda preset-ləri də olmalıdır?
5. konsolidə illik / rüblük hesabatlarda hansı format rəsmi sayılır?

---

## 20. Tövsiyə olunan ilk icra sırası

Ən doğru başlanğıc sırası budur:

1. `Reports` modul skeleti
2. standard report catalog
3. headcount + demographics
4. attendance summary
5. training summary
6. performance summary
7. Excel / CSV / PDF export
8. sonra dynamic builder

Bu sıra ilə getsək:

1. müştəriyə tez görünən nəticə verərik
2. mövcud modullardakı report datalarını reuse edərik
3. riskli hissə olan dynamic builder-ə daha sabit baza ilə keçərik

---

## 21. Hazır qərar

Bu modul üçün ən sağlam yanaşma:

1. əvvəl `standard reports hub` qurmaq
2. sonra `comparative analytics`
3. ən sonda `dynamic report builder`

Yəni birbaşa sərbəst report generator ilə başlamaq doğru deyil.

