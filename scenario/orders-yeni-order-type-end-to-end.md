# Orders Scenario: Yeni `order_type` üçün Sıfırdan Tam Axın (DOCX + Metadata + Add/Edit/Print)

Bu sənədin məqsədi:
- Yeni verilən `order_type` + `.docx` şablonunu sistemə problemsiz əlavə etmək.
- Formun dinamik çıxması, mapping-lərin doğru qurulması və Word generation-un düzgün işləməsini təmin etmək.
- Prosesi başqa komanda üzvünə birbaşa təhvil verilə biləcək səviyyədə standartlaşdırmaq.
- Operativ no-legacy release/check üçün əlavə sənəd: `scenario/orders-no-legacy-operations-checklist.md`.

---

## 1. Qısa anlayış modeli (nə nəyə xidmət edir)

- `orders`:
  - Master şablon kartı (ad, blade, fayl və s.).
- `order_types`:
  - Eyni `order` daxilində biznes tip varyasiyaları.
- `order_template_sets`:
  - Hər `order_type` üçün metadata mühərrikinin konteyneri.
- `order_template_versions`:
  - Versiyalaşdırılmış metadata + DOCX bağlama nöqtəsi (`draft/published`, `is_active`).
- `order_template_fields`:
  - Formda görünən dinamik sahələr.
- `order_template_mappings`:
  - DOCX placeholder -> field key xəritəsi (`row` və ya `scalar` scope).
- `order_logs`:
  - Real yaradılmış əmrlər.

Hazır sistem metadata-only rejimə uyğundur (strict mode açıq olanda legacy fallback bloklanır).

---

## 2. Ön şərtlər (başlamadan əvvəl)

1. DOCX şablonda placeholder-lar standart formatda olmalıdır:
   - Məsələn: `$fullname`, `$rank`, `$day`, `$month`, `$year`, `$structure`, `$position`.
2. Placeholder adları sabit naming ilə verilməlidir.
3. Yeni type üçün hansı sahələrin `required` olduğu əvvəlcədən qərarlaşdırılmalıdır.
4. İstifadəçi icazələri:
   - template set/version idarəsi,
   - metadata/ui config idarəsi,
   - publish/rollback.

---

## 3. Sıfırdan icra addımları

## Addım 1: Master template kartını hazırla

UI-də Templates hissəsində yeni şablon yaradın və ya mövcud şablonu redaktə edin:
- `Ad`
- `Kateqoriya`
- `Səhifə (blade)` (`default`, `vacation`, `business-trips` whitelist)
- DOCX upload

Qeyd:
- `Model` sahəsi manual text yox, seçilə bilən/readonly biznes məna ilə olmalıdır.
- Fayl yüklənəndən sonra checksum görünməsi normaldır.

## Addım 2: Type yaradın və bağlayın

`order_types` daxilində yeni type əlavə edin (məs: “Yeni əmrin adı”).
Bu type müvafiq `order`-a bağlı olmalıdır.

## Addım 3: Onboarding Wizard ilə metadata axınını başlat

Templates ekranında **Template onboarding wizard** açın.

Step 1:
- Şablonu seçin.
- `Ensure sets` edin.
- Lazımdırsa `Open UI config`.

Step 2:
- `Order type` seçin.
- Mövcud aktiv versiya üzərində işləmək istəmirsinizsə `Create draft version`.
- DOCX faylı **seçilmiş versiyaya** upload edin.

Step 3:
- `Generate metadata + mappings` işlədin.
- `Run coverage` işlədin.
- Status blokunda aşağıdakılar 0 olmalıdır:
  - Missing placeholders
  - Orphan mappings

Step 4:
- Hər şey yaşıl olduqdan sonra `Publish version`.

---

## 4. UI Config-də dəqiq tənzimləmə (ən kritik hissə)

`Set Type > UI config` daxilində:

1. Field səviyyəsində:
- label
- key
- `required`
- input type (`text`, `number`, `date`, `select`, `lookup`, `tree-list` və s.)
- helper text / placeholder / default value

2. Lookup/select üçün:
- model
- `searchField`
- `selectedName`
- input parametrləri

