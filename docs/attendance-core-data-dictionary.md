# Attendance Core Tables - Data Dictionary

Bu sənəd `attendance_core` migration-da yaradılan cədvəllərin və kolonların nə iş gördüyünü izah edir.

Qeyd:

- Personnel bağlantıları **id ilə yox**, `personnels.tabel_no` ilə qurulub.
- Bunun səbəbi layihədə personnel domain-də stabil unique açarın `tabel_no` olmasıdır.

## 1) `attendance_settings`

Sistem səviyyəsində attendance qaydaları.

- `scope_type`: qaydanın səviyyəsi (`global`, `structure`, `department` və s.).
- `scope_id`: scope üçün konkret id (məs: structure id). `global` olduqda null ola bilər.
- `timezone`: hesablamaların time zone-u.
- `default_shift_id`: default shift (shift assignment olmayan əməkdaşlar üçün fallback).
- `late_grace_minutes`: gecikmə tolerantlığı.
- `early_leave_grace_minutes`: erkən çıxış tolerantlığı.
- `rounding_policy`: dəqiqə yuvarlaqlaşdırma metodu (`none`, `nearest`, `floor`, `ceil` kimi).
- `rounding_step_minutes`: yuvarlaqlaşdırma addımı (məs: 5 dəqiqə).
- `overtime_policy`: overtime qaydası (`by_approval`, `auto` və s.).
- `is_active`: aktiv konfiqurasiya.
- `created_by`, `updated_by`: dəyişiklik audit user-ları.

## 2) `attendance_shifts`

İş növbəsi tərifi.

- `name`: növbə adı.
- `start_time`, `end_time`: planlanan giriş/çıxış saatı.
- `break_minutes`: standart fasilə.
- `is_night_shift`: gecə növbəsi flag-i.
- `in_flex_before_minutes`, `in_flex_after_minutes`: giriş üçün icazəli fleks pəncərə.
- `out_flex_before_minutes`, `out_flex_after_minutes`: çıxış üçün icazəli fleks pəncərə.
- `is_active`: istifadə edilən növbə olub-olmaması.
- `created_by`, `updated_by`: audit user-ları.
- `deleted_at`: soft delete.

## 3) `attendance_shift_assignments`

Əməkdaşın tarix aralığında hansı shift-də işləyəcəyini saxlayır.

- `tabel_no`: əməkdaşın unique tabel nömrəsi (FK -> `personnels.tabel_no`).
- `shift_id`: bağlı shift.
- `effective_from`, `effective_to`: assignment period.
- `assignment_source`: təyin etmə mənbəyi (`manual`, `import`, `rule`).
- `is_active`: assignment aktiv flag.
- `created_by`, `updated_by`: audit.

## 4) `attendance_calendars`

İş/tatil bayram təqvimi.

- `date`: gün.
- `day_type`: gün tipi (`workday`, `weekend`, `holiday`, `special`).
- `name`: bayram/xüsusi gün adı.
- `is_paid`: ödənişli gün statusu.
- `scope_type`, `scope_id`: təqvimin tətbiq olunduğu scope.
- `created_by`, `updated_by`: audit.

## 5) `attendance_raw_punches`

Cihaz/API/manual-dan gələn xam giriş-çıxış hadisələri (immutable source).

- `tabel_no`: punch düşən əməkdaş.
- `punched_at`: punch timestamp.
- `direction`: `in`, `out`, `break_in`, `break_out`.
- `source`: mənbə (`device`, `api`, `manual`).
- `device_ref`: cihaz identifikatoru.
- `external_id`: xarici sistemdə unik id (dedup üçün).
- `payload_hash`: hash əsaslı dedup açarı.
- `meta`: əlavə payload.
- `is_processed`: ledger-a işlənib/işlənməyib.
- `processed_at`: işlənmə zamanı.

## 6) `attendance_manual_entries`

Manual daxil edilən attendance düzəlişləri.

- `tabel_no`: əməkdaş.
- `date`: hansı gün üçün manual girişdir.
- `worked_minutes`: manual işlənmiş dəqiqə.
- `overtime_minutes`: manual overtime dəqiqəsi.
- `absence_code`: yoxluq kodu (`leave`, `sick`, `absent` və s.).
- `reason`: əsaslandırma.
- `approval_status`: `pending`, `approved`, `rejected`.
- `entered_by`: daxil edən user.
- `approved_by`, `approved_at`: təsdiqləyən user və tarix.
- `deleted_at`: soft delete.

