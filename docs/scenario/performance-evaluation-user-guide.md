# Performance Evaluation User Guide

Bu sənəd `Performance Evaluation` modulunu addım-addım izah edir. Məqsəd odur ki, modulu ilk dəfə açan HR əməkdaşı və ya rəhbər ekrandakı hər formun nə iş gördüyünü, daxil edilən məlumatın hara düşdüyünü və sonradan nəyə təsir etdiyini aydın başa düşsün.

## 1. Modul nə üçündür

Bu modul iki xətt üzrə işləyir:

1. forma əsaslı performans qiymətləndirməsi
2. test və bacarıq ölçümü

Sistemin verdiyi nəticə yalnız “bal” deyil. Buradan yaranan zəif sahələr avtomatik olaraq `Training Needs` moduluna düşə bilər.

## 2. Route və əsas tab-lar

- Route: `/performance-evaluation`
- Tab-lar:
  - `Xülasə`
  - `Dövrlər`
  - `Şablonlar`
  - `Qiymətləndirmələr`
  - `Testlər`

İdeal iş sırası:

1. dövr yarat
2. şablon yarat
3. şablon bölmələrini yarat
4. meyarları yarat
5. formanı əməkdaşa təyin et
6. qiymətləndirən ballarını daxil et
7. zəif nəticələri Training Needs-ə ötür
8. lazım olsa test bankı və test sessiyası qur
9. açıq cavabları review et

## 3. Xülasə tabı

Bu tab əməliyyat aparmaq üçün deyil, ümumi mənzərə üçündür.

Gördüyün sayğaclar:

- `Dövr`: neçə aktiv və ya arxiv qiymətləndirmə dövrü var
- `Şablon`: neçə forma şablonu var
- `Bölmə`: neçə şablon bölməsi var
- `Meyar`: neçə qiymətləndirmə kriteriyası var
- `Forma`: neçə real qiymətləndirmə forması təyin olunub
- `Bal`: neçə evaluator score daxil edilib
- `İnkişaf linki`: zəif score-dan neçə training need yaranıb
- `Test bankı / sessiya / cəhd`: test ölçmə qatının ümumi vəziyyəti

## 4. Dövrlər tabı

`Dövr` qiymətləndirmənin işlədiyi zaman pəncərəsidir.

### 4.1. Yeni dövr formu

Field-lər:

- `Dövr adı`
  - nümunə: `2026 illik qiymətləndirmə`
  - bu ad sonradan form listələrində və report-larda görünür
- `Dövr növü`
  - `İllik`, `Tədris ili`, `Rüblük`
  - bu sadəcə period növünü semantik olaraq göstərir
- `Status`
  - `Qaralama`: hələ işlənir
  - `Aktiv`: istifadə üçün açıqdır
  - `Bağlı`: artıq yeni əməliyyat üçün istifadə edilmir
- `Başlanğıc tarixi`
- `Bitiş tarixi`
  - bu tarix aralığı dövrün hansı müddəti əhatə etdiyini göstərir
- `Açıqlama`
  - HR qeydləri və dövrün məqsədi
- `Formanı avtomatik yarat`
  - aktivdirsə, gələcəkdə sistemin bu dövr üçün avtomatik forma yaratması nəzərdə tutulur

### 4.2. Yadda saxlandıqda nə olur

Məlumat `performance_cycles` cədvəlinə düşür.

Bu dövr daha sonra:

- `Form təyinatı` hissəsində seçilir
- `Test sessiyası` hissəsində seçilir
- `Son formalar` və digər listlərdə görünür

## 5. Şablonlar tabı

Bu tab qiymətləndirmə skeleton-unu qurur. Burada üç qat var:

1. `Şablon`
2. `Şablon bölməsi`
3. `Meyar`

### 5.1. Şablon nədir

Şablon bütün formanın əsas konteyneridir.

Məsələn:

- `Akademik heyət əsas şablonu`
- `İnzibati heyət şablonu`

### 5.2. Yeni şablon formu

Field-lər:

- `Şablon adı`
  - insanın görəcəyi əsas addır
- `Şablon kodu`
  - qısa identifikator
  - nümunə: `PE-AC-01`
  - eyni adda çox şablon olsa belə seçimi asanlaşdırır
- `Açıqlama`
  - şablonun kim üçün və nə məqsədlə yaradıldığını izah edir
- `Aktivdir`
  - aktiv olmayan şablon saxlanır, amma yeni istifadə üçün üstün seçim olmur

### 5.3. Şablon yaradıldıqdan sonra harada görünür

Şablon saxlanandan sonra bunlarda görünür:

