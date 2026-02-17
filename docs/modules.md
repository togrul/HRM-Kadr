# Modules Overview

This project is split into self-contained modules. Each module ships its own
Livewire components, routes, views, and service provider. Shared patterns:

- Namespace: `App\Modules\<Name>\Livewire`
- Views namespace: `<name>::` (passed to `loadViewsFrom`)
- Routes: `app/Modules/<Name>/Routes/web.php` (loaded in provider)
- Livewire discovery: `config/livewire.php` lists module namespaces
- Alias registration: module providers register deterministic aliases via explicit `componentMap()` + `registerAliases($map, '<prefix>')`.

## Enabled Modules
- Personnel (`personnel::`) – CRUD, files, information, vacation list
- Orders (`orders::`) – order CRUD, templates
- Staff (`staff::`) – staff schedule
- Candidates (`candidates::`) – candidate CRUD/list
- Leaves (`leaves::`) – leave CRUD/list
- BusinessTrips (`business-trips::`) – business trip list
- Vacation (`vacation::`) – vacation list
- Admin (`admin::`) – admin settings/masters
- Services (`services::`) – settings, menus, users, ranks, components, roles
- Notifications (`notification::`) – bell dropdown and list
- SidebarStructure (`structure::`) – structure sidebar/orders/services Livewire snippets used across screens
- UI (`ui::`) – shared confirmation/filter/notification blade partials

## Provider pattern
- Load module routes/views in `boot()`
- Register Livewire aliases in a dedicated `registerLivewireComponents()` method using a manual `componentMap()` and `registerAliases($map, '<prefix>')` for deterministic aliases (e.g., `orders.add-order` → `App\Modules\Orders\Livewire\AddOrder`).
- Keep alias naming consistent: `<module>.<area>.<component>`

## Rendering pattern
- Livewire components return module-scoped views (e.g., `orders::livewire.orders.add-order`)
- Blade `@livewire` tags use registered aliases (e.g., `@livewire('services.roles.manage-roles')`)

## Adding a new module
1) Create `app/Modules/<Name>/Providers/<Name>ServiceProvider.php`; load routes/views and register aliases.
2) Add provider to `config/modules.php`; add namespace to `config/livewire.php` discovery.
3) Place Livewire classes under `app/Modules/<Name>/Livewire`; views under `app/Modules/<Name>/Resources/views`.
4) Update traits/render paths to use `<module>::` view prefixes.

### Quick recipe (providers + aliases + observers)
- In the provider `boot()`, call:
  - `$this->loadRoutesFrom(__DIR__.'/../Routes/web.php');`
  - `$this->loadViewsFrom(__DIR__.'/../Resources/views', '<prefix>');`
  - `$this->registerAliases($this->componentMap(), '<prefix>');`
- Alias map: explicit entries, kebab-cased where helpful (e.g., `orders.templates.add-template`).
- Register model observers in the same provider (e.g., `Setting::observe(SettingsObserver::class);`) so cache flushes stay within the module.
- Add the module namespace to `config/livewire.php` `discover.namespaces` to keep `@livewire('<prefix>.<alias>')` working without manual `Livewire::component` calls.
- Optional: to make a module togglable, add it to `config/modules.php` under `catalog` with `enabled => true/false` and (if needed) `migrations => app_path('Modules/<Name>/Database/Migrations')`, then guard the provider with `ModuleState::enabled('<slug>')` and call `loadMigrationsFrom` conditionally.
- Observers/cache flush: register model observers in the module provider (e.g., `Setting::observe(SettingsObserver::class)`) so cache invalidation stays module-scoped.

## Feature flags (organization profiles)
- `config/profiles.php` defines profiles (default/military/public/private). Active profile: `APP_PROFILE` or `profiles.active`.
- Each profile can override modules (on/off) and feature flags (e.g., `ranks`, `military_service`, `weapons`). Defaults live in the `default` profile.
- `feature_enabled('ranks')` helper and `@feature('ranks') ... @endfeature` Blade directive check FeatureState, which is built from the active profile. Use them to hide UI or relax validation when a feature is off.
