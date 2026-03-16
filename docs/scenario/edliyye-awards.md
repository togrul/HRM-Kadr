# Ədliyyə Awards Gap Analizi

## Məqsəd

Bu sənəd, müştərinin `Əməkdaşların Mükafatlarının İdarə Edilməsi` modulu ilə bağlı gözləntisini mövcud sistemdəki `awards` infrastrukturu ilə müqayisə edir.

Sənədin məqsədi:

- hazırkı vəziyyəti dəqiq sənədləşdirmək
- çatışmayan funksionallığı ayırmaq
- gələcək implementasiya üçün istinad sənədi yaratmaq

---

## Müştəri Tələbinin Qısa Xülasəsi

Müştəri aşağıdakı funksionallığı istəyir:

1. mükafatların qeydiyyatı
2. mükafat tarixçəsinin izlənməsi
3. mükafatların əməkdaş profili ilə əlaqələndirilməsi
4. mükafatların axtarışı və status üzrə izlənməsi
5. mükafatların arxivlənməsi
6. təqdimat zamanı bildiriş və e-poçt xəbərdarlığı

---

## Mövcud Vəziyyət

Hazırkı sistemdə `awards` tam modul kimi deyil, əsasən `personnel` modulunun daxilində əlaqəli məlumat kimi mövcuddur.

Mövcud əsas hissələr:

- `Personnel -> awards()` əlaqəsi var  
  Fayl: [Personnel.php](/Users/togruljalalli/Desktop/projects/HRM/app/Models/Personnel.php)

- `personnel_awards` cədvəli var  
  Fayllar:  
  [2023_09_11_232801_create_personnel_awards_table.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Personnel/Database/Migrations/2023_09_11_232801_create_personnel_awards_table.php)  
  [2024_11_18_154427_add_order_columns_to_personnel_awards_table.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Personnel/Database/Migrations/2024_11_18_154427_add_order_columns_to_personnel_awards_table.php)

- Personel wizard daxilində mükafat əlavə etmə formu var  
  Fayllar:  
  [AwardsPunishmentsForm.php](/Users/togruljalalli/Desktop/projects/HRM/app/Livewire/Forms/Personnel/AwardsPunishmentsForm.php)  
  [step6.blade.php](/Users/togruljalalli/Desktop/projects/HRM/resources/views/includes/step6.blade.php)

- `Award` və `AwardType` master data idarəçiliyi admin tərəfində var  
  Fayllar:  
  [Awards.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Admin/Livewire/Awards.php)  
  [admin awards.blade.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Admin/Resources/views/livewire/admin/awards.blade.php)

- Print / export səviyyəsində mükafat məlumatı istifadə olunur  
  Fayllar:  
  [page14-personnel.blade.php](/Users/togruljalalli/Desktop/projects/HRM/resources/views/prints/partials/page14-personnel.blade.php)  
  [PersonnelServiceBookWordExportService.php](/Users/togruljalalli/Desktop/projects/HRM/app/Services/PersonnelServiceBookWordExportService.php)

---

## Mövcud Data Model

### `awards`

Mükafat master məlumatı:

- `id`
- `award_type_id`
- `name`
- `is_foreign`

Fayl: [Award.php](/Users/togruljalalli/Desktop/projects/HRM/app/Models/Award.php)

### `personnel_awards`

Əməkdaşa bağlı mükafat məlumatı:

- `tabel_no`
- `award_id`
- `reason`
- `given_date`
- `is_old`
- `order_given_by`
- `order_no`
- `order_date`

Fayl: [PersonnelAward.php](/Users/togruljalalli/Desktop/projects/HRM/app/Models/PersonnelAward.php)

---

## Müştəri Tələbi ilə Müqayisə

### 1.1. Mükafatların Qeydiyyatı

**Status:** `Qismən var`

Sistemdə hazırda bunlar daxil edilə bilir:

- mükafatın özü (`award_id`)
- səbəb / əsaslandırma (`reason`)
- verilmə tarixi (`given_date`)
- əmr verən (`order_given_by`)
- əmr nömrəsi (`order_no`)
- əmr tarixi (`order_date`)

Çatışmayan hissələr:

- ayrıca `mükafat modulu` səviyyəsində giriş ekranı yoxdur
- `təqdim edən şəxs və ya qurum` semantikası ayrıca model olunmayıb
- maliyyə mükafatı, medal, sertifikat kimi tipoloji fərqlər yalnız reference səviyyəsində dolayı şəkildə idarə olunur

### 1.2. Mükafatların Tarixçəsinin İzlənməsi

**Status:** `Qismən var`

Var olan:

- əməkdaş üzrə award list saxlanılır
- print / xidmət kitabçası çıxışında göstərilir

Çatışmayan:

- ayrıca tarixçə ekranı yoxdur
- timeline görünüşü yoxdur
- date/type/presenter üzrə history search yoxdur

### 1.3. Mükafatların Əməkdaş Profili ilə Əlaqələndirilməsi

**Status:** `Var`

Bu hissə mövcuddur:

- mükafatlar `tabel_no` üzərindən əməkdaşa bağlanır
- personel profilində relation kimi yaşayır

Ancaq aşağıdakı hissə yoxdur:

- performans və karyera ilə bağlı analitik və ya qərar dəstəyi istifadəsi

### 1.4. Mükafatların Axtarışı və İzlənməsi

