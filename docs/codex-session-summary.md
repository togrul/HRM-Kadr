# Codex Session Summary

## Context
- Project: HRM Livewire personnel wizard refactor.
- Goal: Reduce `PersonnelCrud` bloat by delegating state management to dedicated Livewire form objects and remove obsolete dropdown/search scaffolding.

## Key Changes
1. **Dropdown Extraction**
   - Created `PersonnelDropdownOptions` trait; moved computed option methods, caching helpers, label lookups out of `PersonnelCrud`.

2. **Form-Centric Mutations**
   - Added add/remove helpers to forms (`AwardsPunishmentsForm`, `KinshipForm`, `MiscellaneousForm`, `ServiceHistoryForm`, `LaborActivityForm`, `EducationForm`, `DocumentForm`) and updated `PersonnelCrud` methods to delegate to them.

3. **Form Payload Adoption**
   - `PersonalInformationForm` is the single source of truth for Step 1. Blade bindings (e.g., discrediting information) and validation reference `personalForm`.
   - `AddPersonnel`/`EditPersonnel` build persistence payloads exclusively from form objects (personal + document payloads) instead of legacy arrays.

4. **Legacy Array Removal**
   - Removed `$personnel`, `$personnel_extra`, `$document`, `$service_cards*`, `$passports*` from `PersonnelCrud`, plus all sync helpers (`syncArraysFromDocumentForm`, etc.).
   - Navigation validation and helper methods now inspect form payloads (via `documentPayload()` / `personalForm`), ensuring no duplicated state.

5. **Education State Migration**
   - Removed `$education`, `$extra_education`, `$extra_education_list` from `PersonnelCrud`; duration calculations now read from `educationForm`.
   - `EducationForm` exposes `educationForPersistence()` / `extraEducationsForPersistence()` so `AddPersonnel`/`EditPersonnel` persist data without touching trait arrays.
   - Navigation validation + dropdown helpers look only at `educationForm` state; `hasExtraEducation` simply mirrors the form boolean.
   - Dropped the unused `hydrateEducationFormFromArrays()` hook; mounts now rely on the form instance directly.

6. **Personal Form-Only State**
   - Removed the legacy `$personnel` / `$personnel_extra` arrays and the associated sync helpers from `PersonnelCrud`; Livewire bindings now update `PersonalInformationForm` directly.
   - Validation, dropdowns, and lookup-loading use `personalForm` data (including `hasDisability`) instead of shadow properties, reducing duplicate state.

7. **Labor Step Cleanup**
   - Removed `$laborActivityListFingerprint` and the `syncArraysFromLaborActivityForm` helper; Step 4 derives seniority directly from `LaborActivityForm`.
   - `AddPersonnel`/`EditPersonnel` no longer call labor syncs; `calculateSeniority()` runs whenever labor activities change or load.

8. **Step 5–7 Form Simplification**
   - Dropped unused `fillFromArrays()` helpers from `ServiceHistoryForm`, `AwardsPunishmentsForm`, and `KinshipForm`; these steps hydrate solely from models now.
   - Keeps the API surface minimal and prevents future regressions back to component-side array hydration.

9. **Validation & Dropdown Updates**
   - `PersonnelValidationTrait` and dropdown helpers read from form data; document + education validation checks use the respective forms.

10. **Shared Relation Persistence**
   - Added helper methods in `RelationCrudTrait` (`createPersonnelRelations` / `updatePersonnelRelations`) so `AddPersonnel` and `EditPersonnel` share the same transactional relation logic instead of duplicating dozens of calls.

11. **Step 5–8 Conditional Validation**
   - Navigation validation for Military, Awards/Punishments, Kinship, and Misc steps now runs only when there is draft data in the corresponding forms, so populated lists no longer block moving between steps.

12. **Relation Service Extraction**
   - Introduced `PersonnelRelationsService`; the relation trait now delegates create/update flows to this service, unlocking reuse outside Livewire and making the persistence logic testable in isolation.

13. **Education Step Sync Removal**
   - Removed `syncArraysFromEducationForm` in favour of binding the UI directly to `EducationForm`. Extra-education toggles now write to the form itself, and `EducationDurationService` encapsulates the duration math + memoisation so the component only deals with pure data.

14. **Service-Level Tests**
   - Added focused unit coverage for `PersonnelRelationsService` (create/update reconciliation) and `EducationDurationService` caching behaviour to lock in the new abstractions.
