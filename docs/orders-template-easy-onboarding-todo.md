# Orders Template Engine - Easy Onboarding TODO

> **Status (10.10.2026):** bu sənədin işarələri fevral–may aylarından sonra yenilənməyib; açıq qalan bəndlərin çoxu real vəziyyəti əks etdirməyə bilər. Aktiv iş siyahısı `~/Desktop/projects/ISLER_PLANI.md`-dir. Bəndi icra etməzdən əvvəl kodda yoxlayın.
>
> **Tutuşdurma (11.10.2026):** 46/46 bənd yoxlanıb. Metadata-driven şablon mühərriki (sets/versions/fields/mappings/blocks) silinib və Word şablon dizayneri (`orders.designer`, `order_word_templates`) ilə əvəz olunub; mühərrikə bağlı bəndlər `[~]` işarələnib.

## Target
Yeni `Əmr` və `Arayış` növlərini minimal kod dəyişikliyi ilə əlavə etmək:
- template qurulması sürətli olsun,
- metadata/form avtomatik yaransın,
- Word print stabil və düzgün çıxsın.

## Current State (Short)
- Metadata-driven foundation var.
- Versioning, publish/rollback, UI config editor var.
- Amma “tam no-code onboarding” üçün hələ bir neçə kritik hissə tamamlanmalıdır.

## Priority Backlog

### P1 - Reliability (must-have)
- [~] Single-active-version invariant DB səviyyəsində qorunsun: — metadata mühərriki silinib: app/Modules/Orders/Database/Migrations/2026_06_16_030000_drop_legacy_order_template_engine_tables.php (order_template_sets/versions/fields/mappings/blocks); Word şablon (order_word_templates) ilə əvəz olunub
  - [~] partial unique index/guard strategy (`is_active=1` per set). — order_template_versions cədvəli silinib (yuxarı bənd); order_word_templates.code unikaldır, `is_active` per set anlayışı yoxdur
- [~] Publish/Rollback əməliyyatlarına transaction + lock guard tamamlansın. — publish/rollback anlayışı yoxdur: yenidən yükləmə köhnə versiyanı arxivləyir — kod: app/Modules/Orders/Application/Document/OrderWordTemplateVersioner.php::archive()
- [~] UI config açılışında “active version not found” edge-case tam bağlansın. — UI config/aktiv versiya mexanizmi silinib (app/Modules/Orders/Database/Migrations/2026_06_16_030000_drop_legacy_order_template_engine_tables.php)
- [~] Generate metadata deterministic olsun: — metadata mühərriki silinib: app/Modules/Orders/Database/Migrations/2026_06_16_030000_drop_legacy_order_template_engine_tables.php (order_template_sets/versions/fields/mappings/blocks); Word şablon (order_word_templates) ilə əvəz olunub
  - [~] component field silinibsə metadata-dan da silinsin, — metadata generasiyası yoxdur; dəyişənlər yüklənmiş DOCX-dən çıxarılır — kod: app/Modules/Orders/Application/Document/DocxPlaceholderParser.php::extract()
  - [~] duplicate field/mapping yaranmasın. — eyni səbəb: mapping hər placeholder üçün bir sətirdir (OrderTemplateDesigner::$variables)

### P1 - Print Correctness (must-have)
- [x] Placeholder coverage validator: — kod: app/Modules/Orders/Livewire/OrderTemplateDesigner.php::save() (hər placeholder üçün həll olunan mənbə tələb olunur), DocxPlaceholderParser::extract()
  - [x] template-də olan placeholder-lar vs mappings fərq reportu. — kod: eyni; hesabat yox, saxlama zamanı rədd edilir (`choose_source`, `no_variables`)
- [x] DOCX output smoke test suite: — kod: tests/Feature/Orders/DocxPlaceholderParserTest.php, DocxPreviewArchiveSafetyTest.php, OrderComposerTest.php
  - [x] XML integrity, — kod: app/Modules/Orders/Application/Document/DocxPlaceholderParser.php::assertSafeFile()/assertSafeArchive(), tests/Feature/Orders/DocxPreviewArchiveSafetyTest.php
  - [x] required placeholders replaced check. — kod: tests/Feature/Orders/OrderComposerTest.php::documentText() yoxlamaları, DocxPlaceholderParserTest::test_it_normalizes_brackets_to_tokens_that_template_processor_fills
- [x] “Render preview” (server-side dry-run) əlavə edilsin. — kod: app/Modules/Orders/Livewire/OrderTemplateDesigner.php::previewTemplate(), tests/Feature/Orders/OrderTemplateDesignerTest.php::test_it_previews_the_stored_template_as_a_pdf

### P1 - Form Correctness (must-have)
- [x] `required` metadata -> runtime validation 1:1 işləsin. — kod: app/Modules/Orders/Infrastructure/Document/OrderCompositionIssuer.php (`required` yoxlaması), app/Models/OrderWordTemplate.php, tests/Feature/Orders/OrderComposerTest.php::test_an_order_with_an_empty_required_field_is_blocked
- [~] `input type` + lookup config (`model/searchField/selectedName`) admin UI-dan edit edilə bilsin. — model/searchField/selectedName konfiqi legacy metadata mühərrikinə aid idi; indi dəyişən tipi designer-də seçilir, siyahılar kodda — app/Modules/Orders/Infrastructure/Document/OrderLookupFieldRegistry.php
- [~] Row/component select məntiqi legacy fallback ilə konflikt verməsin. — legacy komponent cədvəlləri silinib — app/Modules/Orders/Database/Migrations/2026_06_20_120000_drop_legacy_component_tables.php

