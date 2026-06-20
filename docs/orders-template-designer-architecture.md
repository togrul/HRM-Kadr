# Order Template Designer Architecture

Bu sənəd göndərilən real DOCX nümunələrinə əsasən əmr şablonlarının gələcəkdə manual Word asılılığı olmadan idarə olunması üçün təklif olunan professional arxitekturanı təsvir edir.

Nümunələr:

- Başqa işə keçirilmə
- Hərbi toplantı
- Maddə ilə xitam
- Məzuniyyət
- Ödənişsiz məzuniyyət
- Təhsil məzuniyyəti

## 1. Nümunələrdən Çıxan Real Pattern

Göndərilən sənədlərin hamısı cədvəlsiz, paraqraf əsaslı əmrlərdir. Struktur dəyişsə də, əsas skelet eynidir:

1. Təşkilat adı
2. ƏMR başlığı
3. Əmr nömrəsi
4. Şəhər və tarix
5. Əmr mövzusu
6. Hüquqi və ya faktiki giriş əsası
7. `Əmr edirəm:`
8. Nömrələnmiş qərar bəndləri
9. Əsas sənəd/səbəb
10. İmza sahibi və vəzifəsi

Fərqlənən hissələr:

- Əmr mövzusu
- Hüquqi əsas maddəsi
- Əməkdaş haqqında cümlə
- Müddət/tarix bəndləri
- Mühasibat/HR icra bəndləri
- Əsas sənəd cümləsi
- İmza sahibi

Bu o deməkdir ki, sistemi Word faylı ətrafında deyil, **blok əsaslı əmr mətn modeli** ətrafında qurmalıyıq.

## 2. Əsas Qərar

Word əsas redaktə yeri olmamalıdır.

Doğru model:

- İstifadəçi sistemdə template designer ilə blokları idarə edir.
- Sistem həmin bloklardan canlı preview yaradır.
- Final çıxış kimi DOCX generasiya olunur.
- Word yalnız export formatı qalır.

Beləliklə yeni əmr növü gələndə developer hər dəfə yeni Blade/Livewire/Word manual placeholder işi görmür.

## 3. Hədəf Sistem: Template Designer

Template designer üç hissədən ibarət olmalıdır:

### 3.1. Sol Panel: Blok Kitabxanası

Standart bloklar:

- Header block
- Order number/date block
- Subject block
- Legal basis paragraph
- Free paragraph
- Employee decision clause
- Leave period clause
- Transfer clause
- Termination clause
- Accounting/HR execution clause
- Copy/distribution clause
- Basis clause
- Signature block
- Page break
- Conditional block
- Repeater block

### 3.2. Orta Panel: Visual Preview

İstifadəçi Word açmadan görməlidir:

- Əmr kağız üzərində necə görünəcək
- Hansı bənd harada dayanır
- Əməkdaş adı/struktur/vəzifə necə dolur
- Tarixlər necə formatlanır
- İmza bloku necə çıxır

Preview real sample data ilə işləməlidir.

### 3.3. Sağ Panel: Blok Ayarları

Seçilən blok üçün:

- mətn redaktəsi
- variable picker
- required/optional status
- conditional rule
- numbering rule
- spacing/alignment
- role/permission guard
- sample value

## 4. Data Model

Mövcud `order_template_versions`, `order_template_fields`, `order_template_mappings` saxlanılır. Üstünə layout/blok modeli əlavə olunmalıdır.

Təklif olunan yeni cədvəllər:

### `order_template_blocks`

```text
id
order_template_version_id
parent_id nullable
block_key
block_type
title
content
sort_order
is_required
is_repeatable
condition_config json
layout_config json
data_config json
created_at
updated_at
```

### `order_template_block_variables`

```text
id
order_template_block_id
variable_key
label
fallback_value
format_config json
created_at
updated_at
```

### `order_template_previews`

```text
id
order_template_version_id
sample_payload json
html_preview longtext
docx_preview_path nullable
status
error_message nullable
created_at
```

## 5. Template DSL

Hər şablon versiyası arxada JSON DSL kimi saxlanmalıdır.

Nümunə:

```json
{
  "blocks": [
    {
      "type": "header",
      "content": "“DİNÇER VƏ CARÇIOĞLU” BİRGƏ MÜƏSSİSƏSİ"
    },
    {
      "type": "subject",
      "content": "Əmək məzuniyyətinin verilməsi haqqında"
    },
    {
      "type": "paragraph",
      "content": "Azərbaycan Respublikası Əmək Məcəlləsinin {{ legal.article }} maddəsini rəhbər tutaraq"
    },
    {
      "type": "command_intro",
      "content": "Əmr edirəm:"
    },
    {
      "type": "numbered_clause",
      "content": "{{ employee.full_name }} {{ employee.structure }} strukturunda {{ employee.position }} vəzifəsində çalışdığına görə {{ leave.days }} təqvim günü müddətində məzuniyyətə buraxılsın."
    },
    {
      "type": "basis",
      "content": "Əsas: {{ basis.text }}"
    },
    {
      "type": "signature",
      "mode": "active_signatory"
    }
  ]
}
```

Bu DSL-dən iki renderer istifadə edəcək:

- `HtmlOrderTemplatePreviewRenderer`
- `DocxOrderTemplateRenderer`

## 6. Variable Registry

Variable-lər istifadəçiyə texniki adla göstərilməməlidir.

Müştəri belə görməlidir:

- Əməkdaşın tam adı
- Ata adı şəkilçisi
- Struktur
- Vəzifə
- İş ili
- Başlama tarixi
- Bitmə tarixi
- İşə başlama tarixi
- Əsas sənəd
- Əmr nömrəsi
- İmza sahibi
- İmza sahibinin vəzifəsi