15. **Labor Step Preview**
 - `calculateSeniority()` now includes the in-progress labor entry while you are editing it, so the totals in Step 4 update in real time without needing to add/remove rows first.
16. **Test Harness Improvements**
 - PHPUnit runs against in-memory SQLite with FK constraints disabled; migrations that rely on `change()` now self-skip on SQLite, and the base `UserFactory` hashes passwords via the configured driver so observer logic no longer explodes during tests.
17. **Personnel Listing Optimisations**
  - `AllPersonnel`’s query builder now eager-loads only the localized columns it needs, sorts by structure/position names (via correlated subqueries), and caches the accessible structure id list plus the positions dropdown.
  - The view stores `$personnels`/`$status` locally and uses the new `activeVacation`/`activeBusinessTrip` accessors so Livewire doesn’t re-run expensive relations per row; dropdown lookups for ranks/rank-reasons read locale-specific columns and leverage cached labels to avoid duplicate queries.
18. **Personnel Model Filtering Hardening**
  - Added typed accessors for `activeVacation/activeBusinessTrip` so Livewire consumers know the relations must be eager-loaded, and ensured the `age` accessor safely handles missing birthdates.
  - `scopeFilter` now ignores only truly empty filter payloads, preserves “0” values, normalises date/number ranges via helpers, and reuses strict `in_array` checks to avoid accidental column matches. This keeps range filters deterministic and prevents surprise N+1 queries when filters contain partially-filled arrays.
19. **OrderLog Safeguards**
  - Wrapped `handleDeletion()` in a DB transaction, extracted rollback helpers for EMR personnel, vacation orders, and business trips, and added guards so missing staff schedules or vacations no longer throw errors during cleanup.
  - Reused the filter helpers to keep `OrderLog::scopeFilter` consistent with other models (trims order numbers, normalises date ranges, ignores empty payloads) and added typed Builder hints for IDE support.
20. **Candidate Status Lookups**
  - Added a reusable `LoadsAppealStatuses` concern that caches locale-specific appeal statuses for six hours and wired it into `CandidateList` plus the `CandidateCrud` trait, eliminating duplicate queries on every Livewire render.
  - `CandidateList` now caches accessible structure IDs per mount (instead of resolving the service per query) and keeps status filters/exports fast even when the page re-renders frequently.
21. **Staff Schedule Ancestors**
  - Cached accessible structure ids during `Staffs` mount so the component stops resolving `StructureService` on every render, and expanded `Structure::withRecursive()` to accept a flag that controls whether the `accessible()` macro is applied. The staff schedule now opts out of that filter (so ancestor chains load fully without leaks), while every other consumer keeps their permission-aware recursion.
  - Marked the cached IDs as a locked public property so Livewire hydrates them across requests; without that change the list emptied whenever the page toggled between “all” and “vacancies”.
22. **Select Dropdown Migration (Phase 1)**
  - Upgraded `<x-ui.select-dropdown>` to support Livewire callbacks/dispatch hooks (placeholder payloads, per-option payloads) so it can replace `<x-select-list>` without losing `setData()` side effects.
  - Migrated the shared partials (`includes/component-action.blade.php`, `includes/order-action.blade.php`, `includes/rank-action.blade.php`, `includes/template-action.blade.php`, `includes/candidate-action.blade.php`, `includes/informations/contracts.blade.php`) to the new dropdown so order/candidate/rank workflows, templates, and contract forms all benefit from the modern component.
23. **Candidate Form Simplification**
  - The candidate create/edit form now mirrors Step 1: `structure_id` and `status_id` are bound directly via `<x-ui.select-dropdown>` (with search support for structures), validation targets the scalar IDs, and edit flows hydrate them without the legacy `{id,name}` proxy arrays.
24. **Component Form Dropdowns**
  - Component CRUD now binds `order_type_id`/`rank_id` directly to scalars and renders both selectors with `<x-ui.select-dropdown>` (orders keep their search box). Edit flows hydrate the scalar IDs, and validation plus persistence run on those fields without running `setData` helpers.
25. **Contract Step Dropdown**
  - Personnel “Contracts” step switched to the same dropdown pattern: `contracts.rank_id` is a scalar bound to `<x-ui.select-dropdown>` fed by a computed `contractRankOptions()` list, so even archived ranks show up when editing while active ranks stay cached via `DropdownConstructTrait`.