3. Section blocks:
- `enabled`
- `sort`
- `title`

4. Mapping editor:
- placeholder
- field key
- scope (`row` / `scalar`)
- sort order

Qayda:
- `row` = komponent səviyyəsində təkrarlanan data.
- `scalar` = sənəd üzrə qlobal data (məs: direktor adı, ümumi tarix və s.).

---

## 5. Add Order / Edit Order / Print doğrulama ssenarisi

## Add Order
1. Yeni əmri seçilmiş type ilə açın.
2. Dynamic sahələri doldurun.
3. `required` olanlar boş buraxılanda validasiya xətası gəlməlidir.
4. Save edin.

## Edit Order
1. Yaradılmış order-i açın.
2. Sahələrdə dəyişiklik edin.
3. Save edin.
4. Yenidən açıb dəyərlərin persist olunduğunu yoxlayın.

## Print DOCX
1. Həmin order-i print edin.
2. Çıxan DOCX-də placeholder qalığı olmamalıdır.
3. Word faylı “recovery converter” xətası verməməlidir.

---

## 6. Command-larla texniki doğrulama (release checklist)

## Readiness
```bash
php artisan orders:templates:readiness --json
```
Gözlənilən:
- `metadata_ready` bütün aktiv type-ları əhatə edir.
- `version_without_mappings = 0`
- `legacy_form_blocked` və `legacy_print_blocked` strict rejimdə type sayı ilə uyğundur.

## Smoke
```bash
php artisan orders:templates:smoke --order-type=<ID> --json
```
Gözlənilən:
- `status: ok`
- `missing_placeholders: []`
- `orphan_mappings: []`
- `unresolved_after_render: []`

## Query budget (Add/Edit/Print probe)
```bash
php artisan orders:templates:query-budget --order-type=<ID> --json
```
Əgər həmin type üçün referans order yoxdursa:
```bash
php artisan orders:templates:query-budget --order-type=<ID> --order-no=<ORDER_NO> --json
```

## CI gate lokal yoxlama
```bash
composer ci:orders-template-gate
```

---

## 7. Tez-tez rastlanan problemlər və həll

## Problem A: `Active metadata template version not found`
Səbəb:
- Type üçün active+published versiya yoxdur.
Həll:
1. Draft yaradın.
2. DOCX attach edin.
3. Generate metadata + coverage.
4. Publish edin.

## Problem B: Missing placeholders / orphan mappings
Səbəb:
- DOCX placeholder-ları ilə mapping-lər sinxron deyil.
Həll:
1. Mapping editor-də placeholder-field uyğunluğunu düzəldin.
2. Yenidən coverage run edin.
3. 0/0 olana qədər publish etməyin.

## Problem C: Word açılmır (recovery converter xətası)
Səbəb:
- DOCX içində XML integrity pozuntusu və ya render nəticəsində unresolved token qalığı.
Həll:
1. `orders:templates:smoke` işlədin.
2. unresolved/missing siyahısını təmizləyin.
3. Təkrar print edin.

## Problem D: Query budget type üçün `reference_order_not_found`
Səbəb:
- Həmin order type ilə hələ real `order_logs` nümunəsi yoxdur.
Həll:
1. O type ilə bir order yaradın.
2. Yenidən query-budget probe edin.

---

## 8. “Done” kriteriyası (bu ssenari üçün)

Yeni order type yalnız o zaman “tam hazır” sayılır:
1. Active+published versiya var.
2. Coverage-də missing/orphan 0-dır.
3. Add/Edit validasiya və save doğru işləyir.
4. Print olunan DOCX placeholder-sız və açılan fayldır.
5. Query budget probe 3/3 pass edir.
6. CI quality gate yaşıl keçir.

---

## 9. Handover üçün qısa checklist

1. Şablon və type ID-ləri:
2. Aktiv versiya:
3. Son checksum:
4. Coverage nəticəsi:
5. Test olunmuş order_no:
6. Query budget nəticəsi:
7. CI gate nəticəsi:
8. Qalan risklər:

Bu 8 maddə doldurulmadan release etməyin.
