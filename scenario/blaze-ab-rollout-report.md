# Blaze A/B + Rollout Report (2026-03-04)

## Scope
- Step 1: A/B benchmark with `BLAZE_ENABLED=false/true`
- Step 2: Rollout Blaze compile to core module view paths (compile-only)
- Step 3: Keep/revert decision based on benchmark and safety checks

## Benchmark method
- Command: `php artisan orders:templates:query-budget --json --allow-empty`
- Repeated runs with env switch:
  - `BLAZE_ENABLED=false`
  - `BLAZE_ENABLED=true`

## Baseline sample (5 runs, before module-view rollout)
- `off` (warm runs): generally slower on `add_form_schema` and `edit_order_load`
- `on` (warm runs): generally faster in first two flows
- Notable result: `add_form_schema` query count dropped from 5 to 4 in current optimized setup

## Post-rollout sample (3 runs)

### Blaze OFF averages
- `add_form_schema`: **8.76 ms**
- `edit_order_load`: **11.14 ms**
- `print_payload_build`: **5.43 ms**

### Blaze ON averages
- `add_form_schema`: **8.09 ms**
- `edit_order_load`: **9.87 ms**
- `print_payload_build`: **5.83 ms** (noisy; slight variance)

## Safety checks
- `php artisan views:blaze-safe-lint --strict` => PASS
- `php artisan test tests/Unit/Architecture/CriticalActionIconsSmokeTest.php` => PASS
- `php artisan view:clear && php artisan view:cache` => PASS

## Rollout applied
Added compile-only (`memo=false`, `fold=false`) for:
- `app/Modules/Orders/Resources/views`
- `app/Modules/Candidates/Resources/views`
- `app/Modules/Leaves/Resources/views`
- `app/Modules/Staff/Resources/views`
- `app/Modules/Vacation/Resources/views`
- `app/Modules/BusinessTrips/Resources/views`

### Additional rollout (Stage 10)
Added compile-only (`memo=false`, `fold=false`) for:
- `app/Modules/Services/Resources/views`
- `app/Modules/Admin/Resources/views`
- `app/Modules/UI/Resources/views`

## Post Stage-10 sample (3 runs)

### Blaze OFF averages
- `add_form_schema`: **9.13 ms**
- `edit_order_load`: **10.23 ms**
- `print_payload_build`: **5.68 ms**

### Blaze ON averages
- `add_form_schema`: **7.32 ms**
- `edit_order_load`: **9.19 ms**
- `print_payload_build`: **5.80 ms** (noise-level difference)

### Additional rollout (Stage 11)
Added compile-only (`memo=false`, `fold=false`) for:
- `app/Modules/Notifications/Resources/views`
- `app/Modules/SidebarStructure/Resources/views`

## Post Stage-11 sample (3 runs)

### Blaze OFF averages
- `add_form_schema`: **8.96 ms**
- `edit_order_load`: **13.15 ms**
- `print_payload_build`: **6.50 ms**

### Blaze ON averages
- `add_form_schema`: **7.37 ms**
- `edit_order_load`: **8.90 ms**
- `print_payload_build`: **5.44 ms**

## Decision
- **Keep rollout enabled.**
- Rationale: no breakage, strict lint/smoke pass, and measurable improvement in key read flows (`add_form_schema`, `edit_order_load`) with acceptable variance.

## Next optional phase
- Expand rollout to additional module views (Services/Admin/UI) in compile-only mode.
- Re-run same A/B method after each expansion.