26. **Order Template Dropdown**
  - Order CRUD now binds `order.order_type_id` directly to the selected template ID, exposes `templateOptions()` via `DropdownConstructTrait`, and the form uses `<x-ui.select-dropdown>` (with order search input) instead of `<x-select-list>`. Component pickers also use the new dropdown while still dispatching `componentSelected`.
27. **Rank Category Dropdowns**
  - Rank add/edit flows now load category options via computed lists and bind `rank_category_id` as a scalar; the shared `includes/rank-action` partial uses `<x-ui.select-dropdown>` just like the candidate form.
28. **Template Category Dropdown**
  - Template CRUD switched to scalar `order_category_id`, serves options via `orderCategoryOptions()` (with search support) from `DropdownConstructTrait`, and the Blade partial now uses `<x-ui.select-dropdown>` instead of legacy select lists.
29. **Order Component Sync**
  - Order CRUD no longer relies on `componentSelected` events for building `$selectedComponents`; each render hydrates the dynamic-field list directly from the cached component lookup, so the new dropdown bindings instantly reveal the correct fields (and stale selections are cleared) without extra roundtrips or JavaScript hooks.
30. **Order Template Validation**
  - Updated `OrderValidationTrait` to validate the scalar `order.order_type_id` introduced by the new dropdown; the template selector now passes validation once chosen instead of tripping on the old `{id,name}` structure.
31. **Order Lookup Memoization**
  - `resolveLookupCollections()` now memoizes template/component/personnel/structure/position lookups (keyed by search text & selected template) so Livewire renders reuse cached collections instead of hitting the DB each frame; the existing static cache still handles locale-invariant lists like ranks/main structures.
  - Validation happens once per submission (`runOrderValidation()`), letting `prepareToCrud()` focus solely on shaping the payload and preventing redundant DB queries when complex dynamic rules are in play.
32. **Order Persistence Services**
  - Introduced `OrderComponentPersister` and `OrderPersonnelPersister`, lifted `attachComponents()`/`formatOrderPersonnels()` logic into them, and wired Add/Edit Order flows to call the services (including the vacancy import branch). Component and personnel sync are now isolated, reusable, and easier to profile/extend.
33. **Business Trip Filters**
  - BusinessTrips Livewire filter dropdowns now use the shared `<x-ui.select-dropdown>` with cached/computed option lists (structures + order types via `DropdownConstructTrait`); the screen no longer runs full-table queries on every render and the filter payload stores scalar IDs that map directly into `PersonnelBusinessTrip::scopeFilter`.
34. **OrderCrud Structure Cache & Lean Lookups**
  - `setStructure()` builds labels from an in-memory structure index (`structureLineageCache`) instead of hitting `structures` twice per click, and the candidate vacancy diff only runs for IG orders. Personnel lookups are skipped entirely for non-default blades, trimming the heaviest query during business-trip/vacation workflows.
35. **Order Dynamic Inputs**
  - Dynamic component fields (`personnel_id`, `rank_id`, `structure_main_id`, `position_id`, `transportation`) now bind scalar IDs directly to `<x-ui.select-dropdown>` options. Validation rules, vacancy diffs, and import services were updated to expect scalar payloads, `SelectListTrait` was dropped from `OrderCrud`, and attribute hydration rebuilds the human labels via cached option maps so no `{id,name}` arrays or `setData()` hooks remain.
36. **Order Dropdown Stabilization**
  - Reintroduced a single `updated()` interceptor in `OrderCrud` that funnels every `componentForms.*` mutation through the same helper (so component pickers, personnel dropdowns, and date fields always run, even if Livewire skips `updatedComponents()`), while keeping the scalar-ID payloads and `setStructure()` reset logic intact—no more entangle crashes when dependent dropdowns change.
  - `OrderValidationTrait` now validates `componentForms.*.structure_id` directly, and `CheckVacancyService` extracts scalar IDs + cached structure/position labels before counting vacancies, so vacancy warnings still print human-readable text even though the Livewire payloads no longer ship `{id,name}` arrays.
  - Added a reusable `componentFieldValue()` helper plus updated the shared `dynamic-input`/`radio-tree` blades to read scalar IDs, keeping the UI labels/highlights in sync without hitting the old “Cannot access offset of type …” errors when a dependent dropdown clears a relation.
  - `<x-ui.select-dropdown>` accepts an optional `selectedLabel`, and `dynamic-input` now feeds it via `componentFieldLabel`; this keeps the chosen personnel/structure label visible even when the lookup list deliberately excludes already-picked rows, matching the legacy SelectList behaviour.