Arxada key-lər:

```text
employee.full_name
employee.father_suffix
employee.structure.name
employee.position.name
leave.work_year
leave.start_date
leave.end_date
leave.return_date
basis.text
order.number
signatory.full_name
signatory.position
```

Hər variable üçün saxlanmalıdır:

- key
- label
- category
- type
- formatter
- sample value
- allowed order types
- source resolver

## 7. DOCX Generation Strategy

Word-də manual `${content}` yazmaq uzunmüddətli doğru deyil. Yeni model:

1. Sistem universal DOCX skeleton saxlayır.
2. Designer block-ları HTML-like internal document model-ə çevirir.
3. DOCX generator həmin modeldən Word faylı yaradır.
4. Müştəri istəyirsə yalnız logo/header/footer kimi brand skeleton dəyişir.

Manual DOCX yalnız keçid dövründə dəstəklənməlidir.

### Keçid Dövrü

Hazırkı sistem üçün iki rejim saxlanır:

- `legacy_docx_placeholder`: mövcud `${content}` DOCX-lər
- `designer_layout`: yeni block designer-dən yaranan DOCX

Yeni əmr tipləri default olaraq `designer_layout` olmalıdır.

## 8. Order Type-lar Üçün Blok Preset-lər

### Məzuniyyət

Bloklar:

- legal basis paragraph
- employee leave clause
- leave dates clause
- accounting clause
- basis clause
- signature block

Lazım olan field-lər:

- personnel_id
- leave_type
- work_year_start
- work_year_end
- leave_start_date
- leave_end_date
- return_date
- leave_days
- basis_text

### Ödənişsiz Məzuniyyət

Əlavə field:

- unpaid_reason

### Təhsil Məzuniyyəti

Əlavə field-lər:

- education_institution
- course
- call_reference

### Başqa İşə Keçirilmə

Field-lər:

- personnel_id
- current_structure_id
- current_position_id
- new_structure_id
- new_position_id
- transfer_date
- basis_text

### Hərbi Toplantı

Field-lər:

- personnel_id
- military_body
- event_location
- start_date
- end_date
- calendar_days
- salary_preserved
- basis_text

### Maddə ilə Xitam

Field-lər:

- personnel_id
- termination_date
- labor_code_article
- violation_summary
- investigation_summary
- union_approval_reference
- damage_amount
- basis_text
- distribution_required
- control_retained

Bu field-lər UI-dən idarə oluna bilər, amma kritik hüquqi field-lər preset kimi seed olunmalıdır.

## 9. UX Flow

### Əmr Şablonu Yaratma

1. Əmr tipi seçilir.
2. Sistem uyğun preset təklif edir.
3. İstifadəçi blokları görür.
4. Mətni dəyişir və variable picker-dən dəyişən əlavə edir.
5. Preview görür.
6. Test payload ilə DOCX preview yaradır.
7. Publish edir.

### Əmr Yaratma

1. İstifadəçi əmr tipini seçir.
2. Sistem həmin template schema əsasında form yaradır.
3. Form doldurulur.
4. Canlı mətn preview görünür.
5. Təsdiq edilir.
6. Snapshot saxlanır.
7. DOCX generasiya olunur.

## 10. Publish Gate

Template publish olunmazdan əvvəl sistem bunları yoxlamalıdır:

- required block-lar var
- signature block var
- variable-lərin hamısı tanınır
- required field-lər form schema-da var
- conditional block-lar valid JSON rule-dur
- preview render uğurludur
- DOCX render uğurludur
- query/render budget limit daxilindədir

## 11. Niyə Bu Model Uzunmüddətli Daha Yaxşıdır

Köhnə modeldə:

- Word-də manual `${content}` yazılırdı
- Component-lər əl ilə qurulurdu
- Yeni əmr tipi gələndə developer işi artırdı
- Preview zəif idi
- Müştəri özü rahat idarə edə bilmirdi

Yeni modeldə:

- Əsas idarəetmə sistemdədir
- Word manual asılılığı minimumdur
- Yeni əmr tipi preset + block designer ilə qurulur
- Hər şey versionlanır
- Preview və DOCX eyni mənbədən yaranır
- Müştəri developer olmadan mətn və blok sırasını dəyişə bilir
- Biz isə handler/variable resolver səviyyəsində sistemi idarə edirik

## 12. Implementasiya Mərhələləri

### Mərhələ 1: Designer Data Layer

- `order_template_blocks`
- `order_template_block_variables`
- block repository/service
- migration və testlər

### Mərhələ 2: Block Renderer

- JSON DSL -> HTML preview
- sample payload generator
- variable validation

### Mərhələ 3: Designer UI

- block list
- preview panel
- block settings panel
- variable picker
- reorder

### Mərhələ 4: DOCX Generator

- designer layout -> DOCX
- universal skeleton
- paragraph/numbering/signature renderer

### Mərhələ 5: Preset Library

- leave preset
- unpaid leave preset
- education leave preset
- transfer preset
- military gathering preset
- termination by article preset

### Mərhələ 6: Migration Bridge

- mövcud metadata template-ləri `legacy_docx_placeholder` rejimində saxla
- yeni əmr tiplərini `designer_layout` rejimində yarat
- köhnə order snapshot-larını pozma

## 13. Qəti Tövsiyə

Əgər məqsəd 40-50+ əmr növünü qarışdırmadan böyütməkdirsə, Word faylını əsas redaktə aləti kimi saxlamamalıyıq.

Əsas məhsul `Order Template Designer` olmalıdır.

Word isə final, rəsmi, export olunan sənəd formatı kimi qalmalıdır.
