# Orders No-Legacy / Designer Rollout Plan

Bu sənəd Orders engine-də köhnə component/DOCX fallback-larını təhlükəsiz şəkildə söndürüb yeni `designer_layout` block engine-inə keçid planıdır.

## 1) Məqsəd
- Form və print axınlarında legacy component/DOCX fallback-dan designer-driven rejimə keçid.
- Hər order type üçün publish edilmiş, designer block-ları tam olan aktiv versiya təmin etmək.
- Keçid zamanı mövcud iş axınını qırmamaq.

## 2) İdarəetmə Bayraqları
- `ORDERS_ENGINE_STRICT_MODE=true`
  - Bütün order type-lar üçün legacy fallback bloklanır (`form`, `print`).
  - Aktiv template version yoxdursa və ya designer/metadata coverage natamamdırsa create/edit/print bloklanır.
- `ORDERS_ENGINE_DEFAULT_RENDER_MODE=designer_layout`
  - Yeni yaradılan order type/template version-lar block designer ilə başlayır.
  - Legacy DOCX placeholder rejimi yalnız köhnədən onboard edilmiş tiplər üçün saxlanılır.
- `ORDERS_ENGINE_WRITE_LEGACY_COMPONENT_SNAPSHOTS=true`
  - Müvəqqəti compatibility mirror-dur.
  - `order_log_components` və `order_log_component_attributes` yazılışını saxlayır.
  - Staging-də `false` edilərək `template_snapshot` print axınının tamlığı ayrıca yoxlanmalıdır.
- Qeyd:
  - Keçid üçün istifadə edilən əlavə legacy toggle-lar (`ORDERS_ENGINE_METADATA_ONLY*`, `ORDERS_ENGINE_ALLOW_LEGACY_FALLBACK_*`) runtime konfiqurasiyadan çıxarılıb.
  - Aktiv siyasət: strict-mode + designer-first render.

## 3) Hazırlıq Checklist-i
- [x] Bütün aktiv istifadə olunan order type-lar üçün `order_template_set` mövcuddur.
- [x] Hər set üçün ən az 1 `published + active` version mövcuddur.
- [x] Aktiv versiyada:
  - [x] `render_mode = designer_layout` və ya keçid üçün coverage-clean metadata versiyasıdır.
  - [x] Designer versiyada əsas block-lar var: `order_title`, `subject`, `clauses`, `signature`.
  - [x] Metadata versiyada `template_path`, fields və row mappings tamdır.
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
   - `php artisan orders:templates:legacy-audit --json --fail-on-blockers`
   - `php artisan orders:templates:doctor --json --fail-on-issues`
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
  - [x] `OrderCrud` içində schema-ready və legacy component fallback sərhədini helper-lərlə ayırmaq.
  - [x] Legacy component snapshot yazılışını `OrderLegacyComponentSnapshotPersister` altında mərkəzləşdirmək.
  - [x] `componentForms` üçün `orderRows` adapter qatını əlavə etmək və daxili default/vacancy/edit oxunuşlarını adapterdən keçirmək.
  - [x] `HandlesOrderComponentFieldState` içində component row oxu/yazılarını lokal helper-lərlə mərkəzləşdirmək.
  - [x] `OrderCrud` içində `componentDefinitions` / `components.dynamic_fields` fallback oxunuşunu çıxarmaq.
  - [ ] `OrderCrud` içində `componentForms` və `selectedComponents` runtime state adlarını designer/schema terminləri ilə əvəz etmək.
  - [ ] Component row trait-lərini (`HandlesComponentRows`, `HandlesOrderComponentFieldState`, `ManagesOrderComponents`) designer/schema resolver axını ilə əvəz etmək.
  - [ ] `OrderPrintPayloadFactory` içində legacy render payload branch-ını yalnız historical print üçün izolyasiya etmək, sonra silmək.
  - [ ] `GenerateWordReplaceContent` və köhnə `${content}` DOCX axınını arxivləmək.
- Data cleanup:
  - [ ] `orders.content` template path mənbəyi kimi oxunmadıqdan sonra drop migration planı hazırlamaq.
  - [x] `components.dynamic_fields` runtime/admin yazılışını dayandırmaq.
  - [ ] `components.dynamic_fields` üçün staging təsdiqindən sonra ayrıca drop migration planı hazırlamaq.
  - [ ] `order_log_components` / `order_log_component_attributes` historical print üçün lazım olmadıqda drop migration planı hazırlamaq.