- `Xülasə` tabındakı `Son şablonlar`
- `Şablonlar` tabındakı `Son şablonlar`
- `Şablon bölməsi` formundakı `Şablon` select-i
- `Form təyinatı` hissəsindəki `Şablon` select-i
- `Son formalar` listəsində təyin olunmuş formanın altında

`Açıqlama` da artıq `Son şablonlar` kartında görünür.

### 5.4. Şablon bölməsi nədir

Bölmə, şablon daxilində böyük məna blokudur.

Nümunələr:

- `Pedaqoji fəaliyyət`
- `Yaradıcılıq və inkişaf`
- `İntizam`

### 5.5. Şablon bölməsi formundakı field-lər

- `Şablon`
  - bu bölmənin hansı şablona aid olduğunu seçir
- `Bölmə adı`
  - formda görünəcək başlıqdır
- `Nəticəyə təsir (%)`
  - bu bölmənin yekun nəticədə neçə faiz pay daşıdığını göstərir
  - məsələn `60` yazılırsa, yekun nəticənin 60%-i bu bölmədən gəlir
- `Sıra`
  - UI-da və hesablamada bölmələrin hansı ardıcıllıqla görünəcəyini təyin edir
  - kiçik rəqəm əvvəl gəlir
  - bu sahə yalnız görünüş sırası üçündür, bala birbaşa təsir etmir

### 5.6. Bölmə yaradıldıqda nə olur

Məlumat `performance_form_template_sections` cədvəlinə düşür.

Bu bölmə sonra:

- `Meyar` formundakı `Bölmə` select-ində görünür
- həmin şablona aid meyarların konteyneri olur
- çəkisinə görə yekun score hesabında rol oynayır

### 5.7. Meyar nədir

Meyar ölçülən konkret kriteriyadır.

Nümunə:

- `Tədris metodikası`
- `Etik qaydalara riayət`
- `Komanda işi`

### 5.8. Meyar formundakı field-lər

- `Bölmə`
  - meyarın hansı bölməyə aid olduğunu göstərir
- `Kompetensiya`
  - bu meyarı Training Needs dünyası ilə bağlayır
  - zəif score yarananda training need məhz bu kompetensiya üzərindən yaranır
  - bu sahə boş qalsa training need inteqrasiyası işləməyəcək; buna görə meyarı kompetensiyasız saxlamaq düzgün deyil
- `Meyar adı`
  - evaluator formda bunu görəcək
- `Nəticəyə təsir (%)`
  - həmin bölmə daxilində meyarın nə qədər pay daşıdığını göstərir
  - bu dəyər yekun bal hesablamasında istifadə olunur
- `Aşağı bal həddi`
  - score bu həddin altına düşərsə sistem bunu zəif nəticə sayır
  - zəif nəticə `Training Needs` moduluna need yarada bilər
- `Şərh tələb edir`
  - aktivdirsə evaluator bu meyara bal verəndə izah yazmalıdır

### 5.9. Meyar yaradıldıqda nə olur

Məlumat `performance_form_template_items` cədvəlinə düşür.

Bu item sonra:

- `Evaluator balı` hissəsində seçilir
- təyin olunmuş formaya score girişi üçün istifadə olunur
- zəif nəticədə `performance_training_need_links` və `training_need_items` yarada bilir
- `Son meyarlar` listində adı, bağlı olduğu section/template, kompetensiyası, `Nəticəyə təsir` və `Aşağı hədd` ilə görünür

## 6. Qiymətləndirmələr tabı

Bu tabda iki əsas hissə var:

1. `Form təyinatı`
2. `Evaluator balı`

### 6.1. Form təyinatı nədir

Bu, seçilmiş dövr və şablonu konkret əməkdaşa bağlayır.

### 6.2. Form təyinatı field-ləri

- `Dövr`
  - form hansı dövr daxilində işləyəcək
- `Şablon`
  - hansı skeleton istifadə olunacaq
- `Əməkdaş`
  - kim qiymətləndirilir
- `Rəhbər`
  - manager evaluator
- `İR reviewer`
  - HR tərəfdən yoxlayan şəxs

### 6.3. Yadda saxlandıqda nə olur

Məlumat `performance_forms` cədvəlinə düşür.

Bu form:

- `Son formalar` listəsində görünür
- `Evaluator balı` formundakı `Qiymətləndirmə forması` select-ində görünür
- sonradan self/manager/hr score-larını daşıyır

### 6.4. Evaluator balı formu

Field-lər:

- `Qiymətləndirmə forması`
- `Meyar`
- `Qiymətləndirən`
  - `Özünüqiymətləndirmə`, `Rəhbər`, `İR`
- `Bal`
- `Şərh`

