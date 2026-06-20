# Translation Migration Final Note

Date: 2026-03-09

The module-based translation migration is complete. Runtime dependency on `lang/az.json` has been removed and application translations now resolve through canonical PHP translation catalogs.

## What Was Completed

- Canonical key standard is now `module::file.key`.
- Module translation namespaces are loaded centrally through `ModuleServiceProvider`.
- Legacy uppercase/lowercase duplicate fallback logic was removed.
- Stored text now follows a strict rule:
  - canonical namespaced keys are translated
  - literal stored values are left as-is
- Translation linting and unit coverage were added.
- `lang/az.json` was removed from the repo.

## Current Expected Exceptions

These are normal and are not `az.json` regressions:

- `resources/views/vendor/livewire/*`
  - Uses Laravel/Livewire pagination group keys such as `pagination.previous`.
- Standard framework group translations like `trans('auth.*')`
  - These belong to Laravel language groups, not module catalogs.
- Blank action table headers
  - Some tables still pass an action key/label into the shared table component, but the component intentionally renders that header cell empty.

## Remaining Technical Debt

The translation migration itself is closed, but two follow-up items remain:

1. Test schema alignment
   - Fresh sqlite test runs still fail because the `permissions` table used in tests does not initially include the `description` column expected by later permission-seeding migrations.
2. Documentation alignment
   - Module docs must continue to reflect the distinction between view namespaces and translation namespaces where they differ.

## Verification Snapshot

- `php artisan translations:lint` passes
- Translation-focused unit tests pass
- Broad runtime scans no longer show raw phrase-based translation helpers in application code

## Practical Outcome

- New work should use only canonical namespaced PHP translation catalogs.
- New modules should follow the same pattern from day one.
- `az.json` should not be reintroduced.
