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
  - [x] `OrderCrud` içində `componentForms` və `selectedComponents` runtime state adlarını designer/schema terminləri ilə əvəz etmək.
  - [x] Component row trait-lərini (`HandlesComponentRows`, `HandlesOrderComponentFieldState`, `ManagesOrderComponents`) designer/schema resolver axını ilə əvəz etmək.
  - [x] `OrderPrintPayloadFactory` içində legacy render payload branch-ını yalnız historical print üçün izolyasiya etmək, sonra silmək.
  - [x] `GenerateWordReplaceContent` və köhnə `${content}` DOCX axınını arxivləmək.
- Data cleanup:
  - [x] `orders.content` template path mənbəyi kimi oxunmadıqdan sonra drop migration planı hazırlamaq.
  - [x] `components.dynamic_fields` runtime/admin yazılışını dayandırmaq.
  - [x] `components.dynamic_fields` üçün staging təsdiqindən sonra ayrıca drop migration planı hazırlamaq.
  - [x] `order_log_components` / `order_log_component_attributes` historical print üçün lazım olmadıqda drop migration planı hazırlamaq.
- Test cleanup:
  - [x] Template schema olmayan order type üçün legacy `components.dynamic_fields` UI fallback-ının render olunmadığını testlə bağlamaq.
  - [x] Qalan legacy fallback testlərini designer-first gözləntilərlə əvəzləmək.

### 6.1) Bağlanış (2026-10-10)

Bölmə 6 bağlanıb. Qeyd: kod bəndlərinin çoxu artıq phase 6 (4a–4b.3) commit-lərində
silinmişdi; bu bağlanış qalan izləri təmizləyir, data drop-larını arxivlə təhlükəsiz edir
və sənədi faktiki vəziyyətə uyğunlaşdırır.

**Silinib (kod):**
- `OrderCrud`, `EditOrder`, `componentForms` / `selectedComponents` state-i və row trait-ləri
  (`HandlesComponentRows`, `HandlesOrderComponentFieldState`, `ManagesOrderComponents`) —
  `cca1b888`-də silinib. Yerinə `OrderComposer` + Word şablonu (`order_word_templates`) gəlib;
  ad dəyişikliyinə ehtiyac qalmayıb.
- `OrderPrintPayloadFactory` və legacy render/snapshot builder-ləri — `cca1b888`. `printOrder()`
  yalnız `template_render_mode = docx` əmrləri `template_snapshot.docx_path`-dan verir
  (`c72a7e5a`); köhnə render rejimli əmr üçün print/PDF fallback **yoxdur** (404).
- `App\Services\GenerateWordReplaceContent` + testi, ölü `x-dynamic-input` blade komponenti
  (köhnə `$fullname`/`$structure` placeholder sahə renderer-i), `StructureSelect`-dəki
  `componentFieldValue` hook-u, `radio-tree.item`-in `componentForms` default-u,
  `services::components` dil faylı və `dynamic_fields` açarı.
- `orders.content` oxunuşu: self-service məzuniyyət binder-inin `content LIKE` fallback-ı,
  `Order::$fillable`, `OrderSeeder`. Binder `content`-i yalnız sütun hələ varsa (keçid anı) boş yazır.

**Historical print üçün nə saxlanılıb:** heç nə. Köhnə (pre-designer) əmrlər artıq
çap olunmur/yenidən render edilmir; onların məlumatı `order_logs` + `order_log_personnels`
sətirlərində qalır. Ona görə `order_log_components*` və `orders.content`-i `template_snapshot`-a
backfill etməyə ehtiyac yoxdur — yalnız arxivlənir.

**Arxiv:** `legacy_order_archive` (`source`, `source_key`, JSON `payload`, `archived_at`).
Hər dağıdıcı Orders miqrasiyası drop-dan əvvəl sətirləri buraya köçürür; down() məlumatı
buradan geri yazır və öz arxiv sətirlərini silir.

**Miqrasiyalar:**
| Miqrasiya | Növ |
|---|---|
| `2026_06_20_110000_create_legacy_order_archive_table` | yeni cədvəl, dağıdıcı deyil (down() arxiv boş deyilsə imtina edir) |
| `2026_06_20_120000_drop_legacy_component_tables` | **DAĞIDICI** — `components` (+ `dynamic_fields`), `order_log_components`, `order_log_component_attributes`, `order_log_personnels.component_id`. İndi drop-dan əvvəl arxivləyir. **Diqqət:** bu miqrasiya `main`-də artıq var idi; artıq işləmiş mühitlərdə arxiv addımı yenidən işləməyəcək (o data artıq silinib) |
| `2026_10_10_200000_archive_and_drop_orders_content` | **DAĞIDICI** — `orders.content` |

**Production rollout addımları:**
1. Tam DB backup (`mysqldump` — ən azı `orders`, `order_log_personnels`, və hələ varsa
   `components`, `order_log_components`, `order_log_component_attributes`). Backup-ı bərpa edə bildiyini yoxla.
2. `php artisan migrate:status` — `2026_06_20_120000` artıq `Ran`-dırsa, komponent datası artıq
   arxivsiz silinib (yalnız backup-dadır); `Pending`-dirsə, indi arxivlə silinəcək.
3. Kodu deploy et, sonra `php artisan migrate --force`. Sıra fərqi problem deyil: binder sütun
   varsa `content`-i boş yazır, yoxdursa yazmır.
4. Yoxla: `SELECT source, COUNT(*) FROM legacy_order_archive GROUP BY source` mənbə cədvəl
   sətir sayları ilə üst-üstə düşür.
5. Rollback: `php artisan migrate:rollback --path=app/Modules/Orders/Database/Migrations/2026_10_10_200000_archive_and_drop_orders_content.php`
   (sütunu nullable kimi qaytarıb dəyərləri arxivdən yazır).

**Bayraqlar / readiness əmrləri:** `ORDERS_ENGINE_STRICT_MODE`,
`ORDERS_ENGINE_WRITE_LEGACY_COMPONENT_SNAPSHOTS`, `ORDERS_ENGINE_DEFAULT_RENDER_MODE` və
`orders:templates:{legacy-audit,doctor,readiness,smoke}` əmrləri `cca1b888`-də silinib —
legacy yol fiziki olaraq yoxdur, ona görə "strict mode" artıq konfiqurasiya yox, kodun özüdür.
Bölmə 2, 4 və 10 tarixi qeyd kimi qalır. Mövcud yoxlamalar: `composer ci:orders-gate`
(`orders:list-query-budget`), `OrdersNoLegacyEngineTest` (köhnə simvolların geri qayıtmaması),
`LegacyOrderArchiveMigrationTest` (arxiv + bərpa), `AllOrdersInteractionTest::test_pre_designer_orders_have_no_print_fallback`.

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
> Tarixi qeyd (bağlanışdan əvvəlki vəziyyət). Faktiki vəziyyət üçün bax 6.1: `componentForms`, `components.dynamic_fields`, `order_log_components*` və `orders.content` artıq yoxdur; yalnız `orders.blade` qalır (binder və `OrderLog::handleDeletion` istifadə edir).

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
