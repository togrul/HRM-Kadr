# Dynamic Order Engine Master Plan

Bu sənəd HRM layihəsində bütün əmrlərin dinamik, rahat idarə olunan və avtomatik generasiya olunan sistemə keçirilməsi üçün master planıdır.

Məqsəd: 40-50+ əmr növünü ayrıca hardcode formalarla yox, vahid `Order Engine` üzərindən idarə etmək. Sistem müştəri üçün sadə görünməli, developer üçün isə genişlənən, test olunan və performanslı qalmalıdır.

---

## 1. Əsas Problem

Müştəri istəyir ki, sistemdə bütün əmrlər avtomatik yaransın:

- işə qəbul əmri
- məzuniyyət əmri
- ezamiyyət əmri
- işdən azad əmri
- vəzifə dəyişikliyi əmri
- struktur dəyişikliyi əmri
- sınaq müddəti əmri
- intizam əmri
- mükafatlandırma əmri
- əmək haqqı / ştat dəyişikliyi əmri
- digər 40-50+ əmr növü

Əgər hər əmr növü üçün ayrıca Blade, ayrıca Livewire logic, ayrıca validation, ayrıca Word generate logic yazılsa, sistem tez böyüyəcək, amma idarəolunmaz olacaq.

Doğru yanaşma: əmrləri `Order Type + Schema + Template + Variables + Handler + Snapshot` modelinə keçirməkdir.

---

## 0. İcra Statusu

Hazırda tamamlanan əsas bloklar:

- Order type handler registry və DB-backed handler resolution.
- Create/update pipeline-ların handler hook-lara keçirilməsi.
- Validation və schema resolver-lərin ayrılması.
- Snapshot builder və print payload-da handler variable merge.
- Əsas handler sinifləri: hiring, leave, business trip, transfer, termination.
- Təsdiq gateway-i: `OrderConfirmedService` təsdiqlənmiş əmrlərdə uyğun handler-in `afterApproved()` hook-unu çağırır.
- Personnel scalar variable-ları: `employee.full_name`, `employee.father_suffix`, `employee.position`, `employee.structure`.
- Template Studio variable dictionary paneli: hazır sistem dəyişənləri UI-dan kopyalanır.
- Central field renderer inteqrasiyası: metadata row field-lər mövcud `x-dynamic-input` üzərindən render olunur.
- Generation status UI: son DOCX generation statusu orders grid-də görünür.
- Async generation queue foundation: DOCX faylı fonda növbəyə salına və `order_generation_logs` üzərindən izlənə bilir.
- Regression matrix sənədi: `docs/orders-dynamic-engine-regression-matrix.md`.

Qalan əsas bloklar:

- Real biznes side-effect-lərin hər handler üzrə biznes qaydaları təsdiqləndikcə mərhələli yazılması.
- Staging data ilə query/render budget ölçülərinin təkrar təsdiqi.

---

## 2. Hədəf Arxitektura

### 2.1. Order Type Registry

Hər əmr növü sistemdə ayrıca `order_type` kimi saxlanılır.

Saxlanmalı metadata:

- kod: `hiring`, `leave`, `transfer`, `termination`, `business_trip`
- ad: `İşə qəbul`, `Məzuniyyət`, `Daxili yerdəyişmə`
- kateqoriya
- aktiv/passiv status
- bağlı modul
- default approval flow
- default template set
- handler class
- generation mode: sync/async
- icazə açarları

Yeni əmr növü gələndə ilk addım yeni `order_type` yaratmaq olmalıdır, yeni form faylı yaratmaq yox.

### 2.2. Dynamic Field Schema

Hər əmr növünün forması schema ilə idarə olunmalıdır.

Schema sahələri:

- `field_key`
- `label`
- `field_type`
- `input_type`
- `required`
- `validation_rules`
- `data_source`
- `default_value`
- `dependency_rules`
- `transform_rules`
- `ui_config`
- `sort_order`
- `group`

Nümunə:

```json
{
  "personnel_id": {
    "label": "Əməkdaş",
    "input": "personnel_select",
    "required": true,
    "rules": ["required", "integer", "exists:personnels,id"]
  },
  "start_date": {
    "label": "Başlama tarixi",
    "input": "date",
    "required": true,
    "rules": ["required", "date"]
  }
}
```

Bu schema admin UI-də idarə edilə bilər, amma kritik field-lər üçün developer tərəfindən seed/config guard da olmalıdır.

