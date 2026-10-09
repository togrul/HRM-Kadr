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
| `substitution` | `evezetme` | `employee_substitutions` record (Compensation) with extra pay % or amount / removed |
| `vacation_recall` | `mezuniyyetden_geri_cagirma` | current leave ends the day before the recall date, unused days back to the annual balance / restored |
| `vacation_compensation` | `istifade_olunmamis_mezuniyyet_kompensasiyasi` | days taken off the annual balance (optional amount → payroll one-off) / given back |
| `non_working_day_work` | `qeyri_is_gunu_ise_celb` | approved overtime request (source `order`) for the day via `Attendance\Contracts\OrderRestDayWork` / removed |
| `order_cancellation` | `emrin_legvi` | cancels the chosen approved order of the same employee (its effect reverses) / re-approves it |
| `social_leave`, `award` | `usaga_qulluq_mezuniyyeti`, `fexri_ferman` (no amount) | existing effects |
| `none` | `hevale`, `mezuniyyetin_kecirilmesi`, `qisaldilmis_is_vaxti` | document only |

Existing installs: `2026_10_09_130000_register_more_order_word_templates` adds the new codes;
`2026_10_09_140000_attach_effects_to_standard_order_word_templates` (`StandardOrderEffectUpgrader`) moves the four
formerly document-only types onto their effect only while they are unedited (edited ones are logged and kept).
