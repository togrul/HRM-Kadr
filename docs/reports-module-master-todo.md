# Hesabatlar Modulu Master To-Do

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

- [ ] standard report kataloqunu təsdiqlə
- [ ] hər report üçün source module və owner təyin et
- [ ] eyni anlayışlar üçün canonical metric dictionary qur
- [ ] period anlayışlarını sabitlə: gün / ay / rüb / il
- [ ] headcount, attendance, training, performance üçün ortaq filter dilini yaz

### Phase 1. MVP standard reports

- [ ] yeni `Reports` modul skeletini yarat
- [ ] provider, routes, alias map əlavə et
- [ ] reports dashboard qur
- [ ] standard reports ekranını qur
- [ ] headcount report servisini yaz
- [ ] demographics report servisini yaz
- [ ] workforce movement report servisini yaz
- [ ] attendance summary report servisini yaz
- [ ] training summary report servisini yaz
- [ ] performance summary report servisini yaz
- [ ] Excel export əlavə et
- [ ] CSV export əlavə et
- [ ] print-friendly PDF çıxışı əlavə et
- [ ] permission seed və role matrix yaz
- [ ] audit log hadisələrini əlavə et

### Phase 2. Dynamic builder və müqayisə

- [ ] dynamic report builder ekranını qur
- [ ] source / metric / grouping whitelist-i yaz
- [ ] comparative report ekranını qur
- [ ] year-over-year müqayisələri əlavə et
- [ ] structure comparison əlavə et
- [ ] saved presets əlavə et
- [ ] chart panel komponentlərini yaz
- [ ] chart export / print uyğunluğu əlavə et

### Phase 3. Konsolidə və rəhbərlik paketi

- [ ] rüblük konsolidə hesabat
- [ ] illik konsolidə hesabat
- [ ] rəhbərlik executive dashboard
- [ ] cross-module combined KPI
- [ ] department scorecard
- [ ] queue-based heavy export center

### Phase 4. Advanced maturity

- [ ] scheduled report generation
- [ ] email / notification-based report delivery
- [ ] server-side PDF engine
- [ ] deeper snapshot strategy
- [ ] anomaly / trend insight layer

---

## 17. UX to-do

- [ ] reports dashboard üçün ayrıca vizual istiqamət seç
- [ ] standart report kartlarını vahid dizayn dilinə sal
- [ ] chart komponentləri üçün reusable card pattern qur
- [ ] filter panelini bütün report ekranlarında eyni saxla
- [ ] empty state və no-data state-ləri ayrıca dizayn et
- [ ] export progress və file-ready notification göstər

---

## 18. Test plan

### Unit tests

- [ ] metric formatter
- [ ] filter normalization
- [ ] grouping builder
- [ ] comparison period resolver
- [ ] export payload builder

### Feature tests

- [ ] headcount report render
- [ ] attendance report render
- [ ] training report render
- [ ] performance report render
- [ ] dynamic builder filter scenario
- [ ] export permission checks

### Performance tests

- [ ] `reports:query-budget`
- [ ] `reports:render-benchmark`
- [ ] böyük filter kombinasiyaları üçün regression

### Smoke checks

- [ ] standard report açılır
- [ ] filter dəyişir
- [ ] chart görünür
- [ ] export başlayır
- [ ] PDF print görünüşü işləyir

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