### 2.3. Central Field Component System

Form sahələri yalnız mərkəzi field renderer üzərindən gəlməlidir.

Standart field tipləri:

- text
- textarea
- number
- date
- money
- personnel_select
- candidate_select
- structure_select
- position_select
- rank_select
- dictionary_select
- enum_radio
- checkbox
- file_upload
- repeater
- readonly_snapshot

Hər field type üçün mərkəzi davranış:

- UI component
- validation
- placeholder
- error display
- selected label
- search/load behavior
- accessibility label
- mobile layout
- Livewire binding

Bu qayda vacibdir: yeni əmr gələndə yeni input dizaynı yazılmamalıdır.

### 2.4. Template Versioning

Hər əmr tipinin şablonu versiyalı olmalıdır.

Mövcud bazada artıq bunlar var:

- `order_template_sets`
- `order_template_versions`
- `order_template_fields`
- `order_template_mappings`
- `order_template_version_audits`
- `order_generation_logs`

Bunlar düzgün istiqamətdir. Növbəti mərhələ bu strukturu sadəcə template admin üçün yox, bütün əmr yaratma axınının əsasına çevirməkdir.

Qayda:

- aktiv şablon yalnız bir dənə olmalıdır
- köhnə order köhnə template snapshot ilə açılmalıdır
- template dəyişəndə köhnə order dəyişməməlidir
- publish etməzdən əvvəl placeholder coverage yoxlanmalıdır

### 2.5. Variable Dictionary

Müştəri template-də texniki adlar görməməlidir.

UI-də belə görməlidir:

- Əməkdaşın tam adı
- Əməkdaşın ata adı şəkilçisi
- Əməkdaşın vəzifəsi
- Əməkdaşın strukturu
- İşə başlama tarixi
- Məzuniyyət başlanğıcı
- Məzuniyyət sonu
- Əmr nömrəsi
- İmza sahibi
- İmza sahibinin rütbəsi

Arxada isə bu dəyişənlər texniki key-lərə bağlanır:

```text
employee.full_name
employee.father_suffix
employee.position.name
employee.structure.name
order.order_no
order.given_date
signatory.full_name
signatory.rank
```

Variable dictionary olmadan 40-50 əmr növü müştəri üçün qarışıq olacaq.

### 2.6. Placeholder Mapping Engine

DOCX içində placeholder-lar mərkəzi mapping ilə işləməlidir.

Nümunə:

```text
{{ employee.full_name }}
{{ employee.father_suffix }}
{{ order.given_date }}
{{ signatory.full_name }}
```

Mapping sistemi bilməlidir:

- hansı placeholder hansı field/data source-dan gəlir
- placeholder scalar-dır, yoxsa row/repeater içindədir
- hansı transform tətbiq edilir
- boş dəyər olanda fallback nədir
- publish zamanı placeholder coverage tamdırmı

### 2.7. Order Type Handler Pattern

Tam no-code sistem HR əmrləri üçün risklidir, çünki hüquqi və biznes side-effect-lər var.

Ona görə ən sağlam model `hybrid engine`-dir:

- form schema dinamik
- template dinamik
- variable mapping dinamik
- approval flow dinamik
- biznes təsirlər handler-lərdə kodla idarə olunur

Təklif olunan interface:

```php
interface OrderTypeHandler
{
    public function key(): string;

    public function schemaContext(): array;

    public function validate(array $payload): array;

    public function beforeCreate(array $payload): array;

    public function afterCreated(OrderLog $order, array $payload): void;

    public function afterApproved(OrderLog $order): void;

    public function variables(OrderLog $order): array;
}
```

Nümunə handler-lər:

- `HiringOrderHandler`
- `LeaveOrderHandler`
- `BusinessTripOrderHandler`
- `TransferOrderHandler`
- `TerminationOrderHandler`
- `RewardOrderHandler`

Handler yalnız biznes qaydanı idarə etməlidir. UI, validation render, template render, snapshot və audit engine-in işi olmalıdır.

### 2.8. Snapshot Model

Əmr yaradılan anda bütün kritik data snapshot saxlanmalıdır.

Snapshot sahələri:

- order type snapshot
- template version snapshot
- form schema snapshot
- selected personnel snapshot
- selected structure snapshot
- selected position snapshot
- signatory snapshot
- variable payload snapshot
- rendered text snapshot
- generated file path
- approval flow snapshot