37. **Order Components Cache & Validation Merge**
  - Cached the component definitions returned by `OrderLookupService` so `componentSelected` reuses the already-loaded `dynamic_fields` instead of hitting the database for each selection; the cache survives across renders and only refreshes when the selected template changes.
  - Reused that cache when hydrating `$selectedComponents` during renders, so the dynamic-field lists are rebuilt entirely from in-memory data (no per-row collection scans) while edit flows still hydrate correctly.
  - `runOrderValidation()` now merges the active rule buckets and performs a single `validate()` call (skipping empty-string rules), shaving redundant validation passes off every submit.
  - Vacancy calculations now run only for candidate (IG) orders; we build the comparison payload lazily and skip the diff entirely for regular orders, trimming one more heavy array operation off every submit.
  - Candidate import now expects pure scalar IDs for `structure_id` / `position_id`, aligning it with the refactored dropdown state so we don’t have to juggle `{id,name}` arrays when generating personnels from vacancies.
  - Livewire component state keeps only scalar IDs for dropdown-backed fields; attribute payloads and edit flows rebuild labels via the cached lookup maps, so we no longer push `{id,name}` arrays through validation or vacancy logic.
  - Extracted dropdown/structure label caching into `Orders\DropdownLabelCache` so OrderCrud just consumes the trait; the shared helper now owns the structure lineage cache, label registration, and suffix logic, making future dropdown refactors isolated.
  - Attribute persistence now lives in `OrderAttributePersister`, and vacation/business-trip cleanup moved to `VacationCleanupService`, so OrderCrud delegates both concerns instead of instantiating ad-hoc services + inline deletions.
  - Added `Orders\VacancyDiffService` to encapsulate the candidate diff logic; OrderCrud now simply hands it the current/original arrays and reuses the result when calling `CheckVacancyService`, keeping the trait focused on orchestration.
  - `BladeDataPreparation` now relies on `PersonnelResolver` to fetch tabel numbers for default orders, removing the inline `Personnel::find` inside the trait and giving us a single, cache-friendly place to adjust how personnel IDs are resolved.
  - Component row/search state lives in dedicated helpers: `ManagesOrderComponents` centralises component/selection arrays, and `OrderSearchForm` backs all template/personnel/structure search inputs (Blade bindings now use `search.*`). This keeps the Livewire trait smaller and avoids duplicating array reset logic.
  - Component rows are now stored under `componentForms.*.*`, so Livewire validation/bindings all target the same structured namespace (order templates + dynamic inputs reference `componentForms` everywhere).
 - `CheckVacancyService` now consumes pre-normalised scalar payloads (structure/position IDs plus labels) so it no longer queries `structures`/`positions` per request; OrderCrud normalises the diff payload only for IG/default orders before invoking the service.
 - Order add/edit components now bind to `OrderForm` (Livewire form object) instead of juggling a raw `$order` array; the form seeds defaults, hydrates from existing logs, and exposes a `payload()` helper so the trait/components stay slimmer and validation references (`orderForm.*`) are explicit.
38. **Selected Personnel Form**
  - Introduced `SelectedPersonnelForm` so the business-trip/vacation flows no longer juggle the nested `$selected_personnel_list` array by hand—Livewire now exposes `selectedPersonnel.rows` for the row-specific payload and `selectedPersonnel.personnels` for the flat tabel-no list.
  - `addToList`/`removeFromList`, vacancy normalization, and the business-trip/vacation partials were updated to mutate/bind this form directly, giving us helpers to add/remove rows, flatten them for persistence, and keep duplicate prevention working without manual array surgery.
  - Order edit flows hydrate the form from stored attributes/personnels, `OrderCollectionListsService` receives the flat tabel array, and `CheckVacancyService`/persisters simply read from the form, reducing the risk of desyncs.
39. **Order Component Traits & Validation**
  - Split the monolithic `ManagesOrderComponents` into `HandlesComponentRows` (row lifecycle, coded toggles) and `HandlesPersonnelSelections` (search/add/remove personnel, blade-specific payload shaping). The root trait now just mixes in these focused helpers, making the responsibilities clearer and future extensions safer.
  - Blade-specific validation now lives in dedicated helpers inside `OrderValidationTrait`; instead of building giant rule arrays full of empty strings, `mainValidationRules()` and per-blade dynamic rule methods return only the rules that matter. This trims per-submit validation work and makes the constraints easier to reason about.
