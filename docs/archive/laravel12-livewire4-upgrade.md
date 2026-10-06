# Laravel 12 + Livewire 4 Upgrade Notes

## Applied Changes

### 1) Core dependencies
Updated runtime/dev constraints in `/Users/togruljalalli/Desktop/projects/HRM/composer.json`:

- `laravel/framework`: `^10.10` -> `^12.0`
- `livewire/livewire`: `^3.0` -> `^4.0`
- `laravel/sanctum`: `^3.2` -> `^4.0`
- `laravel/breeze`: `^1.23` -> `^2.3`
- `spatie/laravel-permission`: `^5.11` -> `^6.0`
- `nunomaduro/collision`: `^7.0` -> `^8.6`
- `pestphp/pest`: `^2.0` -> `^3.8`
- `pestphp/pest-plugin-laravel`: `^2.0` -> `^3.2`

Then installed with:

```bash
composer update --with-all-dependencies --no-interaction --no-security-blocking
```

### 2) Livewire 4 config migration
`/Users/togruljalalli/Desktop/projects/HRM/config/livewire.php` aligned to v4 keys:

- `layout` -> `component_layout`
- `lazy_placeholder` -> `component_placeholder`
- Added v4 options: `component_locations`, `component_namespaces`, `make_command`, `class_path`, `smart_wire_keys`, `release_token`, `csp_safe`, `payload`.

Project-specific values preserved:

- `component_layout = layouts.app`
- `component_placeholder = includes.skeleton`
- upload preview mimes includes: `docx`, `pdf`, `doc`

### 3) Spatie Permission v6 compatibility
Fresh/test DB migration fixed in:

- `/Users/togruljalalli/Desktop/projects/HRM/database/migrations/2023_09_08_005201_create_permission_tables.php`

Replaced removed static props:

- `PermissionRegistrar::$pivotPermission`
- `PermissionRegistrar::$pivotRole`

with config-driven pivot keys:

- `$columnNames['permission_pivot_key'] ?? 'permission_id'`
- `$columnNames['role_pivot_key'] ?? 'role_id'`

### 4) Test baseline alignment (project behavior)
Adjusted tests to match current app behavior:

- `/Users/togruljalalli/Desktop/projects/HRM/tests/Feature/ExampleTest.php`
  - guest `/` now asserts redirect to login.
- `/Users/togruljalalli/Desktop/projects/HRM/tests/Feature/ProfileTest.php`
  - user deletion asserts soft-delete (`assertSoftDeleted`) because `User` uses `SoftDeletes`.

### 5) User delete hook safety
`/Users/togruljalalli/Desktop/projects/HRM/app/Models/User.php`

- `auth()->user()->id` -> `auth()->id()`
- `save()` -> `saveQuietly()` inside deleting callback

This avoids null-auth crashes in tests/CLI deletion flows.

## Verification Run

Executed successfully:

```bash
php artisan optimize:clear
php artisan route:list --except-vendor
php artisan optimize
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
```

Current versions verified:

- Laravel `12.51.0`
- Livewire `4.1.4`

## Important Notes

1. No destructive migration command was run by this upgrade flow.
2. Existing unrelated local modifications in table components remained untouched:
   - `/Users/togruljalalli/Desktop/projects/HRM/resources/views/components/table/tbl.blade.php`
   - `/Users/togruljalalli/Desktop/projects/HRM/resources/views/components/table/td.blade.php`
   - `/Users/togruljalalli/Desktop/projects/HRM/resources/views/components/table/tr.blade.php`

## Recommended Next Pass (optional)

- Frontend toolchain modernization (separate controlled task):
  - `vite` 4 -> 7
  - `laravel-vite-plugin` 0.8 -> 2.x
  - Tailwind 3 -> 4 (high-impact CSS migration)
- Livewire 4 enhancements adoption (not required for compatibility):
  - `Route::livewire(...)` for full-page components
  - `wire:navigate` progressive rollout
  - view-based components where useful
