# Orders Template Engine Expert Roadmap

## 1) Goal
Build an enterprise-grade, schema-driven order generation platform that can scale to 40-50+ order types with:
- stable DOCX output (no XML corruption),
- predictable performance,
- minimal per-type custom code,
- versioned templates and fields,
- strong testability.

This document defines:
1. what must change from current state,
2. target architecture,
3. phased migration plan,
4. acceptance criteria.

## Execution Status (current)
- [x] DOCX render hardening moved into a dedicated renderer service (`OrderTemplateRenderer`).
- [x] Orders print flow switched to renderer service (orchestration-only component).
- [x] XML-validity smoke test added for renderer output.
- [x] Blade-specific replacement smoke tests added (`default`, `vacation`, `business-trips`).
- [x] DB-backed schema/version tables added (Phase 1 foundation).
- [x] `TemplateRegistry` service + cache/fallback path added.
- [x] Backfill command added (`orders:templates:backfill`).
- [x] Metadata-driven print payload flow added (`OrderPrintPayloadFactory` + metadata/legacy payload builders).
- [x] Generation telemetry writes to `order_generation_logs` during render.
- [x] Readiness report command added (`orders:templates:readiness`).
- [x] Phase 3 bootstrap: metadata field catalog service + default form field-key resolution hook.
- [x] Phase 3 continued: metadata-driven row group/layout support (`row_groups`) for Add/Edit form rendering.
- [x] Template metadata admin mutations now produce audit trail records (`order_template_version_audits`).
- [x] `order_logs` now stores template snapshot/version context for reproducible historical print.
- [x] Template version lifecycle service added (draft from base, publish, rollback) and exposed in Set Type UI.
- [x] Metadata-only guard mode added (config-driven) for order types that must not fallback to legacy mappings.
- [x] Template registry/readiness and print payload flow optimized to reduce duplicate lookups.
- [ ] Metadata-driven form+render end-to-end (Phase 2-3).

---

## 2) Current State (Summary)
- Current system is hybrid:
  - DOCX templates + placeholder replacement,
  - per-blade logic in code (`default`, `vacation`, `business-trips`),
  - dynamic fields are partially modeled but still code-driven.
- Strengths:
  - placeholders and dynamic fields already exist,
  - modularized Orders module is in place.
- Main scaling bottlenecks:
  - branching logic (`switch/match`) grows with each new type,
  - transform rules are coupled to runtime code,
  - no formal template/field schema versioning,
  - difficult to onboard many new types safely.

---

## 3) What Must Change Now (Immediate Improvements)

## 3.1 Stabilize the current pipeline (short-term hardening)
- Keep DOCX rendering "safe replace only" (no raw XML injection).
- Centralize placeholder sanitization and escaping in one service.
- Ensure all render paths use a single TemplateRenderer service.
- Keep template path resolution unified and validated.

## 3.2 Remove logic spread across UI/traits
- Move field mapping and transformations into dedicated services:
  - `OrderFieldValueResolver`
  - `OrderFieldTransformPipeline`
  - `OrderTemplateRenderer`
- Make Livewire components orchestration-only:
  - gather input,
  - call pipeline,
  - show result/errors.

## 3.3 Introduce metadata-first behavior
- Start storing per-order-type metadata in DB (or JSON seed) instead of new code branches.
- Convert existing 3 blades into metadata-backed definitions first.

---

## 4) Target Expert Architecture

## 4.1 Core Components
1. `TemplateRegistry`
- Resolves active template version by order type.
- Provides immutable template metadata for rendering.

2. `OrderTypeSchema`
- Formal field definitions:
  - key, label, type, required,
  - data source, validation rules,
  - transform chain, display hints.

3. `FieldTransformPipeline`
- Deterministic transform stages, for example:
  - normalize,
  - suffix/grammar,
  - date formatting,
  - casing,
  - fallback/default.

4. `PlaceholderMappingEngine`
- Maps schema field keys -> DOCX placeholders.
- Supports list/repeat sections without custom per-type code.

5. `TemplateRenderer`
- Single output gateway:
  - input payload + schema + template version
  - output file
  - strict escaping and diagnostics.

