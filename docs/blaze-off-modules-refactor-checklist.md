# Blaze Full Rollout Checklist (OFF Modules)

Bu checklist, hazırda `compile=false` olan 3 modulun (`Personnel`, `Notifications`, `SidebarStructure`) Blaze ile tam uyğunlaşması üçün refactor addımlarını verir.

## Current status (2026-03-04)
- `app/Modules/Personnel/Resources/views` -> `compile=false`
- `app/Modules/Notifications/Resources/views` -> `compile=false`
- `app/Modules/SidebarStructure/Resources/views` -> `compile=true` (canary)
- `app/Modules/SidebarStructure/Resources/views/livewire/structure/services.blade.php` -> `compile=false` (targeted bypass)

Sebep: bu modullarda əvvəllər Livewire root/hydration hataları (`RootTagMissingFromViewException`) görüldü.
Son canary nəticəsi:
- SidebarStructure üçün compile ON sınağı `/services` route-da root-tag xətası verdi (`App\Modules\SidebarStructure\Livewire\Services`).
- Module-level canary ON saxlanıldı, yalnız problemli `services.blade.php` üçün targeted compile OFF override tətbiq edildi.
- Notifications üçün compile ON sınağı root-tag riski səbəbi ilə rollback edilərək OFF saxlanıldı.

---

## Global guardrails (all 3 modules)
- [ ] Her modül üçün ayrıca branch + rollout PR.
- [x] Hər addımdan sonra:
  - [x] `php artisan view:clear && php artisan view:cache`
  - [x] `php artisan views:blaze-safe-lint --strict`
  - [x] smoke (feature test): `tests/Feature/Blaze/CompiledRoutesSmokeTest.php`
- [ ] İlk mərhələdə yalnız `compile=true`, `memo=false`, `fold=false`.
- [ ] Hata anında rollback: `config/blaze.php` path üçün `compile=false`.

---

## Module 1: Personnel (highest risk)

### Risk hotspots
- [ ] `x-slot` istifadə edilən view-lar (layout slot + Livewire mount kombinasiyası).
- [ ] Table rows içində nested Livewire komponentləri.
- [ ] Modal açılışında deferred render blokları.

### Refactor checklist
- [ ] Bütün `livewire/personnel/*.blade.php` fayllarında tək root element yoxlaması.
- [ ] `x-slot` yalnız parent layout context-də qalır; child partial-larda slot istifadəsi sıfırlanır.
- [ ] Table body içində condition-lar root-u pozmur (empty state + loop branch parity).
- [ ] Dropdown/modal partial-larında root wrapper standartı (`<div>`).
- [ ] Personnel üçün mərhələli enable:
  - [ ] əvvəl `partials/` alt qovluğu,
  - [ ] sonra `livewire/personnel/` əsas view-lar.

### Acceptance
- [ ] `AllPersonnel`, `EditPersonnel`, `DeletePersonnel`, `VacationList`, `Files` ekranları manual pass.
- [ ] 0 dəfə `RootTagMissingFromViewException`.

---

## Module 2: Notifications (high risk)

### Risk hotspots
- [ ] Alpine + Livewire birlikdə (`x-show`, `x-cloak`, click.away).
- [ ] Counter component + panel component paralel mount.
- [ ] pagination hook script (`window.__notificationPaginatorHooks`).

### Refactor checklist
- [x] `notifications.blade.php`, `notification-list.blade.php`, `notifications-counter.blade.php` üçün root wrapper sabitləşdirmə.
- [x] Alpine transition blokları parent root xaricinə çıxmır.
- [x] `placeholder()` view-ları da tək root qaydasına uyğun.
- [x] event dispatch (`notifications-refresh-count`) loop yaratmır.

### Acceptance
- [x] Header notification dropdown aç/bağla + mark-as-read pass (feature test).
- [x] Notification list pagination + clear pass (feature test).
- [ ] JS console error yoxdur (manual browser smoke pending).

---

## Module 3: SidebarStructure (medium risk)

### Risk hotspots
- [ ] Layout slot daxilində Livewire mount.
- [ ] Tree recursive view (`x-tree.*`) ilə interaction.
- [ ] Collapse/expand dispatch (`ui:sidebar-toggle`) ilə rerender.

### Refactor checklist
- [ ] Sidebar main view tək root + fixed structure.
- [ ] Tree item recursive component-lərdə conditional root qorunur.
- [ ] collapse state dəyişəndə root remount baş vermir.
- [ ] Sidebar route change (`wire:navigate`) sonrası state consistency.

### Acceptance
- [x] Sidebar open/close pass (route smoke-də non-500).
- [ ] Tree select + deep level expand pass (manual browser smoke pending).
- [ ] Route change sonrası ghost node/blank render yoxdur (manual browser smoke pending).

---

## Rollout plan
- [x] Step 1: `SidebarStructure` compile ON (ən az riskli) - ON with targeted file-level bypass
- [ ] Step 2: `Notifications` compile ON
- [ ] Step 3: `Personnel` compile ON (ən sonda)
- [ ] Hər stepdə 24 saat monitor:
  - [ ] `storage/logs/laravel.log` root/hydration errors
  - [ ] user-reported UI breakage

---

## Done criteria (full Blaze rollout for modules)
- [ ] `config/blaze.php` daxilində bu 3 path üçün `compile=true`.
- [ ] 7 gün ərzində root/hydration error 0.
- [ ] `views:blaze-safe-lint --strict` daimi yaşıl.
- [ ] Key user flows üçün no regression.