**Status:** `Zəif / qismən`

Var olan:

- ümumi personnel filter içində `award_id` ilə filtr var  
  Fayllar:  
  [Personnel.php](/Users/togruljalalli/Desktop/projects/HRM/app/Models/Personnel.php)  
  [Detail.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/UI/Livewire/Filter/Detail.php)

Çatışmayan:

- əməkdaş adı ilə awards register axtarışı
- mükafat növü ilə axtarış
- tarix aralığı ilə axtarış
- təqdim edən şəxs / qurum ilə axtarış
- status üzrə izləmə
- real-time status monitorinqi

### 1.5. Mükafatların Arxivlənməsi

**Status:** `Yox`

Var olan:

- məlumatlar DB-də saxlanılır
- export/print səviyyəsində istifadəsi var

Çatışmayan:

- ayrıca arxiv statusu
- archive ekranı
- attachment / sənəd saxlanması
- arxivdən retrieval/download funksiyası

### 1.6. Bildirişlər və Xəbərdarlıqlar

**Status:** `Yox`

Kod bazasında awards ilə bağlı mail və ya notification axını görünmür.

Mövcud observer yalnız dropdown cache təmizləyir:  
[AwardObserver.php](/Users/togruljalalli/Desktop/projects/HRM/app/Observers/AwardObserver.php)

---

## Əsas Gap-lər

### 1. Ayrı modul davranışı yoxdur

Hazırkı həll `personnel relation entry` səviyyəsindədir. Müştəri isə ayrıca idarəetmə modulu istəyir.

Lazım olacaq:

- `Awards Register`
- `Award Detail`
- `Award History`
- `Award Archive`

### 2. Status modeli yoxdur

Müştəri status izləmə istəyir, amma cari cədvəldə status saxlanmır.

Təklif olunan statuslar:

- `draft`
- `pending`
- `presented`
- `archived`
- `cancelled`

### 3. Təqdim edən şəxs/qurum ayrıca model olunmayıb

Hazırdakı `order_given_by` tam olaraq `presented_by` və ya `issuer_org` semantikasını qarşılamır.

Lazım ola biləcək sahələr:

- `presented_by_name`
- `presented_by_type`
- `presented_by_org`

### 4. Elektron arxiv yoxdur

Tələb yalnız DB record deyil. Elektron arxiv üçün lazımdır:

- sənəd upload
- attachment saxlanması
- archive index
- archive search
- archive retrieval

### 5. Bildiriş sistemi yoxdur

Lazımdır:

- award təqdim ediləndə notify
- status dəyişəndə notify
- e-poçt göndərişi

### 6. Awards analytics yoxdur

Müştərinin istədiyi profil əlaqəsi daha faydalı olsun deyə:

- employee profile summary
- performance / career review içində award göstəricisi
- reports modulunda awards hesabatları

---

## Lazım Olan Data Model Genişlənməsi

Mövcud `personnel_awards` cədvəlinə aşağıdakı sahələr əlavə edilməlidir:

- `status`
- `description`
- `justification`
- `presented_by_name`
- `presented_by_org`
- `presented_at`
- `archived_at`
- `attachment_path` və ya ayrıca `award_documents` cədvəli
- `created_by`
- `updated_by`

Alternativ olaraq ayrıca cədvəllər:

- `award_status_histories`
- `award_documents`
- `award_notifications`

---

## Tövsiyə Olunan UI Səthləri

### 1. Awards Register

Sütunlar:

- əməkdaş
- mükafat
- növ
- status
- verilmə tarixi
- təqdim edən
- struktur

Filtrlər:

- əməkdaş
- award type
- award
- status
- tarix aralığı
- structure

### 2. Award Detail

Detallar:

- mükafat adı
- növ
- əsaslandırma
- təqdim edən
- order info
- status tarixçəsi
- attachment-lər

### 3. Employee Profile Tab

Profil daxilində ayrıca `Mükafatlar` bölməsi:

- xülasə say
- son mükafat
- tam tarixçə

### 4. Archive Surface

- archived awards list
- advanced search
- sənəd aç / endir

---

## Tövsiyə Olunan Workflow

### Minimum workflow

1. award yaradılır
2. əməkdaşa bağlanır
3. status `presented` olur
4. profil/history-də görünür

### Tövsiyə olunan geniş workflow

1. `draft`
2. `pending`
3. `approved/presented`
4. notification göndərilir
5. attachment arxivə düşür
6. status history saxlanır

---

## Must-have / Should-have / Nice-to-have

### Must-have

- ayrıca awards register
- status sahəsi
- date/type/presenter filterləri
- employee profile history görünüşü
- notification eventləri

### Should-have

- archive ekranı
- attachment upload
- status history
- awards reports

### Nice-to-have

- performance/career linkage
- dashboard analytics
- approval workflow genişlənməsi

---

## Nəticə

Hazırkı sistemdə awards üçün təməl var:

- master data
- employee relation
- basic entry
- print/export istifadəsi

Amma müştərinin istədiyi modul səviyyəsi üçün bu kifayət etmir.

Cari vəziyyət təxminən:

- **mövcud təməl:** `35-45%`
- **çatışmayan əsas modul davranışı:** `55-65%`

Ən vacib növbəti addım:

1. `Awards Register` yaratmaq
2. `status` modelini əlavə etmək
3. `profile history + archive + notification` xəttini qurmaq
