# Orders Template Engine - Easy Onboarding TODO

## Target
Yeni `Əmr` və `Arayış` növlərini minimal kod dəyişikliyi ilə əlavə etmək:
- template qurulması sürətli olsun,
- metadata/form avtomatik yaransın,
- Word print stabil və düzgün çıxsın.

## Current State (Short)
- Metadata-driven foundation var.
- Versioning, publish/rollback, UI config editor var.
- Amma “tam no-code onboarding” üçün hələ bir neçə kritik hissə tamamlanmalıdır.

## Priority Backlog

### P1 - Reliability (must-have)
- [ ] Single-active-version invariant DB səviyyəsində qorunsun:
  - [ ] partial unique index/guard strategy (`is_active=1` per set).
- [ ] Publish/Rollback əməliyyatlarına transaction + lock guard tamamlansın.
- [ ] UI config açılışında “active version not found” edge-case tam bağlansın.
- [ ] Generate metadata deterministic olsun:
  - [ ] component field silinibsə metadata-dan da silinsin,
  - [ ] duplicate field/mapping yaranmasın.

### P1 - Print Correctness (must-have)
- [ ] Placeholder coverage validator:
  - [ ] template-də olan placeholder-lar vs mappings fərq reportu.
- [ ] DOCX output smoke test suite:
  - [ ] XML integrity,
  - [ ] required placeholders replaced check.
- [ ] “Render preview” (server-side dry-run) əlavə edilsin.

### P1 - Form Correctness (must-have)
- [ ] `required` metadata -> runtime validation 1:1 işləsin.
- [ ] `input type` + lookup config (`model/searchField/selectedName`) admin UI-dan edit edilə bilsin.
- [ ] Row/component select məntiqi legacy fallback ilə konflikt verməsin.

### P2 - Admin UX (high)
- [ ] Mapping editor tamamlansın:
  - [ ] add/edit/delete,
  - [ ] sort order,
  - [ ] scope (`row/scalar`) izahlı UI.
- [ ] Section blocks editor izahlı edilsin:
  - [ ] key read-only,
  - [ ] `sort` label daha aydın (`order`).
- [ ] Audit paneldə “what changed” diff daha oxunaqlı formatda göstərilsin.

### P2 - New Type Onboarding Flow (high)
- [ ] “Create New Template Set Wizard”:
  - [ ] order type seç,
  - [ ] docx upload,
  - [ ] auto-detect placeholders,
  - [ ] initial mappings generate,
  - [ ] publish draft preview.
- [ ] “Clone from existing set” düyməsi:
  - [ ] fields/mappings/ui config copy.

### P3 - Governance/Operations
- [ ] Permissions:
  - [ ] who can draft,
  - [ ] who can publish/rollback/delete.
- [ ] Monitoring:
  - [ ] generation failure rate,
  - [ ] slow render logs,
  - [ ] template version usage stats.
- [ ] Scheduled health checks:
  - [ ] orphan mappings,
  - [ ] inactive published cleanup candidates.

## Definition of Done (Expert Level)
- [ ] Yeni order type əlavə etmək üçün backenddə yeni conditional branch yazmağa ehtiyac qalmasın.
- [ ] Admin yalnız template + metadata editor ilə tam işlək forma/print qura bilsin.
- [ ] Print output 100% valid DOCX və testlərlə qorunsun.
- [ ] Version lifecycle (draft/publish/rollback/delete) hər zaman deterministik olsun.
