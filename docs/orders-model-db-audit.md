# Orders Model + DB Audit (Current State)

This audit maps the current Orders architecture into:
- **Keep (required now)**
- **Keep (transition/legacy, still required)**
- **Cleanup candidates (safe first)**
- **Do not drop yet (high-risk)**

Date: 2026-02-26
Applied cleanup status:
- `app/Models/OrderLogComponent.php` removed
- `app/Models/OrderLogPersonnel.php` removed
- `app/Services/Orders/PersonnelResolver.php` removed

---

## 1) Keep (required now)

These are actively used by Add/Edit/List/Print and template lifecycle.

### Core order domain tables
- `orders`
- `order_types`
- `order_categories`
- `order_statuses`
- `components`
- `order_logs`
- `order_log_personnels`
- `order_log_components`
- `order_log_component_attributes`

### Template engine tables
- `order_template_sets`
- `order_template_versions`
- `order_template_fields`
- `order_template_mappings`
- `order_template_version_audits`
- `order_generation_logs`

### Why they are required
- Print pipeline uses `OrderPrintPayloadFactory` + `OrderTemplateRenderer`.
- Add/Edit uses metadata schema from `OrderTemplateFormSchemaService` plus current fallback paths.
- Template UI config lifecycle depends on versions/fields/mappings/audits.
- Historical reproducibility depends on `order_logs.order_template_version_id` + `template_snapshot`.

---

## 2) Keep (transition/legacy, still required)

These are legacy-ish but still part of current runtime behavior.

1. `orders.content`
- Still used as template file path fallback and in registry resolution.

2. `components.dynamic_fields`
- Metadata bootstrap üçün artıq istifadə olunmur.
- Hələ runtime component-level fallback/state üçün istifadə olunur (`OrderInteractionStateService`, `OrderCrud`, `HandlesOrderComponentFieldState`).

3. `order_logs.order_id` together with `order_logs.order_type_id`
- `order_id` is still used in listing/filter/permission flow (for example IG_EMR logic).
- `order_type_id` is used by template engine and print payload selection.

Conclusion: keep both for now; removing one requires a planned migration.

---

## 3) Cleanup candidates (safe first, PHP-level)

These are code artifacts that appear non-essential or risky.

### A) `app/Models/OrderLogPersonnel.php`
- Direct class usage appeared absent in app runtime.
- Status: removed.

### B) `app/Models/OrderLogComponent.php`
- Direct class usage appeared absent; relation logic uses `belongsToMany` via `OrderLog`.
- Status: removed.

### C) `app/Services/Orders/PersonnelResolver.php`
- No runtime references found.
- Status: removed.

---

## 4) Do not drop yet (high-risk now)

Do not remove these yet; they are part of current runtime:
- Docx content generation helpers (`GenerateWordReplaceContent` flow).
- Table pivots: `order_log_personnels`, `order_log_components`, `order_log_component_attributes`.
- `orders.content` and `components.dynamic_fields`.

Dropping these now will break existing order types and historical documents.

---

## 5) Suggested cleanup sequence (professional/safe)

1. Remove dangerous debug hooks from `OrderLogPersonnel` model. (done via model removal)
2. Remove clearly unused service: `PersonnelResolver`. (done)
3. Add CI test that scans for `dd(` in `app/Models` and `app/Services`.
4. Remove unused wrapper model `OrderLogComponent`. (done)
5. Keep DB schema intact until Phase 4 final “no-legacy mode” rollout.

---

## 6) Final answer to “which old models/tables are no longer needed?”

### Likely no longer needed soon (code-level)
- `app/Services/Orders/PersonnelResolver.php` (unused)
- `app/Models/OrderLogComponent.php` (likely unused wrapper model)
- `app/Models/OrderLogPersonnel.php` (unused + risky debug hooks)

### Not removable yet (DB-level)
- No major Orders DB table is safely removable **today** due hybrid runtime.
- Real DB cleanup should start only after legacy fallback removal is completed for all order types.

---

## 7) Why did DB tables remain after model deletion?

Deleting an Eloquent model class does **not** remove its database table automatically.

In this project:
- `order_log_personnels` and `order_log_components` are still used as pivot tables via
  `OrderLog` `belongsToMany(...)` relations.
- So even if wrapper models (`OrderLogPersonnel`, `OrderLogComponent`) are removed,
  the tables must stay until relation strategy is changed and data migration is completed.