40. **Lookup Caching (Phase 2)**
  - `OrderLookupService` now caches rank/main-structure lists, structure trees (per accessible structure set), component lists per template, position lists, and template lists per order ID when no search query is present—cutting duplicate queries when editing orders or toggling filters. Searches still hit the DB, but steady-state renders reuse cached collections.
  - `getStatusesProperty()` now caches localized order status lists (both add/edit and all-orders list) for 10 minutes so the same table isn’t queried twice per render.
41. **Vacancy Lookup Slimming**
  - `CheckVacancyService` now queries `staff_schedules` only for the structure/position pairs present in the diff payload (instead of pulling the entire table). That drops memory usage and DB time whenever IG orders trigger the vacancy check, while non-default blades continue to skip the diff entirely.
42. **Order Render Builder**
  - Extracted the dataset preparation logic from `OrderCrud::render()` into `OrderRenderPayloadBuilder`, so the Livewire trait focuses on state/event handling while the builder assembles lookup collections + blade-specific lists (and registers dropdown labels) in one place.
43. **Dynamic Input Cleanup**
  - `<x-dynamic-input>` now receives the resolved field label/value from the parent view; the component no longer runs `method_exists` checks on every render (and the default template computes the label/value only once per field), trimming CPU time for large order forms.
44. **Structure Selector Component**
  - The radio-tree logic (word suffixes, Alpine toggles, structure traversal) moved into a dedicated `<x-structure-selector>` component, keeping `<x-dynamic-input>` lean while still supporting coded labels and nested lists.
45. **Global Caching (Menus & Notifications)**
  - Header menus are cached for 10 minutes, and the notifications dropdown caches both the unread count and the recent list per user (invalidated when notifications are marked read) to eliminate repeated queries every render.
46. **Admin Awards Dropdown Refresh**
  - Replaced the legacy `<x-select-list>` in the awards CRUD with `<x-ui.select-dropdown>` backed by `DropdownConstructTrait`, so the form now binds `form.award_type_id` as a scalar, supports inline search, and automatically keeps the selected type in the option list.
  - `Awards` now seeds clean defaults (id/name/type/is_foreign), loads award-type options through a cached builder, and no longer relies on `SelectListTrait`/`setData`, cutting duplicated Livewire reactivity work on every render.
47. **Staff Schedule Auto Fill Balancing**
  - `StaffCrud` now listens to `staff.*` updates the modern way (Livewire `updatedStaff`) so selecting a structure and/or position immediately recalculates the `filled` count from active personnels and keeps `vacant = total - filled`.
  - Global structure changes reset row-level positions, hide the position dropdown when pointing to a top-level structure, and reuse the new helpers to fetch nested structure IDs before counting personnels, matching how the old SelectList-driven flow behaved.
48. **Select Dropdown Migration (Phase 2)**
  - Admin CRUD screens for punishments, cities, positions, and structures dropped `SelectListTrait` in favour of `DropdownConstructTrait`, so their forms now bind scalar IDs, expose cached option builders with inline search, and hydrate defaults without juggling `{id,name}` arrays.
  - Personnel vacation editors (monthly reserved-date picker) and the vacations list filters now render `<x-ui.select-dropdown>` with Livewire bindings; month/structure filters store plain integers, reuse the shared options helpers, and the `PersonnelVacation` scope accepts either scalars or the legacy `{id}` payload for backwards compatibility.
  - With no legacy usages left, the `<x-select-list>` components were removed entirely and `SelectListTrait` now only exposes the `modifyArray()` helper for payload casting.
49. **Structure Cache Invalidation**
  - `StructureObserver` now clears every dropdown/view cache that depends on the tree (`staff:structures`, `candidate:structures`, `businessTrips:structures`, and the order lookup main-structure cache) whenever a structure is created/updated/deleted, so new entries show up immediately without a manual `cache:clear`.
  - Fixed the model attribute to use Laravel’s `#[ObservedBy(StructureObserver::class)]` syntax _and_ registered the observer inside `AppServiceProvider` to ensure it’s wired even on older Laravel builds; previously the observer never fired so caches stayed stale.

## Next Ideas
- Verify Step 5–8 UX once more (manual or automated) to ensure draft detection still covers every branch.
- Layer an end-to-end flow test over the add/edit wizard so the relation service is exercised with real Livewire payloads.
- Monitor the new education duration service in production—if recalculation volume is still high, consider pushing the cache behind Laravel’s cache driver rather than in-memory.
