# Attendance Module

Attendance module is responsible for:

1. Time capture (device/API/manual)
2. Daily ledger calculation
3. Overtime and exception workflow
4. Monthly summaries for payroll exports (XLSX/CSV)

Current state:

1. Module provider, route and dashboard are initialized.
2. Core attendance schema is added (foundation phase).
3. Unified attendance page:
   - dashboard route: `/attendance`
   - daily monitor tab: `/attendance?tab=daily-monitor`
   - puantaj tab: `/attendance?tab=puantaj`
   - exceptions tab: `/attendance?tab=exceptions`
   - overtime board tab: `/attendance?tab=overtime`
   - manual entries tab: `/attendance?tab=manual`
   - legacy `/attendance/manual-entries` route redirects to manual tab
4. Phase B started:
   - token-protected punch ingest endpoint: `POST /api/attendance/punches/ingest`
   - `attendance:punches:process` command (sync/queue)
   - `attendance:recalculate` command (date range + structure/tabel scope)
   - `attendance:monthly-snapshot` command/job for payroll summaries
   - punch normalization + in/out pairing + daily ledger calculator
   - shift window (night/cross-day), calendar override, leave/vacation/business trip override
   - policy-driven overtime/rounding (from attendance settings + config fallback)
   - exception sync (missing_in, missing_out, unmatched_punch)
5. Phase D/F implemented (first slice):
   - daily monitor tab
   - puantaj monthly grid
   - exceptions inbox
   - overtime approval board
   - month close / unlock and locked-month edit guard
   - manual override queue with approve/reject workflow
   - full attendance audit trail (manual/overtime/month lock actions)
6. KPI overview implemented:
   - coverage
   - absence rate
   - overtime trend (month-over-month)
   - compliance (schedule adherence)
7. Role-based access scopes are enabled:
   - attendance.view
   - attendance.daily.view / attendance.puantaj.view
   - attendance.manual.view / attendance.overtime.view / attendance.exceptions.view / attendance.month.view
   - attendance.settings.manage / attendance.shifts.manage
   - attendance.manual.write / attendance.manual.approve
   - attendance.overtime.approve
   - attendance.exceptions.resolve
   - attendance.month.manage
   - attendance.export
   - `manage-attendance-settings` and `manage-attendance-shifts` are explicit admin permissions
   - Role matrix seeded: HR Admin / HR Manager / HR Employee / HR Auditor
8. Phase G performance hardening:
   - pre-aggregated daily structure summaries (`attendance_daily_structure_summaries`)
   - overview cache strategy: `attendance:{org}:{year}:{month}:{structure}`
   - query budget command: `attendance:query-budget`
   - architecture guard test for read-heavy Livewire components
9. Remaining roadmap is tracked in `docs/attendance-module-master-todo.md`.
10. Ops runbook is tracked in `docs/attendance-ops.md`.

CSV export settings are configurable from `config/attendance.php`:

- `attendance.exports.payroll.csv.delimiter`
- `attendance.exports.payroll.csv.enclosure`
- `attendance.exports.payroll.csv.line_ending`
- `attendance.exports.payroll.csv.use_bom`
- `attendance.exports.payroll.csv.output_encoding`

Observability schedule settings:

- `attendance.observability.reports.enabled`
- `attendance.observability.reports.daily_at`
- `attendance.observability.reports.weekly_day`
- `attendance.observability.reports.weekly_at`
- `attendance.observability.reports.append_output`
- `attendance.observability.reports.output_file`

Local quality gate:

- `composer ci:attendance-gate`