Bu hüquqi baxımdan vacibdir. Məsələn, əməkdaş sonra başqa vəzifəyə keçsə də, köhnə əmrdə o vaxtkı vəzifə qalmalıdır.

### 2.9. Approval Flow Engine

Hər əmr növü üçün təsdiq axını fərqli ola bilər.

Məsələn:

- sadə HR təsdiqi
- rəhbər təsdiqi
- hüquq təsdiqi
- baş direktor imzası
- avtomatik təsdiq

Approval flow da dinamik model olmalıdır:

- flow template
- steps
- role/permission based approver
- personnel based approver
- structure based approver
- substitute/delegation support
- escalation rule

Başlanğıcda sadə status sistemi saxlanıla bilər, amma struktur approval engine-ə uyğun genişlənməlidir.

### 2.10. Generation Queue

DOCX/PDF generation ağırlaşdıqca request içində etməmək lazımdır.

Model:

- preview kiçik sənədlər üçün sync ola bilər
- final generation queue job ilə işləməlidir
- generation status göstərilməlidir
- error log saxlanmalıdır
- retry imkanı olmalıdır

Bu, 40-50 əmr növü və çox istifadəçi olanda sistemin sürətini qoruyacaq.

---

## 3. Müştəri Üçün İstifadə Ssenarisi

### 3.1. Əmr Yaratma

1. İstifadəçi `Əmr yarat` bölməsinə girir.
2. Əmr növünü seçir.
3. Sistem schema əsasında uyğun formu avtomatik açır.
4. İstifadəçi sahələri doldurur.
5. Sistem real vaxtda validation göstərir.
6. `Preview` basanda sənəd mətnini görür.
7. `Yadda saxla` və ya `Təsdiqə göndər` edir.
8. Sistem snapshot yaradır.
9. Final sənəd queue ilə generasiya olunur.
10. Əmr arxivdə saxlanır.

### 3.2. Şablon İdarəetmə

1. Admin `Əmr şablonları` bölməsinə girir.
2. Əmr tipini seçir.
3. Yeni DOCX şablon yükləyir.
4. Sistem placeholder-ları oxuyur.
5. Admin placeholder-ları variable dictionary ilə map edir.
6. Coverage yoxlanır.
7. Test preview edilir.
8. Şablon publish olunur.
9. Yeni əmrlər bu versiya ilə yaranır.
10. Köhnə əmrlər köhnə snapshot ilə qalır.

### 3.3. Yeni Əmr Növü Əlavə Etmə

1. `Order Type` yaradılır.
2. Schema sahələri əlavə edilir.
3. Lazımdırsa handler class yazılır.
4. DOCX template yüklənir.
5. Placeholder mapping edilir.
6. Preview test edilir.
7. Permission və approval flow bağlanır.
8. Publish edilir.

---

## 4. Mövcud Sistem Analizi

### 4.1. Mövcud Güclü Tərəflər

Layihədə artıq yaxşı başlanğıc var:

- `OrderTemplateRenderer` var.
- `TemplateRegistry` var.
- `OrderTemplateFormSchemaService` var.
- `order_template_*` cədvəlləri var.
- active version invariant var.
- template snapshot `order_logs`-da saxlanılır.
- generation log var.
- placeholder coverage və readiness command-ları var.
- metadata-driven row field-lər qismən işləyir.
- query budget testləri var.

Bu o deməkdir ki, sıfırdan başlamırıq. Mövcud işi “tam Order Engine” səviyyəsinə qaldırmaq lazımdır.

### 4.2. Mövcud Zəif Tərəflər

Hazırda əmr yaratma axınında hələ də legacy/hybrid hissələr var:

- `includes.order-action` hələ əsas form layout-u əl ilə qurur.
- `selectedBlade` ilə `default`, `vacation`, `business-trips` kimi branching qalır.
- `includes.order-templates.{blade}` ilə field render edilir.
- validation hələ `selectedBlade` əsasında match ilə ayrılır.
- `componentForms` və `selectedComponents` modeli köhnə component məntiqinə bağlıdır.
- biznes side-effect-lər `AddOrder::store()` içində qarışıq görünür.
- candidate import, vacation cleanup, personnel attach, confirmed service eyni transaction içində işləyir.
- template metadata mövcuddur, amma bütün form hələ 100% schema-driven deyil.

Bu struktur kiçik sayda əmr üçün işləyir. Amma 40-50 əmr növündə böyüdükcə çətinlik yaradacaq.