## 7) `attendance_daily_ledgers`

Attendance-in əsas "hesablanmış nəticə" cədvəli (source of truth for payroll/report).

- `tabel_no`: əməkdaş.
- `date`: gün.
- `shift_id`: həmin gün üçün tətbiq olunan shift.
- `scheduled_minutes`: planlanan dəqiqə.
- `worked_minutes`: faktiki işlənən dəqiqə.
- `break_minutes`: faktiki/standart fasilə.
- `overtime_minutes`: hesablanmış overtime.
- `late_minutes`: gecikmə dəqiqəsi.
- `early_leave_minutes`: erkən çıxış dəqiqəsi.
- `attendance_status`: ümumi status (`present`, `absent`, `leave`, `holiday` və s.).
- `absence_code`: detallı yoxluq tipi.
- `source_summary`: hesablamanın mənbə xülasəsi (`system`, `manual_override`).
- `is_locked`: ay bağlandıqdan sonra edit qoruması.
- `approved_by`, `approved_at`: təsdiq məlumatı.
- `meta`: hesablamaya aid texniki context.

## 8) `attendance_exceptions`

Anomaliya və konflikt inbox-u.

- `tabel_no`: əməkdaş.
- `date`: problemli gün.
- `type`: exception tipi (`missing_in`, `missing_out`, `conflict`, `anomaly`).
- `status`: `open`, `resolved`, `ignored`.
- `message`: sistem izahı.
- `resolution_note`: həll qeydi.
- `resolved_by`, `resolved_at`: kim və nə vaxt bağladı.

## 9) `attendance_overtime_requests`

Overtime təsdiq workflow cədvəli.

- `tabel_no`: əməkdaş.
- `date`: overtime günü.
- `requested_minutes`: istənilən overtime.
- `approved_minutes`: təsdiqlənən overtime.
- `status`: `pending`, `approved`, `rejected`.
- `reason`: əsaslandırma.
- `requested_by`: sorğunu açan user.
- `approved_by`, `approved_at`: təsdiqləyən məlumatı.
- `deleted_at`: soft delete.

## 10) `attendance_monthly_summaries`

Aylıq yekun snapshot (hesabat və bordro üçün sürətli oxu).

- `tabel_no`: əməkdaş.
- `year`, `month`: period.
- `total_scheduled_minutes`: aylıq plan dəqiqəsi.
- `total_worked_minutes`: aylıq faktiki dəqiqə.
- `total_overtime_minutes`: aylıq overtime.
- `total_absence_minutes`: aylıq yoxluq dəqiqəsi.
- `total_workdays`: period iş günü.
- `total_present_days`: iştirak günləri.
- `total_absence_days`: yoxluq günləri.
- `is_locked`: period lock.
- `calculated_at`: son hesablanma zamanı.

## 11) `attendance_daily_structure_summaries`

Gün + struktur səviyyəsində pre-aggregated summary cədvəli (dashboard KPI/read-heavy flow üçün).

- `date`: gün.
- `structure_id`: struktur id (nullable ola bilər).
- `ledger_rows`: həmin gün+struktur üçün ledger sətr sayı.
- `scheduled_days`: schedule olan sətr/gün sayı.
- `present_days`: iştirak (işlənmiş/manual present və s.) sətr sayı.
- `absence_days`: `absent/manual_absence` sətr sayı.
- `compliant_days`: gecikmə/erkən çıxış olmayan compliant sətr sayı.
- `scheduled_minutes_sum`: planlanan dəqiqələrin cəmi.
- `worked_minutes_sum`: işlənmiş dəqiqələrin cəmi.
- `overtime_minutes_sum`: overtime dəqiqələrinin cəmi.
- `late_minutes_sum`: gecikmə dəqiqələrinin cəmi.
- `early_leave_minutes_sum`: erkən çıxış dəqiqələrinin cəmi.

Bu cədvəl pipeline və month snapshot prosesindən rebuild olunur; read-lər üçün `attendance_daily_ledgers` üzərindən hər dəfə ağır aggregate işlətməmək üçün istifadə edilir.
