# Orders No-Legacy Rollout Plan

Bu sənəd metadata-driven Orders engine üçün legacy fallback-ların təhlükəsiz şəkildə tam söndürülməsi planıdır.

## 1) Məqsəd
- Form və print axınlarında legacy fallback-dan metadata-only rejimə keçid.
- Hər order type üçün publish edilmiş, coverage-clean metadata versiyası təmin etmək.
- Keçid zamanı mövcud iş axınını qırmamaq.

## 2) İdarəetmə Bayraqları
- `ORDERS_ENGINE_STRICT_MODE=true`
  - Bütün order type-lar üçün legacy fallback bloklanır (`form`, `print`).
  - Aktiv metadata version + row mapping yoxdursa create/edit/print bloklanır.
- Qeyd:
  - Keçid üçün istifadə edilən əlavə legacy toggle-lar (`ORDERS_ENGINE_METADATA_ONLY*`, `ORDERS_ENGINE_ALLOW_LEGACY_FALLBACK_*`) runtime konfiqurasiyadan çıxarılıb.
  - Aktiv siyasət: strict-mode + metadata-only render.

## 3) Hazırlıq Checklist-i
- [x] Bütün aktiv istifadə olunan order type-lar üçün `order_template_set` mövcuddur.
- [x] Hər set üçün ən az 1 `published + active` version mövcuddur.
- [x] Aktiv versiyada:
  - [x] `template_path` doludur.
  - [x] `order_template_fields` boş deyil.
  - [x] `order_template_mappings` içində ən az 1 `scope=row` mapping var.
- [x] Coverage nəticəsi:
  - [x] `missing_placeholders = 0`
  - [x] `orphan_mappings = 0` (və ya qəbul edilən whitelist siyasəti)
  - [x] Smoke check (`php artisan orders:templates:smoke --json`) `ok = checked_versions`

## 4) Rollout Strategiyası
1. Staging-də `strict_mode` aç.
2. Smoke axını yoxla (ən az 1 əsas order type):
   - add order
   - edit order
   - print/export docx
3. Readiness report:
   - `php artisan orders:templates:readiness --json`
   - `php artisan orders:templates:smoke --json`
4. Production canary:
   - əvvəl məhdud istifadəçi qrupu
   - sonra full rollout
5. Monitoring:
   - `orders.template.*` warning/error log-ları
   - print failure rate, form validation error spike

## 5) Rollback Planı
- Təcili rollback: `ORDERS_ENGINE_STRICT_MODE=false`
- Root-cause aradan qaldırıldıqdan sonra yenidən strict aç.

## 6) Legacy Cleanup (Strict stabilləşəndən sonra)
- Kod cleanup:
  - [x] `OrderLegacyRenderPayloadBuilder` runtime istifadəsini çıxarmaq.
  - [x] `OrderPrintPayloadFactory` içində metadata+legacy merge qatını silmək.
  - [x] Form schema servisində legacy catalog fallback qatını silmək.
  - [x] Readiness report statuslarını metadata-only semantikaya keçirmək (`legacy_fallback` branch çıxarıldı).
- Data cleanup:
  - [x] `orders.content` print runtime fallback kimi istifadə olunmur (yalnız template master/onboarding mənbə kontekstində qalır).
  - [x] Köhnə metadata bootstrap mənbə qatını (`GenerateDynamicFieldsService`) çıxarmaq; metadata seed yalnız DOCX placeholder + deterministic fallback ilə idarə olunur.
- Test cleanup:
  - [x] Legacy fallback testlərini strict-mode-only/metadata-only gözləntilərlə əvəzləmək.

## 7) Ops / Governance əlavələri
- [x] Permission matrix tətbiqi:
  - `manage-order-template-sets`
  - `manage-order-template-metadata`
  - `manage-order-template-versions`
- [x] UI config audit diff readability (`summary`, `diff`, `diff_highlights`).
- [x] Metrics command: `php artisan orders:templates:metrics --json`
  - generation error rate
  - slow render p95/p99
  - version usage
- [x] Query budget command: `php artisan orders:templates:query-budget --json`
  - add form schema
  - edit order load
  - print payload build
- [x] Scheduled report command: `php artisan orders:templates:report --json`
  - metrics + query-budget nəticələrini toplayır
  - log/slack/telegram kanallarına göndərir
  - `app/Console/Kernel.php` scheduler ilə daily/weekly işləyir
- [x] CI quality gate workflow əlavə edildi:
  - `.github/workflows/orders-template-quality-gate.yml`
  - `composer ci:orders-template-gate`
  - gate command-ları:
    - `orders:templates:metrics --json`
    - `orders:templates:query-budget --json --allow-empty`
- [x] Legacy audit command:
  - `php artisan orders:templates:legacy-audit --json`
  - strict mode + template readiness + legacy footprint metrikləri

## 8) Son Readiness Snapshot
- `php artisan orders:templates:readiness --json` nəticəsi:
  - `metadata_ready: 2`
  - `version_without_mappings: 0`
  - `version_not_active: 0`
  - `no_template_set: 0`
  - `legacy_form_blocked: 2`
  - `legacy_print_blocked: 2`
  - `strict_mode: enabled`

## 9) Done Kriteriyaları
- Production-da strict mode aktivdir.
- Yeni order add/edit/print axınlarında legacy fallback log-u yoxdur.
- 2 həftə ərzində template render error rate stabildir.
