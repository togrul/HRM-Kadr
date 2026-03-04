# Notifications Blaze Refactor Plan (Safe-First)

## Current decision
- `app/Modules/Notifications/Resources/views` Blaze compile **OFF** kalacak.
- Canary enable yapılmayacak.
- Önce refactor + smoke + log check tamamlanacak, sonra yeniden denenecek.

## Why
- Geçmiş loglarda `RootTagMissingFromViewException` tetiklenmiş.
- Hata path’i notification render zincirinde (`notification.notifications*`) görülmüş.
- Üretim stabilitesi için rollout değil, önce uyumluluk refactor’u gerekli.

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
- [ ] Tüm view dosyalarında tek ve sabit root wrapper bırak.
- [ ] Root wrapper dışında koşullu render bırakma.
- [ ] `@if/@empty/@forelse` blokları root içinde kalsın, root’u etkilemesin.
- [ ] Placeholder view’ları da aynı root standardına getir.

### 2) Alpine + Livewire interaction safety
- [ ] `x-show/x-cloak` paneli root içinde ve bağımsız container’da tut.
- [ ] `x-on:click.away` ile Livewire rerender race condition riskini azalt (state reset guard).
- [ ] Dropdown içinde nested Livewire child (counter) için stable key kullanımı doğrula.

### 3) Pagination/list script isolation
- [ ] `window.__notificationPaginatorHooks` init kodunu null-safe ve idempotent hale getir.
- [ ] Hook register işlemini component-id guard ile tek seferde sabitle.
- [ ] `message.processed` callback’inde sadece ilgili component id’ye çalıştır.

### 4) Render contract cleanup
- [ ] `render()` metotlarının her zaman root tag üreten view döndürdüğünü doğrula.
- [ ] `placeholder()` çıktısını root + minimal deterministic markup’a indir.
- [ ] Auth guest branch’lerinde boş string dönme ihtimalini kaldır.

### 5) Smoke and gate
- [ ] `php artisan view:clear && php artisan view:cache`
- [ ] `php artisan views:blaze-safe-lint --strict`
- [ ] Manuel smoke:
  - [ ] Header notifications dropdown open/close
  - [ ] mark single read
  - [ ] mark all read
  - [ ] notifications list pagination
  - [ ] clear notifications
- [ ] `storage/logs/laravel.log` içinde son 15dk root-tag/hydration error = 0

## Re-enable criteria (strict)
- [ ] Refactor checklist full pass
- [ ] 0 root-tag exception after full smoke
- [ ] Blaze ON denemesi sonrası tekrar 0 exception (minimum 24h)

## Re-enable step
- `config/blaze.php`:
  - `app_path('Modules/Notifications/Resources/views') => compile: true` (memo/fold false)
- Ardından:
  - `php artisan view:clear`
  - `php artisan view:cache`
  - `php artisan views:blaze-safe-lint --strict`

