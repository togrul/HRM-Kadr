# Attendance Module Master TODO

Bu sənəd Mesai Takibi / Attendance modulunun sıfırdan expert səviyyədə qurulması üçün icra planıdır.

## Operational closure (post-Phase G)

- [x] 1) Acceptance closure (manual smoke + command checklist)
- [ ] 2) CI quality gate-in əsas branch-də stabil təsdiqi
- [x] 3) Scheduler/Ops wiring (process + snapshot + observability)
- [x] 4) Performance tuning/Ops (query budget + cache + pre-aggregation runbook)

## Rule of execution

- [x] Hər fazın sonunda smoke test və migration yoxlanışı et.
- [ ] Domain qaydalarını UI-dan ayır (service/use-case qatında saxla).
- [ ] Raw data immutable olmalıdır; hesablanmış nəticə ayrıca ledger cədvəlində saxlanmalıdır.
- [x] Manual override və approval addımları audit log-a düşməlidir.

## Phase A — Foundation (P1)

- [x] `attendance` modulunu qeydiyyata al (provider + routes + Livewire page).
- [x] Core DB cədvəllərini yarat:
  - [x] `attendance_settings`
  - [x] `attendance_shifts`
  - [x] `attendance_shift_assignments`
  - [x] `attendance_calendars`
  - [x] `attendance_raw_punches`
  - [x] `attendance_manual_entries`
  - [x] `attendance_daily_ledgers`
  - [x] `attendance_exceptions`
  - [x] `attendance_overtime_requests`
  - [x] `attendance_monthly_summaries`
- [x] Yüksək dəyərli index və unique constraint-ləri əlavə et.

## Phase B — Capture Layer (P1)

- [x] Device/API punch ingest endpoint/adapter əlavə et.
- [x] Manual entry ekranı (HR/Manager) əlavə et.
- [x] Duplicate punch detection (`external_id/hash`) əlavə et.
- [x] Punch normalization job-u yarat.

## Phase C — Rule Engine & Ledger (P1/P2)

- [x] Daily ledger calculation service yaz.
- [x] Shift + calendar + leave/vacation/business trip override qaydalarını birləşdir.
- [x] Late/early/overtime hesablamasını policy-driven et.
- [x] Night shift və cross-day handling əlavə et.
- [x] Recalculate job (date range + structure scope) əlavə et.

## Phase D — UI/UX (P2)

- [x] Aylıq puantaj grid (days 1..31 + row/column totals).
- [x] Daily monitor panel (bugün kim gəldi/gəlmədi/gecikdi).
- [x] Exceptions inbox (missing in/out, anomaly, conflict).
- [x] Overtime approval board.
- [x] Month close/lock ekranı.

## Phase E — Payroll & Reporting (P2/P3)

- [x] Bordro export contract (CSV/XLSX format) müəyyən et.
- [x] Monthly summary snapshot job-u yaz.
- [x] KPI dashboard:
  - [x] Coverage
  - [x] Absence rate
  - [x] Overtime trend
  - [x] Compliance (mandatory schedule adherence)

## Phase F — Governance & Security (P2/P3)

- [x] Role-based permissions (HR Admin, Manager, Employee, Auditor).
- [x] Locked month edit guard.
- [x] Manual override approval workflow.
- [x] Full activity/audit logging.
- [x] Role matrix seed (HR Admin/Manager/Employee/Auditor) for attendance scopes.

## Phase G — Performance hardening (P3)

- [x] Heavy queries üçün pre-aggregated summary table.
- [x] Cache strategy (`attendance:{org}:{year}:{month}:{structure}`).
- [x] Query budget command əlavə et.
- [x] Architecture tests (Livewire-də direct model query guard).

## Acceptance Criteria

- [x] Device olan qurumlarda tam avtomatik axın işləyir.
- [x] Device olmayan qurumlarda manual axın eyni ledger modelinə düşür.
- [x] Leave/Vacation/Business Trips attendance ilə ziddiyyət yaratmır.
- [x] Month close sonrası bordroya stabil export alınır.
- [x] Audit trail ilə kim nəyi niyə dəyişdi izlənə bilir.
- [x] Manual flow acceptance test checklist bağlıdır (`tests/Feature/Attendance/AttendanceManualFlowAcceptanceTest.php`).
- [x] Audit trail acceptance test checklist bağlıdır (`tests/Feature/Attendance/AttendanceManualFlowAcceptanceTest.php`).
