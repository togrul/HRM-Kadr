# Blaze Final State (Hybrid, Stable)

Tarih: 2026-03-04

## Final decision
- Projede Blaze **hybrid mode** ile kullanılacak.
- Amaç: stabiliteyi bozmadan component render optimizasyonu almak.
- Full module-view rollout şu mərhələdə dayandırılıb (root-tag/hydration risklərinə görə).
- Blaze fazı bu qərarla **closed** hesab olunur (yeni rollout yalnız ayrıca canary planı ilə).

## Active policy
- `resources/views/components/**` üçün Blaze aktivdir.
- Livewire module view-ları üçün Blaze yalnız ayrıca canary + smoke ilə açılır.
- Default olaraq yeni modul view-ları Blaze-a əlavə edilmir.

## What is ON now (principle)
- Shared/component layer (button, badge, table shell, common UI atoms)
- SidebarStructure module view-ları (canary) **except**
  - `app/Modules/SidebarStructure/Resources/views/livewire/structure/**` (targeted bypass, compile OFF)

## What is OFF now (principle)
- Problemli və ya yüksək riskli Livewire module view path-ləri:
  - Personnel
  - Notifications
  - Orders
  - Staff
  - Candidates
  - Leaves
  - Vacation
  - BusinessTrips
  - Services
  - Admin
  - UI

## Known guardrails
- SidebarStructure üçün compile ON saxlanılır, amma `livewire/structure/**` path-i compile OFF-dur.
- Bu guardrail root-tag incident-ləri təkrarlamamaq üçündür; əvvəl canary zamanı `structure.sidebar`, `structure.orders`, `structure.services` komponentlərində aralıqlı root-tag xətası müşahidə olunub.
- Regression test:
  - `tests/Feature/Blaze/SidebarStructureRootTagRegressionTest.php`
  - Bu test 3 komponenti birbaşa mount edir və boş/root-less render regressiyasını bloklayır.
- Route-level smoke:
  - `tests/Feature/Blaze/CompiledRoutesSmokeTest.php`

## Quality gate commands
- `php artisan view:clear`
- `php artisan view:cache`
- `php artisan views:blaze-safe-lint --strict`

## New module / component rule
1. Yeni Blade component `resources/views/components/**` altındadırsa:
   - Adətən əlavə `config/blaze.php` dəyişikliyi lazım deyil.
2. Yeni Livewire module view üçün Blaze istifadəsi istənirsə:
   - əvvəlcə OFF saxla,
   - checklist + canary ilə test et,
   - yalnız problemsizdirsə həmin path üçün `compile=true` et.

## Closure criteria met
- [x] App stabil işləyir (kritik route-lar açılır)
- [x] Blaze-safe lint strict yaşıl
- [x] Rollback strategiyası sadə və sürətlidir
- [x] Team üçün əməliyyat qaydası sənədləşdirildi
