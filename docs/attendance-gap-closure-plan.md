# Attendance Gap Closure Plan

Date: 2026-03-09

This document closes the two functional gaps identified in the Attendance module:

1. Work regime / calendar operational management UI
2. Leave subtype-aware attendance registration

## 1. Work Regime / Calendar Management

## Current state

- Data model exists:
  - `attendance_settings`
  - `attendance_shifts`
  - `attendance_shift_assignments`
  - `attendance_calendars`
- Runtime consumption exists:
  - global + structure-scoped day type resolution
  - shift assignment history
  - daily ledger calculation uses shift + calendar context
- Missing part:
  - no dedicated operator/admin UI for calendar CRUD
  - no explicit structure-level work regime management screen

## Goal

Support both:

- general work regime
- special work regime

with history, scope, and operator-safe CRUD.

## Proposed product surface

Add a new Attendance tab:

- `calendar-regimes`

This tab should include three blocks:

1. Global calendar rules
   - mark date as `workday`, `weekend`, `holiday`
   - set name
   - set paid/unpaid
2. Structure-scoped calendar overrides
   - choose structure
   - choose date
   - override day type for that structure only
3. Regime history
   - list who changed what
   - show before/after
   - show effective date and scope

## Required implementation

### A. Livewire/UI

- Create `App\Modules\Attendance\Livewire\CalendarRegimes`
- Add new blade:
  - `app/Modules/Attendance/Resources/views/livewire/attendance/calendar-regimes.blade.php`
- Add new tab entry in dashboard
- Add translation catalogs:
  - `attendance::calendar_regimes`

### B. Authorization

Add explicit permission:

- `attendance.calendars.manage`

Dashboard tab visibility must follow this permission.

### C. Service layer

Add:

- `AttendanceCalendarManagementService`

Responsibilities:

- create/update/delete calendar row
- validate unique scope/date rule
- prevent invalid day type values
- log audit entries

### D. Audit

Every change should be recorded through attendance audit logger:

- calendar.created
- calendar.updated
- calendar.deleted

### E. Acceptance

- operator can define a global holiday
- operator can override a date for one structure
- recalculation reflects the override in ledger/puantaj
- audit row is visible for each change

## 2. Leave Subtype-Aware Attendance Registration

## Current state

- Attendance currently resolves leave override generically as `leave`
- Vacation and business trip are separate override types
- Leave subtype details are not reflected into ledger/tabel cells

## Expected business rule

When a leave request is approved:

- attendance must automatically reflect it
- correct days must be marked
- hour/minute effect must match the actual approved interval
- subtype must remain distinguishable in attendance output

## Required domain change

The attendance module must stop treating all approved leaves as one generic override.

## Proposed target model

### A. Override payload

Extend leave override from:

- `type = leave`

to:

- `type = leave`
- `leave_type_id`
- `leave_code`
- `starts_at`
- `ends_at`
- `is_partial_day`

### B. Ledger payload

`attendance_daily_ledgers` should preserve subtype details in `meta` and produce a meaningful `absence_code`.

Recommended:

- sickness leave -> `SICK`
- regular leave -> `LEAVE`
- unpaid leave -> `UNPAID`
- study leave -> `STUDY`

Final code map should come from leave type metadata, not hardcoded titles.

### C. Partial-day handling

Current date-range override logic is day-based.
This is not enough if leave spans only part of a day.

We need:

- resolve overlap between approved leave interval and shift window
- reduce scheduled/workable minutes accordingly
- avoid marking full-day absence when only partial leave exists

### D. Trigger points

Attendance must update when:

1. leave is approved
2. approved leave is edited
3. approved leave is deleted/cancelled
4. leave date range changes

The safest implementation is:

- dispatch attendance recalculation for affected `tabel_no + date range`

## Required implementation

### A. Leave metadata source

Pick one of these models:

1. Add attendance-facing code fields to leave types
2. Create a mapping service from leave type to attendance absence code

Preferred:

- explicit leave-type metadata field such as `attendance_code`

### B. Context resolver

Update `AttendanceDayContextResolverService`:

- join approved leave rows with leave type metadata
- emit subtype-aware override payload
- preserve actual datetime interval

### C. Calculator

Update `AttendanceDailyLedgerCalculatorService`:

- support full-day leave
- support partial-day leave
- compute scheduled/worked/absence minutes correctly
- write subtype-aware `absence_code`

### D. Recalculation wiring

On leave approval lifecycle:

- queue attendance recalculation for impacted range

### E. UI impact

Puantaj and daily monitor should show subtype-aware leave states.

Examples:

- `SICK`
- `LEAVE`
- `UNPAID`

instead of one generic leave bucket.

## Recommended execution order

1. Add leave-type attendance metadata
2. Make context resolver subtype-aware
3. Make calculator partial-day aware
4. Trigger recalculation on leave lifecycle
5. Update puantaj/daily monitor labels
6. Add tests

## Test checklist

- approved full-day sick leave marks correct dates
- approved partial-day leave reduces only relevant minutes
- cancelled leave restores attendance after recalculation
- vacation/business trip precedence remains correct
- manager views show subtype-aware leave codes