### 6.5. Score daxil ediləndə nə olur

Məlumat `performance_form_scores` cədvəlinə düşür.

Sistem sonra:

- həmin formanın uyğun statusunu `submitted` edə bilər
- meyarın aşağı həddi ilə score-u müqayisə edir
- score həddən aşağıdırsa `Training Needs` moduluna need yaradır
- əvvəllər yaranmış auto need varsa və score düzələrsə həmin linki təmizləyir
- meyar kompetensiyaya bağlıdırsa bu score `performance_training_need_links` və `training_need_items` yarada bilər

Əgər meyar kompetensiyaya bağlanmayıbsa:

- bal yadda saxlanır
- evaluator statusu yenilənir
- amma avtomatik təlim ehtiyacı yaranmır

### 6.6. Son formalar kartı necə oxunur

Hər kartda 4 status badge-i ola bilər:

- `Özünüqiymətləndirmə`
- `Rəhbər`
- `İR`
- `Yekun`

İlk 3 badge evaluator axınının vəziyyətidir:

- `Qaralama`: həmin evaluator hələ bal daxil etməyib
- `Təqdim edilib`: həmin evaluator balı artıq saxlayıb

`Yekun` badge-i isə ümumi nəticə kateqoriyasıdır:

- `Yüksək`
- `Orta`
- `Zəif`

Bu badge evaluator statusu deyil; form üzrə hesablanmış final nəticədir.

## 6.7. Mənim qiymətləndirmələrim

Bu ayrıca evaluator iş sahəsidir. Rəhbər və ya İR reviewer özünə düşən işləri burada idarə edir.

Buradakı əsas hissələr:

- `Axtarış`
- `Rol filtri`
- `Status filtri`
- `Qiymətləndirmə forması`
- `Meyar`
- `Bal`
- `Şərh`

İş prinsipi:

1. soldakı kartlardan formanı tapırsan
2. `Bal formunu aç` düyməsi ilə formanı sağdakı form-a doldurursan
3. meyar seçirsən
4. bal və şərh yazırsan
5. `Balı yadda saxla` ilə submit edirsən

Açıq cavab review hissəsi də eyni məntiqlə işləyir:

1. solda gözləyən cavabı tapırsan
2. `Yoxlama formasını aç` edirsən
3. sağda review balı və rəy yazırsan
4. `Cavabı yoxla` ilə yekunlaşdırırsan

Bu workspace tam report ekranı deyil.

- təyin olunmuş formalar: maksimum `24`
- gözləyən açıq cavablar: maksimum `24`

Yəni bu hissə gündəlik iş qutusu kimidir. Tam siyahı və arxiv üçün export/print hissəsi istifadə olunur.

Əlavə olaraq `Tam siyahılar` tab-ı artıq ayrıca list/detail workspace-dir:

- formalar
- şablonlar
- meyarlar
- test cəhdləri
- zəif sahə linkləri

burada search, filter, pagination və detail panel ilə görünür. Dashboard kartları qısa xülasə üçündür, tam siyahı üçün bu tab istifadə olunur.

### 6.6. `Son formalar` kartındakı badge-lər nə deməkdir

Bu kartda adətən 4 badge görünür:

- 1-ci badge: `self_status`
  - əməkdaş özünüqiymətləndirmə hissəsini doldurubsa `Təqdim edilib`
  - doldurmayıbsa `Qaralama`
- 2-ci badge: `manager_status`
  - rəhbər hissəsi doldurulubsa `Təqdim edilib`
  - doldurulmayıbsa `Qaralama`
- 3-cü badge: `hr_status`
  - HR reviewer hissəsi doldurulubsa `Təqdim edilib`
  - doldurulmayıbsa `Qaralama`
- 4-cü badge: `final_category`
  - yekun nəticə kateqoriyasıdır
  - `Yüksək`, `Orta`, `Zəif` kimi görünür

Yəni ekranda `2 qaralama + 1 təqdim edilib + 1 yüksək` görürsünüzsə, bu o deməkdir:

- `Özünüqiymətləndirmə`, `Rəhbər`, `İR` badge-lərindən ikisi hələ `Qaralama` vəziyyətindədir
- həmin evaluator-lar hələ öz score-larını tamamlamayıb
- evaluator-lardan biri artıq score göndərib və statusu `Təqdim edilib` olub
- sistemin hazırkı yekun kateqoriyası `Yüksək` çıxıb

Bu badge-lər ayrı-ayrı mənalar daşıyır; 4 badge eyni status deyil.

### 6.7. Aşağı score veriləndə konkret nə baş verir

Əgər score meyarın `Aşağı bal həddi` dəyərindən aşağıdırsa:

