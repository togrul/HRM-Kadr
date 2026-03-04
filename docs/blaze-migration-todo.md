# Livewire Blaze Migration TODO (Deferred Plan)

## Status update (2026-03-04)
- Full migration məqsədi bu mərhələdə dayandırıldı.
- Proje **hybrid/stable** modeldə bağlandı.
- Final əməliyyat qaydası və checklist:
  - `docs/blaze-final-checklist.md`

## Objective
Adopt Blaze gradually to improve render performance for presentational Blade components without breaking existing Livewire-heavy workflows.

## Important Scope Note
- Blaze migration is a **UI render optimization**.
- It does **not** fix business logic issues, query load, N+1, template mapping bugs, or DOCX generation quality directly.

## Phase 0 - Readiness
- [ ] Pin current baseline:
  - [ ] page render timings (Orders list, Add/Edit modal, Set Type UI Config modal),
  - [ ] memory usage snapshots,
  - [ ] Lighthouse / browser perf markers for key screens.
- [ ] Identify candidate components:
  - [ ] mostly static/presentational components first (`x-button`, badges, table wrappers, cards, nav items),
  - [ ] exclude stateful/dynamic Livewire blocks initially.
- [ ] Add visual regression checklist for top 10 screens.

## Phase 1 - Safe Pilot
- [ ] Install and configure Blaze in local/dev only.
- [ ] Enable Blaze for a very small component subset:
  - [ ] shared UI atoms only (buttons/badges/icons),
  - [ ] no form state or metadata editors yet.
- [ ] Compare before/after:
  - [ ] render duration,
  - [ ] DOM parity,
  - [ ] interaction parity.
- [ ] Roll back immediately if hydration/slot incompatibility appears.

## Phase 2 - Expand to Layout Components
- [ ] Migrate low-risk layout components:
  - [ ] card shells,
  - [ ] table chrome wrappers,
  - [ ] static sidebars/headers.
- [ ] Keep Livewire interactive modules outside Blaze path until stable.
- [ ] Validate slot-heavy nested component behavior.

## Phase 3 - Selective Advanced Usage
- [ ] Evaluate whether complex components benefit:
  - [ ] only migrate if measurable gain exists,
  - [ ] avoid premature migration for dynamic form builders.
- [ ] Add fallback switches per component group.
- [ ] Document team guidelines:
  - [ ] when to use Blaze,
  - [ ] when to stay plain Blade/Livewire.

## Guardrails
- [ ] Feature-flag Blaze usage by environment.
- [ ] Keep hard rollback path (`config` toggle).
- [ ] Do not mix migration with major functional refactors in same PR.

## Done Criteria
- [ ] No visual regressions on core HRM screens.
- [ ] No Livewire event/hydration regressions.
- [ ] Measurable render improvement on selected pages.
- [ ] Written team conventions for future components.
