# Attendance (Mesai Takibi) Modulu — Addım-addım istifadə kılavuzu

Bu sənəd HRM layihəsində Attendance modulunu **sıfırdan istifadə etməyi** izah edir.
Məqsəd: texniki olmayan istifadəçi də bu sənədlə işləyib sistemi düzgün idarə edə bilsin.

---

## 0) Attendance nə edir?

Attendance modulu 3 əsas işi görür:

1. İşə giriş-çıxış məlumatını toplayır (kart/device və ya manual giriş).
2. Günlük hesablayır (iş saatı, gecikmə, erkən çıxış, overtime, yoxluq).
3. Ay sonu yekunlaşdırır (puantaj, bordro export, lock/unlock).

Səhifə: `/attendance`

---

## 1) Kimlər istifadə edir (rol əsaslı)

Attendance-də əsas icazələr:

- `attendance.view` → modulu görmək
- `attendance.manual.write` → manual giriş əlavə etmək
- `attendance.manual.approve` → manual giriş təsdiqləmək/rədd etmək
- `attendance.exceptions.resolve` → exception həll etmək
- `attendance.overtime.approve` → overtime təsdiqləmək/rədd etmək
- `attendance.month.manage` → ayı close/unlock etmək
- `attendance.export` → bordro export almaq

Əgər tab görünmürsə, adətən səbəb həmin icazənin olmamasıdır.

---

## 2) Səhifə strukturu (tab-lar)

`/attendance` içində bu tab-lar var:

1. **Puantaj xülasə** (overview KPI)
2. **Günlük monitor**
3. **Puantaj grid**
4. **Exceptions inbox**
5. **Overtime board**
6. **Month close**
7. **Manual entries**

Yuxarıda il/ay selector var. Tab-ların çoxu bu il/aya görə işləyir.

---

## 3) İlk dəfə qurulum (Admin/HR üçün)

Bu addımlar bir dəfə düzgün edilməlidir.

### 3.1. Attendance settings yoxla

Attendance setting daxilində ən vacib konfiqurasiyalar:

- timezone (məs: `Asia/Baku`)
- default shift
- overtime policy (`by_approval` və s.)
- rounding policy / rounding step
- günlük limitlər (late/early threshold kimi siyasətlər)

### 3.2. Shift-ləri yarat

Məsələn:

- Normal: 09:00-18:00
- Gecə növbəsi: 22:00-06:00 (cross-day)

Vacib: night shift doğru işləsin deyə shift intervalı düzgün verilməlidir.

### 3.3. Shift assignment et

İşçilərə (tabel_no) hansı periodda hansı shift tətbiq olunduğunu assignment ilə ver.

### 3.4. Calendar doldur

Ay üzrə gün tipi:

- workday
- holiday
- weekend
- special

Attendance hesablamasında calendar prioritetdir.

### 3.5. Leave/Vacation/Business trip inteqrasiyası

Bu modullar aktiv olduqda Attendance onları override kimi nəzərə alır:

- məzuniyyətdədirsə absence kimi sayılmır
- ezamiyyətdədirsə xüsusi status kimi düşür

---

## 4) Məlumat necə gəlir? (2 rejim)

Attendance 2 rejimi dəstəkləyir.

### Rejim A — Device/API ilə avtomatik

1. Device və ya xarici sistem punch-ları API-yə göndərir.
2. `attendance_raw_punches`-ə immutable raw data düşür.
3. `attendance:punches:process` komandası normalize + pair + ledger hesablayır.
4. Nəticə `attendance_daily_ledgers` və summary-lərə yazılır.

### Rejim B — Manual giriş

1. HR `Manual entries` tab-da işçi + gün + saat məlumatı daxil edir.
2. Entry əvvəlcə pending ola bilər (workflow-a görə).
3. Approve/reject-dən sonra ledger yenidən hesablanır.

Nəticə: Hər iki rejim eyni ledger modelinə düşür.

---

## 5) Gündəlik iş axını (operativ)

### 5.1. Günlük monitor tab

Burada bugünkü vəziyyəti izləyirsən:

- kim gəlib (`present`)
- kim gecikib (`late`)
- kim gəlməyib (`absent`)
- işlənən saatlar

İstifadə: gün ərzində operativ nəzarət.

### 5.2. Exceptions inbox tab

Tipik exception-lar:

- `missing_in`
- `missing_out`
- `unmatched_punch`

HR burada səbəb əlavə edib resolve edir.

### 5.3. Overtime board tab

Pending overtime request-lər görünür.

- Approve
- Reject

Approve/reject olduqdan sonra ledger yenidən hesablanır.

### 5.4. Manual entries tab

Device olmayan və ya düzəliş lazım olan hallarda manual entry buradan idarə olunur.

---

## 6) Puantaj və ay sonu iş axını

### 6.1. Puantaj grid

Griddə:

- sütunlar: ayın günləri (1..31)
- sətirlər: işçilər
- hüceyrə: həmin günün saat/məlumatı
- sağda/sətirdə toplamlar

Bu ekran operativ yoxlama və düzəliş üçün əsas görünüşdür.

### 6.2. Month close

Ay sonu mərhələsi:

1. `Close` et (period bağlanır)
2. Snapshot yarat (monthly summary)
3. Bordro export (XLSX/CSV)

Lock olandan sonra həmin ay üçün edit guard aktiv olur.

### 6.3. Unlock (yalnız icazəli)

Səhv düzəlişi lazımdırsa unlock edilə bilər, amma bu auditə düşür.

---

## 7) Bordro export (XLSX + CSV)