1. həmin meyar zəif nəticə kimi qəbul olunur
2. meyara bağlı `Kompetensiya` varsa, sistem onu Training Needs ilə əlaqələndirir
3. `training_need_items` cədvəlində avtomatik ehtiyac yarana bilər
4. `performance_training_need_links` cədvəlində bu zəif nəticə ilə yaranmış need arasında link yazılır
5. həmin ehtiyac sonradan:
   - plan board-da görünə bilər
   - smart suggestion-a düşə bilər
   - illik təlim planına daxil edilə bilər

Əgər sonradan score düzəlirsə və artıq həddin üstündədirsə, sistem auto-created link-i təmizləyir.

## 7. Testlər tabı

Bu hissə skill measurement üçündür.

### 7.1. Test bankı

Field-lər:

- `Test bankı adı`
- `Test bankı kodu`
- `Keçid balı (%)`
- `Müddət (dəqiqə)`
- `Açıqlama`
- `Aktivdir`

Yadda saxlananda:

- `performance_test_banks`

### 7.2. Sual bankı

Field-lər:

- `Test bankı`
- `Kompetensiya`
- `Sual növü`
  - `Çoxseçimli`
  - `Açıq cavab`
  - `Praktiki case study`
  - `Situasiya əsaslı`
- `Sual mətni`
- `Maksimum bal`
- `Sıra`
- `Variantlar`

`Variantlar` yalnız çoxseçimli sual üçün praktik olaraq vacibdir.

### 7.3. Test sessiyası

Field-lər:

- `Dövr`
- `Test bankı`
- `Əməkdaş`
- `Reviewer`
- `Plan tarixi`
- `Son tarix`
- `Keçid balı`
- `Müddət`
- `Maksimum cəhd`
- `Status`

Yadda saxlananda:

- `performance_test_sessions`

### 7.4. Cavab toplama

Field-lər:

- `Test sessiyası`
- `Sual`
- `Cəhd nömrəsi`
- `Variant`
- `Cavab mətni`

Burada cavablar `performance_test_attempt_answers` cədvəlinə yazılır.

### 7.5. Cəhdi yekunlaşdır

Bu hissə seçilmiş cəhdi bağlayır. Yekunlaşdırmadan sonra sistem:

- auto-score olunan sualları hesablayır
- ümumi faiz çıxarır
- pass/fail nəticəsi yaradır
- zəif competency nəticələri varsa need yarada bilir

### 7.6. Açıq cavab yoxlaması

Burada reviewer açıq cavablara əl ilə bal verir.

Field-lər:

- `Cavab`
- `Yoxlama balı`
- `Rəy`

Review bitəndən sonra session nəticəsi tamamlanır.

## 8. Training Needs ilə inteqrasiya

Bu modul iki yoldan training need yarada bilir:

1. `Evaluator balı` aşağı həddin altına düşəndə
2. test nəticəsi competency threshold-dan aşağı olanda

Bu zaman need aşağıdakı məntiqlə yaranır:

- kompetensiya item və ya sualdan götürülür
- item və ya sual kompetensiyaya bağlanmayıbsa training need yaranmır
- personel form və ya session-dan götürülür
- mənbə:
  - `performance_gap`
  - `skill_gap`

## 9. Tipik iş ssenarisi

### Ssenari 1: İllik qiymətləndirmə

1. HR `Dövr` yaradır
2. HR `Şablon` yaradır
3. HR `Bölmə` və `Meyar` qurur
4. HR formu əməkdaşa təyin edir
5. Rəhbər bal verir
6. Zəif meyar varsa system training need yaradır
7. HR həmin need-i `Training Needs` modulunda planlaşdırır

### Ssenari 2: Testlə skill gap aşkar edilir

1. HR test bankı yaradır
2. sualları və kompetensiya bağlarını yazır
3. test sessiyası açır
4. əməkdaş cavab verir
5. reviewer açıq cavabları yoxlayır
6. sistem yekun nəticə çıxarır
7. zəif competency varsa training need yaradır

## 10. Praktik qeydlər

- Şablon yaratdıqdan sonra onu dərhal:
  - `Şablon bölməsi`
  - `Form təyinatı`
  hissələrində seçə bilərsən.
- `Açıqlama` boş saxlanıla bilər, amma şablon çoxaldıqda qarışıqlığın qarşısını alır.
- `Nəticəyə təsir (%)` və `Sıra` təsadüfi sahələr deyil:
  - `Nəticəyə təsir (%)` hesablamaya təsir edir
  - `Sıra` görünüş və axına təsir edir
- `Aşağı bal həddi` Training Needs ilə inteqrasiyanın trigger sahəsidir.