### P2 - Admin UX (high)
- [x] Mapping editor tamamlansın: — kod: app/Modules/Orders/Livewire/OrderTemplateDesigner.php (mapping sətirləri, tip/scope/mənbə redaktəsi), tests/Feature/Orders/OrderTemplateDesignerTest.php::test_it_remaps_a_variable_and_saves_without_reupload
  - [~] add/edit/delete, — sətirlər yüklənmiş DOCX-in placeholder-larından yaranır: əlavə/silmə faylı yenidən yükləməklə olur; ayrıca add/delete düyməsi yoxdur (qəsdən)
  - [~] sort order, — sıra DOCX-dəki placeholder sırasıdır (tests/Feature/Orders/DocxPlaceholderParserTest.php::test_it_extracts_run_split_placeholders_in_order); ayrıca sort sahəsi yoxdur
  - [x] scope (`row/scalar`) izahlı UI. — kod: app/Modules/Orders/Livewire/OrderTemplateDesigner.php::getScopeOptionsProperty(), app/Modules/Orders/Resources/lang/az/order_composer.php (designer.scopes)
- [~] Section blocks editor izahlı edilsin: — section block redaktoru silinib — app/Modules/Orders/Database/Migrations/2026_06_19_140000_drop_order_block_templates_table.php
  - [~] key read-only, — eyni səbəb
  - [~] `sort` label daha aydın (`order`). — eyni səbəb
- [~] Audit paneldə “what changed” diff daha oxunaqlı formatda göstərilsin. — order_template_version_audits cədvəli silinib; versiyalar siyahısı var (app/Modules/Orders/Livewire/OrderTemplateDesigner.php::getVersionsProperty()), diff göstərilmir

### P2 - New Type Onboarding Flow (high)
- [x] “Create New Template Set Wizard”: — kod: app/Modules/Orders/Livewire/OrderTemplateDesigner.php (route orders.designer, app/Modules/Orders/Routes/web.php)
  - [x] order type seç, — kod: OrderTemplateDesigner::updatedEffect()/getEffectOptionsProperty()
  - [x] docx upload, — kod: OrderTemplateDesigner::updatedUpload()
  - [x] auto-detect placeholders, — kod: OrderTemplateDesigner::updatedUpload(), tests/Feature/Orders/OrderTemplateDesignerTest.php::test_uploading_a_word_file_detects_and_lists_its_placeholders
  - [x] initial mappings generate, — kod: OrderTemplateDesigner::suggestAutoKey()
  - [x] publish draft preview. — kod: OrderTemplateDesigner::previewTemplate() (draft/publish mərhələsi yoxdur, preview var)
- [ ] “Clone from existing set” düyməsi: — kodda tapılmadı (app/Modules/Orders/Livewire/OrderTemplateDesigner.php-də klonlama yoxdur)
  - [ ] fields/mappings/ui config copy. — kodda tapılmadı

### P3 - Governance/Operations
- [x] Permissions: — kod: yeganə icazə `edit-orders` — OrderTemplateDesigner::save() `authorize('edit-orders')`, tests/Feature/Orders/OrderTemplateDesignerTest.php::test_users_without_edit_orders_are_forbidden
  - [~] who can draft, — draft mərhələsi yoxdur; ayrıca icazə yoxdur (edit-orders) — köhnə template permission-lar silinib: app/Modules/Orders/Database/Migrations/2026_06_16_040000_remove_legacy_order_template_permissions.php
  - [~] who can publish/rollback/delete. — publish/rollback yoxdur; arxiv versiya yeniləmə ilə yaranır, silmə üçün ayrıca icazə yoxdur
- [ ] Monitoring: — kodda tapılmadı (şablon generasiyası üçün monitorinq yoxdur)
  - [ ] generation failure rate, — kodda tapılmadı
  - [ ] slow render logs, — kodda tapılmadı
  - [ ] template version usage stats. — kodda tapılmadı
- [~] Scheduled health checks: — orphan mapping və published anlayışı silinib (alt bəndlərə bax)
  - [~] orphan mappings, — mappings cədvəli silinib
  - [~] inactive published cleanup candidates. — publish/inactive version anlayışı silinib

## Definition of Done (Expert Level)
- [x] Yeni order type əlavə etmək üçün backenddə yeni conditional branch yazmağa ehtiyac qalmasın. — kod: app/Modules/Orders/Infrastructure/Document/Effects/OrderEffectCatalog.php + OrderTemplateDesigner (effekt seçimi) — mövcud effektlə yeni şablon kod dəyişikliyi tələb etmir; yeni effekt növü üçün OrderEffect sinfi lazımdır
- [x] Admin yalnız template + metadata editor ilə tam işlək forma/print qura bilsin. — kod: OrderTemplateDesigner (DOCX + mapping + preview) və OrderComposer şablondan formanı qurur, tests/Feature/Orders/OrderTemplateDesignerTest.php
- [ ] Print output 100% valid DOCX və testlərlə qorunsun. — qismən: DocxPlaceholderParser::assertSafeFile və testlər var; 'həmişə valid DOCX' təminatı yoxlanmadı (yoxlanmadı)
- [~] Version lifecycle (draft/publish/rollback/delete) hər zaman deterministik olsun. — draft/publish/rollback/delete lifecycle silinib; yerinə yenidən yükləmədə arxivləmə (OrderWordTemplateVersioner)