---

## 5. İndiki Template/Component Formadan Yeni Engine-ə Keçid

### 5.1. İndiki Model

Hazırkı məntiq:

- user order type seçir
- `templateSelected()` işləyir
- `selectedBlade` müəyyən olur
- component rows açılır
- `includes.order-templates.{selectedBlade}` render edilir
- `OrderValidationTrait` selectedBlade-ə görə validation verir
- `AddOrder::store()` create/sync/attach/confirm işlərini görür
- DOCX generate zamanı template snapshot istifadə olunur

Bu modelin yaxşı tərəfi odur ki, artıq real işləyən biznes axını var.

Pis tərəfi: yeni əmr növü gələndə çox yerdə dəyişiklik etmək lazım olur.

### 5.2. Hədəf Model

Yeni model:

- user order type seçir
- active template version + schema resolve edilir
- `OrderDynamicFormRenderer` schema-dan form yaradır
- validation schema-dan avtomatik çıxır
- payload normalize edilir
- `OrderCreatePipeline` order yaradır
- handler side-effect-ləri icra edir
- snapshot saxlanılır
- generation job sənədi yaradır

### 5.3. Dəyişməli Olan Əsas Hissələr

#### A. UI Render Layer

İndiki:

```blade
@include("includes.order-templates.{$selectedBlade}")
```

Hədəf:

```blade
<x-orders.dynamic-form
    :schema="$this->resolvedOrderSchema"
    wire-model-prefix="componentForms"
/>
```

Dəyişikliklər:

- `default.blade.php`, `vacation.blade.php`, `business-trips.blade.php` mərhələli olaraq metadata renderə keçirilməlidir.
- Field render üçün mərkəzi component map yaradılmalıdır.
- Repeater/row field-lər ayrıca idarə olunmalıdır.
- Dynamic form mobile/desktop layout eyni qaydada işləməlidir.

Risk:

- Mövcud template-lərin bəziləri specific layout istəyir.
- Bəzi order tiplərində eyni field başqa məna verə bilər.
- Legacy Blade-ləri birdən silmək olmaz.

Mitigation:

- Feature flag: hər order type üçün `render_mode = legacy|metadata|engine`.
- Əvvəl 3 əsas tip metadata ilə 100% stabil edilir.
- Sonra yeni order type-lər yalnız engine ilə əlavə olunur.

#### B. Validation Layer

İndiki:

```php
match ($this->selectedBlade) {
    Order::BLADE_DEFAULT => $this->defaultComponentRules(),
    Order::BLADE_VACATION => $this->vacationComponentRules(),
    Order::BLADE_BUSINESS_TRIP => $this->businessTripComponentRules(),
}
```

Hədəf:

```php
$rules = $schemaValidationResolver->rulesFor($orderType, $schema);
```

Dəyişikliklər:

- validation DB schema-dan gəlməlidir.
- label-lər translation key ilə gəlməlidir.
- row/repeater validation ayrıca dəstəklənməlidir.
- dependency validation lazımdır: məsələn, xarici ezamiyyətdə `description` required, daxili ezamiyyətdə başqa field required.

Risk:

- DB-də yanlış rule saxlanılsa form pozula bilər.
- Laravel validation syntax-ı müştəri üçün texniki ola bilər.

Mitigation:

- Admin UI-də rule builder olmalıdır, raw text yox.
- Kritik order tipləri üçün handler əlavə validation etməlidir.
- Publish zamanı schema validation smoke test işləməlidir.

#### C. Payload Normalization

İndiki:

- `componentForms`
- `component_ids`
- `personnel_ids`
- `attributes`
- `vacancy_list`

Hədəf:

```php
OrderDraftPayload {
    orderTypeId
    scalarFields
    rows
    actors
    snapshots
}
```

Dəyişikliklər:

- payload formadan gələn raw array-dən typed DTO-ya çevrilməlidir.
- dropdown field-lər yalnız ID yox, label snapshot ilə saxlanmalıdır.
- personnel/structure/position üçün selected label həmişə resolve edilməlidir.

Risk:

- Mövcud `order_log_component_attributes` strukturu ilə yeni payload arasında mapping lazımdır.

Mitigation:

- İlk mərhələdə köhnə cədvəllərə yazmaq saxlanılır.
- Paralel olaraq `order_payload_snapshot` JSON saxlanılır.
- Sonra oxuma/render yeni snapshot-dan istifadə etməyə keçir.