Month close tab-da export düymələri var:

- XLSX export
- CSV export

CSV formatı `config/attendance.php` ilə idarə olunur:

- delimiter
- enclosure
- line ending
- BOM
- encoding

---

## 8) Audit və izlenebilirlik

Attendance-də bu əməliyyatlar audit olunur:

- manual entry add/update/approve/reject
- overtime approve/reject
- month close/unlock/snapshot

Məqsəd: kim, nə vaxt, nəyi dəyişib — tam izlənə bilsin.

---

## 9) Scheduler (avtomatik fon işləri)

Scheduler `app/Console/Kernel.php` içindədir.

### 9.1. Punch processing schedule

- Command: `attendance:punches:process`
- Env:
  - `ATTENDANCE_PROCESS_SCHEDULE_ENABLED`
  - `ATTENDANCE_PROCESS_SCHEDULE_EVERY_MINUTES`

### 9.2. Monthly snapshot schedule

- Command: `attendance:monthly-snapshot --previous-month [--lock]`
- Env:
  - `ATTENDANCE_SNAPSHOT_SCHEDULE_ENABLED`
  - `ATTENDANCE_SNAPSHOT_SCHEDULE_DAY`
  - `ATTENDANCE_SNAPSHOT_SCHEDULE_AT`
  - `ATTENDANCE_SNAPSHOT_SCHEDULE_LOCK`

### 9.3. Query budget observability schedule

- Command: `attendance:query-budget --json --allow-empty`
- Env:
  - `ATTENDANCE_REPORTS_ENABLED`
  - `ATTENDANCE_REPORT_DAILY_AT`
  - `ATTENDANCE_REPORT_WEEKLY_DAY`
  - `ATTENDANCE_REPORT_WEEKLY_AT`
  - `ATTENDANCE_REPORT_APPEND_OUTPUT`
  - `ATTENDANCE_REPORT_OUTPUT_FILE`

---

## 10) Performans nəzarəti

### 10.1. Query budget

Komanda:

- `php artisan attendance:query-budget --json --allow-empty`

Yoxladığı axınlar:

1. overview build
2. daily monitor load
3. puantaj grid load

### 10.2. Cache

Overview cache key nümunəsi:

- `attendance:{org}:{year}:{month}:{structure}`

İnvalidation trigger-lər:

- punch process
- recalculate
- month close/unlock/snapshot

### 10.3. Pre-aggregation

`attendance_daily_structure_summaries` cədvəli overview üçün yükü azaldır.

---

## 11) Komandalar (əməliyyat üçün qısa siyahı)

### 11.1. Punch process

```bash
php artisan attendance:punches:process
```

Date range/tabel/structure üçün:

```bash
php artisan attendance:recalculate --from=2026-03-01 --to=2026-03-31 --structure=12
php artisan attendance:recalculate --from=2026-03-01 --to=2026-03-31 --tabel=COMP-26-000123
```

### 11.2. Monthly snapshot

```bash
php artisan attendance:monthly-snapshot --year=2026 --month=2
php artisan attendance:monthly-snapshot --year=2026 --month=2 --lock
php artisan attendance:monthly-snapshot --previous-month
```

### 11.3. Query budget

```bash
php artisan attendance:query-budget --json --allow-empty
```

### 11.4. CI gate

```bash
composer ci:attendance-gate
```

---

## 12) Tipik problemlər və həll

### Problem: Günlük monitor boş gəlir

Yoxla:

1. il/ay seçimi doğrudurmu?
2. structure filter çox dar deyil?
3. punch process işləyibmi?

Həll:

```bash
php artisan attendance:punches:process
```

### Problem: Griddə hüceyrə gözlənilən kimi deyil

Yoxla:

1. shift assignment düzgün perioddadır?
2. calendar həmin gün holiday/workday doğrudur?
3. leave/vacation/business trip override var?

Həll:

```bash
php artisan attendance:recalculate --from=YYYY-MM-DD --to=YYYY-MM-DD --tabel=...
```

### Problem: Month close sonrası edit olmur

Bu normaldır. Ay lock olub.

- Lazımdırsa icazəli user unlock etməlidir.

### Problem: Overtime təsdiqlənib, amma nəticə dəyişməyib

Yoxla:

1. request status həqiqətən approved oldumu?
2. period lock deyil?

Lazım olsa recalculate et.

---

## 13) “Gündəlik HR operatoru” üçün qısa workflow

1. `/attendance` aç.
2. Günlük monitor tab-da kritik hallara bax.
3. Exceptions inbox-da açıq problemləri həll et.
4. Manual entry tələb olunsa `manual` tab-dan daxil et.
5. Overtime board-da pending request-ləri qərara bağla.
6. Ay sonu `month-close` tab-da close + snapshot + export et.

---

## 14) “Yeni qurum üçün ilkin aktivasiya” checklist

1. Attendance permission-ları rola verildi.
2. Settings daxil edildi.
3. Shift-lər yaradıldı.
4. Shift assignment-lar verildi.
5. Calendar işləndi.
6. Leave/Vacation/Business trip override test edildi.
7. Punch process scheduler açıldı.
8. Query budget scheduler açıldı.
9. Month close test + export test edildi.
10. Audit log yoxlandı.

---

## 15) Qeyd

- Bu modulda əsas identifikator `tabel_no`-dur.
- Hesablama məntiqi service qatında saxlanılır, UI yalnız istifadəçi qarşılıqlı əlaqə qatıdır.
- Qayda dəyişəndə ən təhlükəsiz yol: date-range recalculate + query-budget yoxlaması + snapshot refresh.