- Test cleanup:
  - [x] Template schema olmayan order type üçün legacy `components.dynamic_fields` UI fallback-ının render olunmadığını testlə bağlamaq.
  - [ ] Qalan legacy fallback testlərini designer-first gözləntilərlə əvəzləmək.

## 7) Yeni Əmr Tipini 0-dan Yaratmaq Ardıcıllığı
1. `Şablonlar -> Əmr tipi` hissəsində yeni order type yaradılır.
   - Sistem avtomatik `code`, `is_active=true`, `render_mode=designer_layout` və boş designer version yaradır.
2. Designer-də sənəd block-ları qurulur:
   - `header`: müəssisə adı, şəhər, tarix, əmr nömrəsi
   - `order_title`: məsələn “Əmək məzuniyyətinin verilməsi haqqında”
   - `legal_basis`: əsas qanun/maddə cümləsi
   - `clauses`: bəndlər, şərtli bəndlər, təkrar əməkdaş sətirləri
   - `basis`: əsas sənəd
   - `signature`: rəhbər/imza sahibi snapshot
3. Lazım olan form field-ləri designer block variable-larına bağlanır.
   - Əməkdaş, struktur, vəzifə, tarix, gün sayı, əsas sənəd kimi sahələr registry-dən seçilməlidir.
   - Manual text field yalnız real biznes sahəsi olduqda əlavə edilməlidir.
4. Preview/doctor yoxlanır:
   - `php artisan orders:templates:doctor --fail-on-issues`
   - browser preview və DOCX export vizual yoxlanır.
5. Version publish edilir.
   - Publish edilən version artıq add/edit/print axınında istifadə olunur.
6. Real order yaradılıb test edilir:
   - validation mesajları
   - generated text
   - DOCX layout
   - signatory snapshot

## 8) Köhnə Strukturdan Nə Qalıb
- `componentForms`
  - Hazırda form row state namespace kimi qalır.
  - Bu ad həm legacy, həm də yeni schema-driven row-lar üçün istifadə olunduğuna görə birbaşa silinməməlidir.
  - `orderRows()` adapter qatı əlavə olunub; daxili kod yeni neytral helper-lərə keçməlidir, Livewire binding-lər isə ayrıca mərhələdə dəyişdirilməlidir.
- `components.dynamic_fields`
  - Köhnə component field schema mənbəyidir.
  - Runtime/admin yazılışı dayandırılıb və aktiv designer template-lərdə əsas mənbə deyil.
  - DB-də tarixi/keçid izi kimi qalır; staging təsdiqindən sonra drop migration mümkündür.
- `orders.content`
  - Köhnə DOCX/template path və `${content}` axınının izi kimi qalır.
  - Yeni designer engine-də sənəd layout-u `order_template_blocks` / `designer_layout` üzərindən gəlməlidir.
- `order_log_components` və `order_log_component_attributes`
  - Köhnə snapshot cədvəlləridir.
  - Yeni kodda birbaşa attach/create edilmir; yazılış `OrderLegacyComponentSnapshotPersister` üzərindən gedir.
  - Historical print bütünlüklə `template_snapshot` ilə təmin ediləndən sonra arxiv/drop planına keçə bilər.
- `orders.blade`
  - Köhnə form layout selector-dur.
  - Hələ bəzi kod branch-larında default/vacation/business-trip davranışını seçir.
  - Final modeldə bu məsuliyyət order type handler + designer schema-ya keçməlidir.

## 9) Ops / Governance əlavələri
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
  - `--fail-on-blockers` CI/staging gate üçün istifadə olunur
  - strict mode + designer readiness + legacy footprint metrikləri

## 10) Son Readiness Snapshot
- `php artisan orders:templates:legacy-audit --json` nəticəsi:
  - `strict_mode: enabled`
  - `template_tables_ready: yes`
  - `order_types_total: 3`
  - `order_types_without_template_set: 0`
  - `active_versions_total: 3`
  - `active_designer_versions: 3`
  - `active_non_designer_versions: 0`
  - `legacy_snapshot_orders: 0`
  - `blockers: 0`
  - `orders.content`: hələ `keep-for-now`
  - `components.dynamic_fields`: `candidate` - runtime/admin yazılışı çıxarılıb, DB drop üçün staging təsdiqi lazımdır

## 11) Done Kriteriyaları
- Production-da strict mode aktivdir.
- Yeni order add/edit/print axınlarında legacy fallback log-u yoxdur.
- 2 həftə ərzində template render error rate stabildir.
- `orders:templates:legacy-audit --fail-on-blockers` production/staging-də sıfır blocker qaytarır.
