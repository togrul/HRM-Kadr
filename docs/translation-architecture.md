# Translation Architecture

- Standard format: `module_namespace::file.section.item`
- Module namespace is derived from the module slug in canonical `lower_snake_case`
- Translation file keys must use `lower_snake_case`; no uppercase/lowercase fallback or duplicate normalized keys
- Module translations live under `app/Modules/<Module>/Resources/lang/<locale>/*.php`
- Root `lang/*.json` files are legacy fallback only; new module UI strings should move into namespaced PHP catalogs

## Examples

- `services::permissions.groups.attendance_manual`
- `services::permissions.methods.approve`
- `admin::languages.actions.add`

## Checks

- Lint catalogs: `php artisan translations:lint`
- Run the translation gate: `composer ci:translations`
