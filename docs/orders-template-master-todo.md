# Orders Template Master TODO (Step-by-Step)

Bu fayl Orders Template + Components sistemini “enterprise, metadata-driven, easy onboarding” səviyyəsinə çıxarmaq üçün **single source of truth** olaraq istifadə olunur.

## Rule of execution
- Hər task tamamlandıqca checkbox işarələ.
- Hər böyük taskdan sonra: smoke test + qısa qeyd.
- “No-legacy mode”a keçid yalnız bütün P1/P2 bitəndən sonra.

---

## Phase A — Reliability & Invariants (P1)
- [ ] A1. Single active version invariant sərtləşdir:
  - [ ] DB-level guard strategiyası
  - [ ] service-level reconcile fallback
  - [ ] publish/rollback sonrası invariant assert
- [ ] A2. Publish/Rollback/Delete əməliyyatları transaction + lock ilə 100% deterministik
- [ ] A3. “active metadata template version not found” edge-case tam bağlansın
- [ ] A4. Generate metadata deterministic:
  - [ ] silinən dynamic field metadata-dan da silinsin
  - [ ] duplicate mapping/field yaranmasın

## Phase B — Print Correctness (P1)
- [ ] B1. Placeholder coverage publish gate:
  - [ ] template-də var, mapping-də yoxdur -> block
  - [ ] mapping var, template-də yoxdur -> warn/block policy
- [ ] B2. DOCX smoke checks:
  - [ ] XML integrity
  - [ ] required placeholder replacement
- [ ] B3. Server-side dry-run / preview render endpoint

## Phase C — Metadata-driven Form Engine (P1/P2)
- [ ] C1. required/input/lookup config runtime-da 1:1 enforce
- [ ] C2. Field config editor:
  - [ ] input type
  - [ ] model/searchField/selectedName
  - [ ] helper text/placeholder/default
- [ ] C3. Section blocks:
  - [ ] enable/order/title
  - [ ] row_fields, scalar_fields, extra_sections
- [ ] C4. Mapping editor finalize:
  - [ ] add/edit/delete
  - [ ] scope row/scalar
  - [ ] sort order

## Phase D — Onboarding UX (P2)
- [x] D1. Templates səhifəsinə onboarding wizard entrypoint
- [ ] D2. Wizard Step 1: Create/Select template set
- [ ] D3. Wizard Step 2: Upload docx + checksum/version info
- [ ] D4. Wizard Step 3: Detect placeholders + generate metadata
- [ ] D5. Wizard Step 4: Coverage check + quick fix links
- [ ] D6. Wizard Step 5: Preview + publish

## Phase E — Legacy Cleanup / No-Legacy Mode (P2/P3)
- [ ] E1. Legacy fallback matrix sənədləşdir
- [ ] E2. Type-by-type fallback disable plan
- [ ] E3. No-legacy feature flag + gradual rollout
- [ ] E4. Legacy branch/code removal after rollout freeze

## Phase F — Governance, Ops, Performance (P2/P3)
- [ ] F1. Role/permission matrix (draft/publish/rollback/delete)
- [ ] F2. Audit log diff readability (what changed)
- [ ] F3. Metrics:
  - [ ] generation error rate
  - [ ] slow render percentile
  - [ ] version usage
- [ ] F4. N+1 and query budget checks for Add/Edit/Print flows

---

## Current Sprint (Now)
- [x] S1. Master checklist yaradıldı.
- [x] S2. Onboarding wizard entrypoint əlavə edildi.
- [ ] S3. Wizard Step 1 (Create/Select set) implement.
- [ ] S4. Wizard Step 2 (Upload + detect) implement.
- [ ] S5. Wizard Step 3 (coverage + publish) implement.

