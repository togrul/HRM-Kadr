# Orders Template Master TODO (Step-by-Step)

Bu fayl Orders Template + Components sistemini “enterprise, metadata-driven, easy onboarding” səviyyəsinə çıxarmaq üçün **single source of truth** olaraq istifadə olunur.

## Rule of execution
- Hər task tamamlandıqca checkbox işarələ.
- Hər böyük taskdan sonra: smoke test + qısa qeyd.
- “No-legacy mode”a keçid yalnız bütün P1/P2 bitəndən sonra.

---

## Phase A — Reliability & Invariants (P1)
- [x] A1. Single active version invariant sərtləşdir:
  - [x] DB-level guard strategiyası
  - [x] service-level reconcile fallback
  - [x] publish/rollback sonrası invariant assert
- [x] A2. Publish/Rollback/Delete əməliyyatları transaction + lock ilə 100% deterministik
- [x] A3. “active metadata template version not found” edge-case tam bağlansın
- [x] A4. Generate metadata deterministic:
  - [x] silinən dynamic field metadata-dan da silinsin
  - [x] duplicate mapping/field yaranmasın

## Phase B — Print Correctness (P1)
- [x] B1. Placeholder coverage publish gate:
  - [x] template-də var, mapping-də yoxdur -> block
  - [x] mapping var, template-də yoxdur -> warn/block policy
- [x] B2. DOCX smoke checks:
  - [x] XML integrity
  - [x] required placeholder replacement
- [x] B3. Server-side dry-run / preview render endpoint

## Phase C — Metadata-driven Form Engine (P1/P2)
- [x] C1. required/input/lookup config runtime-da 1:1 enforce
- [x] C2. Field config editor:
  - [x] input type
  - [x] model/searchField/selectedName
  - [x] helper text/placeholder/default
- [x] C3. Section blocks:
  - [x] enable/order/title
  - [x] row_fields, scalar_fields, extra_sections
- [x] C4. Mapping editor finalize:
  - [x] add/edit/delete
  - [x] scope row/scalar
  - [x] sort order

## Phase D — Onboarding UX (P2)
- [x] D1. Templates səhifəsinə onboarding wizard entrypoint
- [x] D2. Wizard Step 1: Create/Select template set
- [x] D3. Wizard Step 2: Upload docx + checksum/version info
- [x] D4. Wizard Step 3: Detect placeholders + generate metadata
- [x] D5. Wizard Step 4: Coverage check + quick fix links
- [x] D6. Wizard Step 5: Preview + publish

## Phase E — Legacy Cleanup / No-Legacy Mode (P2/P3)
- [x] E1. Legacy fallback matrix sənədləşdir
- [x] E2. Type-by-type fallback disable plan
- [x] E3. No-legacy feature flag + gradual rollout
- [x] E4. Legacy branch/code removal after rollout freeze

## Phase F — Governance, Ops, Performance (P2/P3)
- [x] F1. Role/permission matrix (draft/publish/rollback/delete)
- [x] F2. Audit log diff readability (what changed)
- [x] F3. Metrics:
  - [x] generation error rate
  - [x] slow render percentile
  - [x] version usage
- [x] F4. N+1 and query budget checks for Add/Edit/Print flows
- [x] F5. CI quality gate (metrics + query-budget) pipeline integration
- [x] F6. Daily/weekly observability reporting (log/slack/telegram)
- [x] F7. No-legacy freeze audit command and runbook

---

## Current Sprint (Now)
- [x] S1. Master checklist yaradıldı.
- [x] S2. Onboarding wizard entrypoint əlavə edildi.
- [x] S3. Wizard Step 1 (Create/Select set) implement.
- [x] S4. Wizard Step 2 (Upload + detect) implement.
- [x] S5. Wizard Step 3 (coverage + publish) implement.