#### D. Store/Create Pipeline

İndiki:

`AddOrder::store()` içində:

- validation
- order log create
- component sync
- attribute save
- personnel attach
- candidate conversion
- vacation extra data
- confirmation side-effect

Hədəf:

```php
OrderCreatePipeline
    ->validate()
    ->normalize()
    ->snapshot()
    ->persist()
    ->runHandler()
    ->dispatchGeneration()
```

Dəyişikliklər:

- Livewire component yalnız orchestration etməlidir.
- Transaction pipeline service-də olmalıdır.
- Handler side-effect-lər ayrıca mərhələdə işləməlidir.

Risk:

- Hazırda bir neçə side-effect eyni transaction içindədir.
- Candidate -> personnel conversion həssasdır.
- Vacation order-lər əlavə cədvəllərlə bağlı ola bilər.

Mitigation:

- Əvvəl pipeline mövcud logic-i sadəcə daşısın, davranışı dəyişməsin.
- Sonra handler-lərə bölünsün.
- Hər əmr tipi üçün regression test yazılsın.

#### E. Template Variables

İndiki:

- field/mapping var, amma müştəri üçün tam variable catalog UI hələ ideal deyil.

Hədəf:

- variable dictionary
- variable group-lar
- autocomplete
- preview
- missing variable detector

Dəyişikliklər:

- `employee.*`, `order.*`, `signatory.*`, `company.*`, `dates.*` kimi namespace-lər hazırlanmalıdır.
- Handler-lər öz variable-larını registry-yə əlavə edə bilməlidir.

Risk:

- Eyni variable müxtəlif əmr tipində fərqli data verə bilər.

Mitigation:

- Global variables və order-type variables ayrılmalıdır.
- Publish zamanı variable availability yoxlanmalıdır.

#### F. Word/DOCX Generation

İndiki:

- `OrderTemplateRenderer` mövcuddur.
- snapshot/version var.

Hədəf:

- generation job
- preview render
- immutable final file
- retry/error diagnostics
- render duration metrics

Dəyişikliklər:

- Final generation queue ilə işləməlidir.
- Preview ilə final fərqli mode olmalıdır.
- Generated file path order log-a bağlanmalıdır.

Risk:

- Queue işləmirsə istifadəçi sənəd gözləyə bilər.

Mitigation:

- Kiçik sənədlər üçün fallback sync mode.
- Queue status panel.
- Failed job notification.

---

## 6. Əsas Risklər və Problem Yaşayacağımız Yerlər

### 6.1. Legacy Component Model

Hazırkı sistem `components`, `dynamic_fields`, `componentForms`, `order_log_component_attributes` məntiqinə bağlıdır.

Problem:

- Yeni schema modeli ilə köhnə component model üst-üstə düşməyə bilər.
- 40-50 order növündə component seçimi redundant ola bilər.

Həll:

- `component` anlayışı mərhələli olaraq `order field group` / `order line item` modelinə çevrilməlidir.
- Köhnə component-lər backward compatibility üçün saxlanmalıdır.

### 6.2. Template Şablonlarının Keyfiyyəti

Müştəri Word şablonunu düzgün hazırlamaya bilər.

Problem:

- placeholder səhv yazıla bilər
- eyni placeholder iki fərqli yerdə istifadə edilə bilər
- row/repeater strukturu düzgün qurulmaya bilər

Həll:

- template upload zamanı inspect
- coverage report
- preview required
- publish block
- friendly error messages

### 6.3. Dynamic Validation Təhlükəsi

Validation DB-dən gələndə səhv rule sistemi poza bilər.

Həll:

- raw Laravel rule yazdırmamaq
- rule builder
- allowed rule whitelist
- publish-time validation compile test

### 6.4. Business Side-Effect-lər

Məsələn:

- işə qəbul təsdiqlənəndə personnel yaranır
- məzuniyyət təsdiqlənəndə leave yaranır
- işdən azad təsdiqlənəndə status dəyişir
- daxili yerdəyişmədə position/structure dəyişir

Problem:

- Bunları no-code etmək təhlükəlidir.

Həll:

- handler pattern
- idempotent side-effect
- audit log
- rollback/compensation plan

### 6.5. Snapshot və Data Dəyişiklikləri

Əməkdaşın adı, vəzifəsi, strukturu dəyişə bilər.

Həll:

- order yarananda snapshot saxlanmalıdır.
- render həmişə snapshot-dan getməlidir.
- live data yalnız yeni order üçün istifadə edilməlidir.

### 6.6. Performance

40-50 order tipi, çoxlu template, çoxlu lookup, çoxlu variable resolve performansı zəiflədə bilər.

Həll:

- schema cache
- active template cache
- lookup lazy loading
- query budget test
- generation queue
- no N+1 guard

---

## 7. Mərhələli İcra Planı

### Current Implementation Status

- [x] Master plan sənədləşdirildi.
- [x] `OrderTypeHandler` kontraktı əlavə edildi.
- [x] `OrderDraftPayload` DTO əlavə edildi.
- [x] `OrderCreatePipeline` əlavə edildi.
- [x] `AddOrder::store()` create detalları pipeline arxasına keçirildi.
- [x] Rəhbər/imza snapshot məntiqi create pipeline daxilində qorundu.
- [x] `OrderValidationResolver` əlavə edildi.
- [x] Legacy `OrderValidationTrait` validation qaydalarını resolver-ə ötürən nazik adapterə çevrildi.
- [x] `OrderResolvedSchema` DTO əlavə edildi.
- [x] `OrderSchemaResolver` əlavə edildi və form schema resolve üçün mərkəzi typed giriş nöqtəsi quruldu.
- [x] `OrderCrud::refreshTemplateFormSchema()` birbaşa schema service-dən ayrılıb resolver üzərindən işləyir.
- [x] `DefaultOrderTypeHandler` və `OrderTypeHandlerRegistry` əlavə edildi.
- [x] `OrderCreatePipeline` artıq əmr tipi handler hook-larını çağırır; hazır handler-lər no-op olduğu üçün legacy davranış dəyişmir.
- [x] `OrderPrintPayloadFactory` handler variables-ları scalar print payload-a merge edir.
- [x] `OrderUpdatePipeline` əlavə edildi və `EditOrder::store()` update detallarını service qatına ötürdü.
- [x] `OrderTypeHandler` update hook-ları ilə genişləndirildi.
- [x] `order_types` üçün engine metadata migration əlavə edildi: `code`, `handler_class`, `is_active`, `meta`.
- [x] Mövcud order type-lar üçün stabil `code` backfill məntiqi əlavə edildi.
- [x] `OrderVariableRegistry` əlavə edildi və əsas əmr/personnel/tarix dəyişən kataloqu config-lə mərkəzləşdirildi.
- [x] `OrderSnapshotBuilder` əlavə edildi; rəhbər/imza və template snapshot payload-u create/update pipeline-larında mərkəzləşdirildi.
- [x] Mövcud Orders regression testləri keçdi.

### Phase 0: Mövcud Axını Dondur və Ölç

Məqsəd: indiki sistemin davranışını qorumaq.

Tasklar:

- [ ] bütün hazır order tipləri üçün regression test siyahısı çıxart
- [ ] create/edit/approve/print flow-ları sənədləşdir
- [ ] current query budget baseline ölç
- [ ] hazır DOCX output smoke testləri genişləndir

Exit criteria:

- Refactor zamanı nəyi pozduğumuzu testlər göstərir.

### Phase 1: Engine Contract-ları

Tasklar:

- [x] `OrderTypeHandler` interface yarat
- [x] `OrderDraftPayload` DTO yarat
- [x] `OrderCreatePipeline` skeleti yarat
- [x] `OrderSchemaResolver` yarat
- [x] `OrderValidationResolver` yarat
- [x] `OrderVariableRegistry` yarat
- [x] `OrderSnapshotBuilder` yarat

Exit criteria:

- Hələ davranış dəyişmir, amma yeni arxitektura kontraktları hazırdır.

### Phase 2: Current Flow-u Pipeline Arxasına Keçir

Tasklar:

- [x] `AddOrder::store()` logic-i pipeline service-ə daşı
- [x] `EditOrder::store()` logic-i pipeline service-ə daşı
- [x] Livewire component-i orchestration-only et
- [x] mövcud `default`, `vacation`, `business-trips` davranışı dəyişmədən saxla
- [x] regression testləri keçir

Exit criteria:

- İstifadəçi fərq hiss etmir, kod daha idarəolunan olur.

### Phase 3: Dynamic Form Renderer

Tasklar:

