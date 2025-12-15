## Orders Refactor Findings (to revisit)

- **OrderCrud “God object”**: Lookup cache, validation, vacancy diff, render and event handling all live in one trait. Splitting into LookupResolver, VacancyManager, ComponentRow DTO + Validator would aid testability and readability.
- **Validation/prepareToCrud**: `runOrderValidation()` merges main + dynamic rules every action; `resolveVacancyData` runs even when not needed. Move blade-specific rules into small form objects and validate once on submit.
- **Lookup/options**: `resolveLookupCollections` memoizes but still runs `modifyCodedList()` and `syncSelectedComponentsFromLookup()` every render. Update componentDefinitions/selectedComponents only on change. Option lists could return typed DTOs to reduce property proxy overhead.
- **Vacancy diff**: `normalizeVacancyEntries`/diff still deals with mixed array/label payloads. Introduce a `VacancyEntry` DTO with required `personnel_id/component_id/structure_id/position_id` to avoid empty payloads.
- **ImportCandidateToPersonnel**: Hard-coded defaults (mobile/email/pin/addresses/work_norm_id), not transactional. Move defaults to config and wrap creation in a single transaction.
- **OrderConfirmedService**: IG approval not transactional; rank/laborActivity updates can half-apply. Wrap pending/status/rank updates in one transaction; batch StaffScheduleUpdated events. Clarify payload type (candidate ids vs NMZD tabel numbers).
- **Pivot ops**: Add/Edit call attachAssignments for both candidate/non-candidate; validate early when `component_id`/`personnel_id` missing instead of silently skipping.
- **AllOrders query**: mount only does `viewAny`; with/select not narrowed. Consider selecting only used columns/eager loads; large TemplateProcessor work could be queued for big documents.
- **Legacy label cache**: componentFieldLabel/Value still mixes ids/labels. A small SelectValue value-object would simplify and remove `array_key_exists` checks in blades.
