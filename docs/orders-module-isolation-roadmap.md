# Orders Module Isolation Roadmap

This roadmap defines how to move Orders from hybrid modular style to stronger module isolation.

## Current baseline

- Orders works as a module (`app/Modules/Orders`) but still depends on shared `App\Models` and shared services.
- Metadata-driven template engine is active and strict mode is enabled.
- Legacy form/print fallbacks are blocked.

## Phase 1 (started)

Goal: introduce module contracts (ports) and Eloquent adapters without breaking behavior.

Completed:

- Added `OrderTemplateAdmin` contract:
  - `app/Modules/Orders/Domain/Contracts/OrderTemplateAdmin.php`
- Added `OrderTemplateRepository` contract:
  - `app/Modules/Orders/Domain/Contracts/OrderTemplateRepository.php`
- Added Eloquent adapter:
  - `app/Modules/Orders/Infrastructure/Persistence/Eloquent/EloquentOrderTemplateRepository.php`
- Added `OrderTemplateRegistry` contract:
  - `app/Modules/Orders/Domain/Contracts/OrderTemplateRegistry.php`
- Bound contracts in Orders provider:
  - `app/Modules/Orders/Providers/OrdersServiceProvider.php`
- Migrated template admin flow to contract:
  - Add/Edit template Livewire now resolve `OrderTemplateAdmin` contract.
- Template registry usage in core services is now contract-based:
  - Form schema, print payload, snapshot, version lifecycle, metadata sync, onboarding/ui config mutation flow.

## Phase 2

Goal: remove direct model dependency from Livewire/UI layer.

Tasks:

1. Add application use-cases under `app/Modules/Orders/Application/UseCases/*`:
   - CreateTemplate
   - UpdateTemplate
   - CreateTemplateDraftVersion
   - PublishTemplateVersion
   - SaveUiConfig
2. Move transaction boundaries from Livewire traits/components to use-cases.
3. Replace direct `Order::query()` calls in UI with read repositories.

Progress:

- Added `OrderTemplateReadRepository` contract:
  - `app/Modules/Orders/Domain/Contracts/OrderTemplateReadRepository.php`
- Added Eloquent read adapter:
  - `app/Modules/Orders/Infrastructure/Persistence/Eloquent/EloquentOrderTemplateReadRepository.php`
- Bound read contract in provider.
- Migrated direct reads to repository in:
  - `AllTemplates` (list/restore/delete lookup)
  - `SetType` (template load + order type read)
  - `OnboardingWizard` (template/type/version options)
- Added Application use-case for onboarding flows:
  - `app/Modules/Orders/Application/UseCases/Templates/TemplateOnboardingWizardUseCase.php`
- Moved `OnboardingWizard` write/query-heavy actions into use-case:
  - ensure sets
  - create draft version
  - upload docx to selected version
  - generate metadata + mappings
  - coverage scan
  - preview render
  - version checksum refresh
- Moved `OnboardingWizard::publishSelectedVersion` into application use-case (`TemplateOnboardingWizardUseCase`).
- Added SetType write-focused use-cases:
  - `ManageSetTypeOrderTypesUseCase` (add/remove/update type)
  - `SetTypeUiConfigLifecycleUseCase` (create draft/publish/rollback/delete/reconcile version)
  - `SetTypeUiConfigWriteUseCase` (metadata field add/remove + ui config persistence transaction)
  - `SetTypeMetadataBootstrapUseCase` (metadata bootstrap/auto-init writes)
- Refactored SetType component + traits so write boundaries are executed through application use-cases instead of direct DB writes in Livewire layer.

## Phase 3

Goal: isolate cross-module reads and writes.

Tasks:

1. Define read contracts for external dependencies:
   - Personnel lookup
   - Structure lookup
   - Candidate lookup
2. Implement adapters in `Infrastructure/Persistence/Eloquent`.
3. Remove direct external model usage from `OrderRenderStateService` and `OrderLookupService`.

Progress:

- Added cross-module read contracts:
  - `PersonnelLookupReadRepository`
  - `StructureLookupReadRepository`
  - `RankPositionLookupReadRepository`
  - `OrderTypeStatusLookupReadRepository`
- Added Eloquent adapters:
  - `EloquentPersonnelLookupReadRepository`
  - `EloquentStructureLookupReadRepository`
  - `EloquentRankPositionLookupReadRepository`
  - `EloquentOrderTypeStatusLookupReadRepository`
- Bound contracts in Orders provider.
- Refactored `OrderLookupService` to use read contracts for Personnel/Candidate/Structure access (no direct model usage for those entities).
- Added architecture guard test:
  - `tests/Unit/Architecture/OrdersCrossModuleReadIsolationTest.php`
- Refactored `OrderLookupService` rank/position reads to `RankPositionLookupReadRepository` adapter.
- Refactored OrderType/OrderStatus lookup reads (`OrderLookupService`, `OrderCrud`, `AllOrders`, SetType UI lifecycle) to `OrderTypeStatusLookupReadRepository`.
- Refactored `OrderCrud` personnel name lookup (`Candidate::find` / `Personnel::find`) to `PersonnelLookupReadRepository`.
- Expanded architecture guard to scan Orders Livewire/trait layer for forbidden direct Rank/Position model queries.
- Expanded architecture guard to also block direct OrderType/OrderStatus query tokens in Orders Livewire/traits.
- Expanded architecture guard (targeted orchestration files) to block direct Candidate/Personnel/Structure/OrderType/OrderStatus import/query tokens.
- Added `AccessibleStructureScopeReadRepository` port and moved Orders read-side `StructureService` dependency behind adapter.
- Introduced `SetTypeReadUseCase` to centralize SetType/UI-config read path orchestration.
- Added `OrderPrintPayloadData` DTO to make print payload assembly typed before final array export.

## Phase 4

Goal: enforce boundaries by tests and static checks.

Tasks:

1. Add architecture tests:
   - Orders Livewire must not use Eloquent models directly.
   - Orders Domain/Application must not depend on `App\Models` except through contracts.
2. Add CI guard for architecture tests.

Progress:

- Added `OrdersDomainApplicationBoundaryTest`:
  - blocks direct external model usage tokens in `Domain` + `Application` layers (Personnel/Candidate/Structure/Rank/Position/OrderStatus).

## Phase 5

Goal: optional package-style extraction readiness.

Tasks:

1. Move module config, migrations, routes, views under package-like boundaries.
2. Introduce internal DTOs/VOs for render payloads and template schema.
3. Finalize adapter-only persistence access.