- [ ] mərkəzi `orders.dynamic-field` component yarat
- [ ] mərkəzi `orders.dynamic-form` component yarat
- [ ] field type -> UI component map yarat
- [ ] validation error display vahidləşdir
- [ ] row/repeater field dəstəyi əlavə et
- [ ] `includes.order-templates.*` üçün metadata fallback qur

Exit criteria:

- Ən azı 1 real order type tam schema-driven form ilə işləyir.

### Phase 4: Existing Types Migration

Tasklar:

- [ ] default order metadata tamamlansın
- [ ] vacation metadata tamamlansın
- [ ] business trip metadata tamamlansın
- [ ] hər biri üçün create/print regression test
- [ ] legacy blade fallback flag altında saxlanılsın

Exit criteria:

- Mövcud əsas tiplər engine ilə işləyir.

### Phase 5: Variable Dictionary + Template Studio

Tasklar:

- [ ] variable groups hazırla
- [ ] variable label translation-ları əlavə et
- [ ] placeholder mapping UI-ni müştəri dili ilə sadələşdir
- [ ] preview panel əlavə et
- [ ] publish gate-ləri sərtləşdir

Exit criteria:

- Müştəri/developer olmayan admin yeni template mapping-i qarışdırmadan edə bilir.

### Phase 6: Handler-lər

Tasklar:

- [ ] `HiringOrderHandler`
- [ ] `LeaveOrderHandler`
- [ ] `BusinessTripOrderHandler`
- [ ] `TransferOrderHandler`
- [ ] `TerminationOrderHandler`
- [ ] side-effect idempotency testləri

Exit criteria:

- Biznes təsirlər Livewire-dan çıxıb handler-lərdə idarə olunur.

### Phase 7: Async Generation

Tasklar:

- [ ] `GenerateOrderDocumentJob`
- [ ] generation status
- [ ] retry/error log
- [ ] final file archive
- [ ] preview/final mode ayrımı

Exit criteria:

- Ağır DOCX generation request-i bloklamır.

### Phase 8: New Order Onboarding Workflow

Tasklar:

- [ ] order type create wizard
- [ ] schema builder
- [ ] template upload
- [ ] placeholder detect
- [ ] variable mapping
- [ ] preview
- [ ] publish
- [ ] permission/approval binding

Exit criteria:

- Yeni order type minimum developer işi ilə sistemə əlavə edilir.

---

## 8. Prioritet Sırası

1. `AddOrder::store()` logic-i pipeline-a daşımaq.
2. `OrderTypeHandler` kontraktı yaratmaq.
3. Dynamic field renderer-i mərkəzləşdirmək.
4. Validation resolver-i schema-driven etmək.
5. Existing 3 order tipini schema-driven etmək.
6. Variable dictionary UI-ni gücləndirmək.
7. Async generation job əlavə etmək.
8. Yeni order type onboarding wizard-ı tamamlamaq.

---

## 9. Qəbul Meyarları

Sistemi professional səviyyədə saymaq üçün bunlar olmalıdır:

- [ ] yeni order type əlavə etmək üçün yeni Blade faylı məcburi deyil
- [ ] yeni order type əlavə etmək üçün Livewire store logic dəyişmir
- [ ] form sahələri schema-dan gəlir
- [ ] validation schema-dan gəlir
- [ ] template placeholder coverage publish zamanı yoxlanır
- [ ] generated order snapshot dəyişməz qalır
- [ ] köhnə order köhnə template versiyası ilə açılır
- [ ] side-effect-lər handler-lərdədir
- [ ] final DOCX generation loglanır
- [ ] query budget testləri keçir
- [ ] customer-facing UI texniki termin göstərmir
- [ ] yeni template preview edilmədən publish olunmur

---

## 10. Qısa Texniki Nəticə

Bu layihə üçün ən doğru yol tam no-code yox, `hybrid metadata-driven Order Engine`-dir.

Səbəb:

- HR əmrlərində hüquqi mətn, snapshot və audit vacibdir.
- Bəzi əmrlər real data dəyişir, bunu DB config ilə kor-koranə etmək olmaz.
- Müştəri rahat template və field idarə etməlidir.
- Developer yalnız ciddi biznes qayda gələndə handler yazmalıdır.

Yəni hədəf budur:

```text
Order Type -> Schema -> Dynamic Form -> Validation -> Handler -> Snapshot -> Template Render -> Approval -> Archive
```

Bu model 40-50+ əmr növündə sistemi ağırlaşdırmadan, idarəolunan və professional saxlayacaq.
