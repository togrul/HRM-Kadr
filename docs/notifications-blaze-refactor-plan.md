# Notifications Blaze Refactor Plan (Safe-First)

## Current decision
- `app/Modules/Notifications/Resources/views` Blaze compile **OFF** (stability mode).
- Reason: compile canary zamanı Livewire page mount wrapper (`@livewire($name, $params)`) ilə
  `RootTagMissingFromViewException` yenidən tetikləndi.
- Refactor + tests saxlanılır, amma module-level compile yenidən sınanmadan əvvəl əlavə Blaze uyumluluk işi lazımdır.

## Why
- Geçmiş loglarda `RootTagMissingFromViewException` tetiklenmişti.
- Refactor + regression testlər tamamlandı, amma compile canary-də yenə root-tag istisnası görüldü.
- Buna görə production/staging sabitliyi üçün module-level Blaze compile yenə OFF saxlanır.

## Target files
- `app/Modules/Notifications/Resources/views/livewire/notification/notifications.blade.php`
- `app/Modules/Notifications/Resources/views/livewire/notification/notification-list.blade.php`
- `app/Modules/Notifications/Resources/views/livewire/notification/notifications-counter.blade.php`
- `app/Modules/Notifications/Resources/views/livewire/notification/placeholders/notifications-nav.blade.php`
- `app/Modules/Notifications/Livewire/Notifications.php`
- `app/Modules/Notifications/Livewire/NotificationList.php`
- `app/Modules/Notifications/Livewire/NotificationsCounter.php`

## Refactor checklist

### 1) Root-safety hardening
- [x] Tüm view dosyalarında tek ve sabit root wrapper bırak.
- [x] Root wrapper dışında koşullu render bırakma.
- [x] `@if/@empty/@forelse` blokları root içinde kalsın, root’u etkilemesin.
- [x] Placeholder view’ları da aynı root standardına getir.

### 2) Alpine + Livewire interaction safety
- [x] `x-show/x-cloak` paneli root içinde ve bağımsız container’da tut.
- [x] `x-on:click.away` ilə Livewire rerender race condition riskini azalt (state reset guard).
- [x] Dropdown içinde nested Livewire child (counter) için stable key kullanımı doğrula.

### 3) Pagination/list script isolation
- [x] `window.__notificationPaginatorHooks` init kodunu null-safe ve idempotent hale getir.
- [x] Hook register işlemini component-id guard ile tek seferde sabitle.
- [x] `commit` callback’inde sadece ilgili component id’ye çalıştır.

### 4) Render contract cleanup
- [x] `render()` metotlarının her zaman root tag üreten view döndürdüğünü doğrula.
- [x] `placeholder()` çıktısını root + minimal deterministic markup’a indir.
- [x] Auth guest branch’lerinde boş string dönme ihtimalini kaldır.

### 5) Smoke and gate
- [ ] `php artisan view:clear && php artisan view:cache`
- [x] `php artisan views:blaze-safe-lint --strict`
- [ ] Manuel smoke:
  - [ ] Header notifications dropdown open/close
  - [x] mark single read
  - [x] mark all read
  - [x] notifications list pagination
  - [x] clear notifications
- [ ] `storage/logs/laravel.log` içinde son 15dk root-tag/hydration error = 0

Automated coverage:
- `tests/Feature/Notifications/NotificationsRenderTest.php`
- `tests/Feature/Notifications/NotificationsActionsTest.php`

## Re-enable criteria (strict)
- [x] Refactor checklist full pass (code-side + test-side)
- [ ] 0 root-tag exception after full smoke
- [ ] Blaze ON sonrası 24 saat boyunca 0 exception

## Rollout step (deferred)
- `config/blaze.php`:
  - `app_path('Modules/Notifications/Resources/views') => compile: true` (memo/fold false)
- Ardından:
  - `php artisan view:clear`
  - `php artisan view:cache`
  - `php artisan views:blaze-safe-lint --strict`