6. `DocumentGenerationJob`
- Async generation with queue for heavy loads.
- Saves generation logs and error payloads for support/debugging.

## 4.2 Data Model (recommended)
Use these tables:
- `order_template_sets`
  - logical template family per order type.
- `order_template_versions`
  - file path, status, published_at, checksum, is_active.
- `order_template_fields`
  - field_key, field_type, required, ui_config, data_source, transform_config, validation_config.
- `order_template_mappings`
  - placeholder -> field_key mapping,
  - supports block/list scope.
- `order_template_version_audits`
  - who changed what and when.
- `order_generation_logs`
  - request id, template version, duration, status, error detail.

Optional:
- `order_template_previews` for cached preview artifacts.

## 4.3 Design Principles
- Version everything (template + schema + mappings).
- Never modify old versions in place.
- New order uses active version; old order reopens with historical version.
- No per-type renderer duplication.

---

## 5) Migration Plan (Phased)

## Phase 0 - Guardrails (1-2 days)
- Add render smoke tests for existing 3 blades.
- Add XML validity checks for generated DOCX in test pipeline.
- Add structured logging around generation steps.

Exit criteria:
- Existing flows produce valid DOCX and pass smoke tests.

## Phase 1 - Schema Foundation (3-5 days)
- Introduce DB schema for template versions/fields/mappings.
- Build `TemplateRegistry` and metadata loader.
- Backfill current order types (`default`, `vacation`, `business-trips`) as metadata records.

Exit criteria:
- Metadata read path works for all current types.

## Phase 2 - Render Engine Refactor (4-7 days)
- Build single `TemplateRenderer` service.
- Move transformations to pipeline classes.
- Remove branching from Livewire where possible; keep only orchestration.

Exit criteria:
- All current types rendered via same renderer service.

## Phase 3 - Dynamic Form Builder (5-8 days)
- Generate add/edit form inputs from `order_template_fields`.
- Field validation generated from schema.
- Dropdown/search/structure/personnel data sources plugged through adapters.

Exit criteria:
- Add/Edit forms for existing types are schema-driven.

## Phase 4 - Template Mapping Studio (optional, advanced)
- Build admin tool for mapping placeholders to fields.
- Preview pane (rendered output preview), not full Word editor.
- Publish workflow with version activation.

Exit criteria:
- New order type can be onboarded with minimal/no new backend code.

---

## 6) Performance Targets
- P95 generation time:
  - sync small docs: < 400 ms
  - async queued docs: completed < 2 s under normal load
- P95 add/edit modal render:
  - < 250 ms server-side for normal payload
- DB:
  - no N+1 in add/edit/render pipeline
  - schema metadata cached (Redis/local cache) with explicit invalidation

---

## 7) Testing Strategy
- Unit tests:
  - suffix/transform pipeline,
  - placeholder mapping,
  - schema rule resolution.
- Integration tests:
  - add/edit -> generate document per order type.
- Snapshot/approval tests:
  - rendered content key paragraphs.
- Regression tests:
  - reopen old order with old template version.

---

## 8) Risks and Mitigation
- Risk: too much refactor at once.
  - Mitigation: phased rollout, feature flag per order type.
- Risk: template mismatch in production.
  - Mitigation: publish workflow + checksum + smoke preview before activation.
- Risk: performance drop during transition.
  - Mitigation: queue generation + cache metadata + profiling budget checks.

---

## 9) Suggested Backlog (Start Here)
1. Create DB migrations for template versioning tables.
2. Implement `TemplateRegistry` + cache.
3. Build `OrderFieldTransformPipeline` with existing suffix/date logic.
4. Wrap current render flow into `TemplateRenderer`.
5. Switch current 3 order types to metadata-backed configuration.
6. Add generation logs and XML validity tests.

---

## 10) Definition of Done (for "Expert Structure")
- New order type can be added by:
  - uploading template version,
  - defining fields/mappings in metadata,
  - activating version,
  - without adding new renderer branch code.
- Output DOCX is valid and reproducible.
- Add/Edit UI and validation are schema-driven.
- Performance and logs meet target budgets.
