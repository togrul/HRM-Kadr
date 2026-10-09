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

## Multi-participant orders (çoxşəxsli əmr)

A Word template can be flagged multi-participant in the designer (only for effects that make sense per person:
`OrderEffectCatalog::supportsParticipants()` — business trip, paid absence, award, the leave effects, disciplinary).
Each manual variable then has a scope: shared (`order`), per participant (`participant`) or shared with a per-person
override (`override`). `participant.*` automatic variables (same values as `employee.*` plus `participant.n`) resolve
per person.

- **Data:** `order_participants` (order, personnel, position, own `fields`, `effect_state`). The snapshot keeps the
  first participant as `personnel_id`; `order_log_personnels` links every participant (list visibility, employee card).
- **Document:** every table row holding a participant-only token is repeated per participant; alternatively the
  paragraphs between `[İştirakçılar]` and `[/İştirakçılar]` are repeated (`ParticipantTemplateProcessor`).
- **Approval:** all participants are checked first (dates, active, no overlap) — the first who fails refuses the whole
  approval, named; then the effect runs per participant in one transaction with that person's effect state kept on
  their row. Reversal undoes all. One outbox event per participant (`docs/integration-finance.md`).
- The standard `ezamiyyet` is multi-participant (participants table, trip kind, funding source);
  `2026_10_10_120000_make_standard_business_trip_template_multi_participant` (`StandardBusinessTripTemplateUpgrader`)
  replaces an installed copy only while it is unedited and re-keys the orders already issued on it.
