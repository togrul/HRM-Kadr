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

## Phase 3

Goal: isolate cross-module reads and writes.

Tasks:

1. Define read contracts for external dependencies:
   - Personnel lookup
   - Structure lookup
   - Candidate lookup
2. Implement adapters in `Infrastructure/Persistence/Eloquent`.
3. Remove direct external model usage from `OrderRenderStateService` and `OrderLookupService`.

## Phase 4

Goal: enforce boundaries by tests and static checks.

Tasks:

1. Add architecture tests:
   - Orders Livewire must not use Eloquent models directly.
   - Orders Domain/Application must not depend on `App\Models` except through contracts.
2. Add CI guard for architecture tests.

## Phase 5

Goal: optional package-style extraction readiness.

Tasks:

1. Move module config, migrations, routes, views under package-like boundaries.
2. Introduce internal DTOs/VOs for render payloads and template schema.
3. Finalize adapter-only persistence access.

