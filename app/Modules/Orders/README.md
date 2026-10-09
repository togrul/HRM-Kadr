# Orders Module

Livewire components, routes, and views for order CRUD and templates.

- Namespace: `App\Modules\Orders\Livewire`
- Views namespace: `orders::`
- Routes: `app/Modules/Orders/Routes/web.php`
- Provider: `App\Modules\Orders\Providers\OrdersServiceProvider` (loads routes/views, registers Livewire aliases).

## Documentation

- `[Orders Module Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/orders-module-guide.md)`
- `[Orders User Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/orders-user-guide.md)`
- `[Orders Admin Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/orders-admin-guide.md)`
- `[Orders Approval Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/orders-approval-guide.md)`
- `[Orders Ops / Commands Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/orders-ops-commands-guide.md)`

## Order types and their effects

The standard catalogue lives in `App\Console\Commands\SeedOrderWordTemplatesCommand::catalogue()`; the effects in
`Infrastructure/Document/Effects/OrderEffectCatalog`. Each effect applies on approval and reverses on cancel/revert,
keeping what it needs to undo itself in the order snapshot (`effect_state`).

| Effect | Order types | Apply / reverse |
|---|---|---|
| `paid_absence` | `herbi_toplanti`, `donor_gunu`, `secki_komissiyasi`, `mulki_mudafie` | approved leave of the type's own kind (puantaj code HT/DG/SK/MM) via `Leaves\Contracts\OrderAbsenceRecorder` / removed |
| `disciplinary` | `intizam_tenbehi` | `personnel_punishments` row expiring after the term in Settings (12 months default); `personnel:lift-expired-sanctions` (daily) stamps `lifted_at` / row removed |
| `salary_change` | `emek_haqqi_deyisme` | new active compensation from the effective date via `OrderCompensationSync` / removed, previous one restored |
| `substitution` | `evezetme` | `employee_substitutions` record (Compensation) with extra pay % or amount / removed; payroll pays it (see below) |
| `vacation_recall` | `mezuniyyetden_geri_cagirma` | current leave ends the day before the recall date, unused days back to the annual balance / restored |
| `vacation_compensation` | `istifade_olunmamis_mezuniyyet_kompensasiyasi` | days taken off the annual balance (optional amount → payroll one-off) / given back |
| `non_working_day_work` | `qeyri_is_gunu_ise_celb` | approved overtime request (source `order`, `compensation` double_pay/day_off) for the day via `Attendance\Contracts\OrderRestDayWork` / removed; payroll pays double_pay days (see below) |
| `order_cancellation` | `emrin_legvi` | cancels the chosen approved order of the same employee (its effect reverses) / re-approves it |
| `social_leave`, `award` | `usaga_qulluq_mezuniyyeti`, `fexri_ferman` (no amount) | existing effects |
| `none` | `hevale`, `mezuniyyetin_kecirilmesi`, `qisaldilmis_is_vaxti` | document only |

Existing installs: `2026_10_09_130000_register_more_order_word_templates` adds the new codes;
`2026_10_09_140000_attach_effects_to_standard_order_word_templates` (`StandardOrderEffectUpgrader`) moves the four
formerly document-only types onto their effect only while they are unedited (edited ones are logged and kept).

### How payroll pays them

`Payroll\Application\Services\OrderEarningsService` reads both facts at calculation time (regular runs only) through
`Attendance\Contracts\PayrollRestDayWork` and `Compensation\Contracts\SubstitutionRegister`, so a recalculation follows
the orders and a revoked order's pay disappears on the next calculation. Both lines are taxable and social-insurable.

- `rest_day_work`: double_pay days only — base salary × worked minutes × 2 ÷ the employee's month norm minutes
  (hourly rate = base ÷ norm hours). A day named by several orders is paid once; ordinary (non-order) overtime is not paid
  by this line. day_off days add no money; the choice stays on the overtime request (`compensation`) and in the feed.
- `substitution`: percent × the substituting employee's own base salary (per the order text "vəzifə maaşının … faizi"),
  or the stored fixed monthly amount on older rows, × calendar days of the substitution in the month ÷ days in the month.

Each order-derived line stores the records it was built from (`payslip_lines.sources`); locking refuses a regular run
whose order facts changed since its calculation (`order_earnings_changed`: reopen and recalculate), like the one-off guard.
Locked runs are never changed; an order that lands in an already locked month reaches the employee through
`RetroService` (net difference on the next regular run). An order revoked after its month was locked is recovered the
same way as a `retro_recovery` deduction on the next regular run, capped at that payslip's net; only the part caused by
order records that no longer stand is recovered — other negative differences (e.g. a retroactive pay cut) are still not
clawed back. When finance owns payroll the run refuses to compute and the
same facts travel in the `attendance.month` (`rest_day_work`) and `compensation` (`substitutions`) feeds — see
`docs/integration-finance.md`.
