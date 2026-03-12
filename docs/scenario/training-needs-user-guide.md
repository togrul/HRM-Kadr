# Training Needs User Guide

Bu sənəd `Training Needs` modulunu sıfırdan izah edir. Məqsəd odur ki, sistemi ilk dəfə açan HR əməkdaşı hər tabın nə iş gördüyünü, formda doldurulan məlumatın hara düşdüyünü və sonradan nəyə təsir etdiyini rahat anlaya bilsin.

## 1. Modulun ümumi məntiqi

Bu modul təkcə “təlim ehtiyacı siyahısı” deyil. Bütöv axın belədir:

1. kompetensiya və proqram kataloqu qurulur
2. vəzifə üçün tələb olunan kompetensiyalar yazılır
3. əməkdaşın mövcud səviyyəsi daxil edilir
4. ehtiyaclar yaranır
5. sistem ağıllı təkliflər verir
6. illik plan hazırlanır
7. HR plan item-ları review və təsdiq edir
8. approved item-lardan session proposal çıxır
9. sessiya yaradılır və keçirilir
10. iştirakçı, delivery və feedback bağlanır
11. nəticə report və analitikaya düşür

## 2. Route və tab-lar

- Route: `/training-needs`
- Tab-lar:
  - `Xülasə`
  - `Kataloqlar`
  - `Rol tələbləri`
  - `Profil və plan`
  - `İllik planlama`
  - `Təlim təqvimi`
  - `Nəticələr`
  - `Analitika`

## 3. Xülasə tabı

Bu tab əməliyyat aparmaq üçün deyil. Burada ümumi saylar görünür:

- qrup sayı
- kompetensiya sayı
- proqram sayı
- rol tələbi sayı
- profil sayı
- need sayı
- plan sayı
- plan item sayı
- session sayı
- delivery sayı
- feedback sayı

Bu hissə HR üçün “sistem nə vəziyyətdədir” sualının qısa cavabıdır.

Dashboard blokları tam report siyahısı deyil.

- çoxu limitli gəlir
- tipik limitlər `5`, `6`, `8`, user inbox-larda isə `24`-dür
- bu bloklarda hələ pagination yoxdur
- ekranın sonsuz uzanmaması üçün limit saxlanır
- tam siyahı üçün export və print summary hissəsi istifadə olunur

Yeni `Tam siyahılar` tab-ında isə need, plan item, session və delivery siyahıları ayrıca search, status filter, pagination və detail panel ilə görünür.

## 4. Kataloqlar tabı

Burada modulun təməli qurulur.

### 4.1. Kompetensiya qrupları

Field-lər:

- `Qrup adı`
- `Açıqlama`
- `Sıra`
- `Aktivdir`

Yadda saxlananda:

- `training_competency_groups`

Nümunə qruplar:

- Liderlik
- Texniki bacarıqlar
- Yumşaq bacarıqlar
- Tədris bacarıqları

### 4.2. Kompetensiya səviyyələri

Field-lər:

- `Səviyyə adı`
- `Bal`
- `Açıqlama`
- `Sıra`
- `Standartdır`

Yadda saxlananda:

- `training_levels`

`Bal` sonradan gap hesabı və suggestion score üçün istifadə olunur.

### 4.3. Kompetensiyalar

Field-lər:

- `Qrup`
- `Kompetensiya adı`
- `Açıqlama`
- `Məcburidir`
- `Aktivdir`

Yadda saxlananda:

- `training_competencies`

### 4.4. Təlim proqramları

Field-lər:

- `Proqram adı`
- `Proqram kodu`
- `Keçirilmə növü`
  - `Daxili`
  - `Xarici`
  - `Hibrid`
- `Saat`
- `Açıqlama`
- `Aktivdir`

Yadda saxlananda:

- `training_programs`

### 4.5. Təlim proqramı -> kompetensiya xəritəsi

Field-lər:

- `Təlim proqramı`
- `Kompetensiya`
- `Hədəf səviyyə`

Yadda saxlananda:

- `training_program_competency_map`

Bu xəritə sonradan belə işləyir:

- hansı proqram hansı boşluğu bağlayır
- smart recommendation hansı proqramı seçsin
- session proposal hansı proqram üzrə qurulsun

## 5. Rol tələbləri tabı

Bu tab “hansı vəzifə üçün hansı kompetensiya lazımdır?” sualının cavabıdır.

Field-lər:

- `Vəzifə`
- `Kompetensiya`
- `Tələb olunan səviyyə`
- `Prioritet`
- `Məcburidir`

Yadda saxlananda:

- `role_competency_requirements`

Bu məlumat:

- gap score
- suggestion score
- coverage analytics
- top gap positions

üçün əsas bazadır.

## 6. Profil və plan tabı

Bu tab iki hissədən ibarətdir:

1. `Əməkdaş kompetensiya profili`
2. `Təlim ehtiyacı sırası`

### 6.1. Əməkdaş kompetensiya profili

Field-lər:

- `Əməkdaş`
- `Kompetensiya`
- `Mövcud səviyyə`
- `Mənbə`
- `Son qiymətləndirmə tarixi`

`Mənbə` nə ola bilər:

- `manual`
- `manager_review`
- `hr_review`
- `exam`

Yadda saxlananda:

- `employee_competency_profiles`

Bu məlumat sonradan:

- tələb olunan səviyyə ilə müqayisə olunur
- gap ölçülür
- smart suggestion score üçün istifadə olunur

### 6.2. Təlim ehtiyacı sırası

Field-lər:

- `Əməkdaş`
- `Kompetensiya`
- `Tövsiyə olunan proqram`
- `Hədəf səviyyə`
- `Prioritet`
- `Mənbə`
- `Status`
- `Səbəb`
- `Plan qeydi`
- `Hədəf tamamlanma tarixi`

Yadda saxlananda:

- `training_need_items`

Need mənbələri:

- `manual`
- `manager_request`
- `employee_request`
- `manager_review`
- `hr_review`
- `performance_gap`
- `skill_gap`
- `exam`

Need statusları:

- `draft`
- `review`
- `approved`
- `planned`
- `completed`

## 7. İllik planlama tabı

Bu tab modulun ən vacib idarəetmə qatıdır. Burada:

1. illik plan yaradılır
2. sistem suggestion çıxarır
3. plan item-lar yaranır
4. HR review edir

### 7.1. İllik plan formu

Field-lər:

- `Plan adı`
- `Plan ili`
- `Plan rübü`
  - `Bütün il` və ya konkret rüb
- `Status`
- `Təsdiqlənmiş ehtiyaclardan plan sətirlərini avtomatik yarat`
- `Qeyd`

Yadda saxlananda:

## 8. Təlim təqvimi

Bu tab approved plan item-ları real sessiyaya çevirir.

Buradakı əsas hissələr:

- `Sessiya təklifləri`
- `Təlim təqvimi`
- `İştirakçı və davamiyyət`

### 8.1. Sessiya təklifləri

Approved plan item üçün sistem session proposal yaradır.

Burada HR:

- görünən təklifləri seçə bilər
- `Seçilən təkliflərdən sessiya yarat` ilə toplu session yarada bilər
- `Formaya yerləşdir` ilə session form-u avtomatik doldura bilər
- tək-tək `Sessiya yarat` edə bilər

### 8.2. İştirakçı və davamiyyət

Sessiya seçildikdən sonra:

- iştirakçı axtarışı
- davamiyyət filtri
- iştirakçı mənbə filtri
- görünənləri seç
- seçimi təmizlə
- toplu davamiyyət statusu tətbiqi

mümkündür.

Bu hissə sessiya icrası üçün əsas əməliyyat panelidir.

## 9. Nəticələr

Bu tab delivery və sənəd bağlama hissəsidir.

Burada:

- sessiyanı tamamlayırsan
- certificate / document faylı yükləyirsən
- `Bax / Yüklə / Əvəz et / Sil` ilə sənədi idarə edirsən
- rəy forması və cavabları toplayırsan

Certificate viewer burada ayrıca document panel kimi işləyir:

- böyük preview sahəsi
- sənəd tipi və status badge-ləri
- metadata bloku
- action rail (`Bax`, `Yüklə`)
- sessiya konteksti

## 10. Analitika və hesabat

Burada aşağıdakılar var:

- `Əhatə və uyğunluq`
- `Hesabat xülasəsi`
- `İcra xülasəsi export`
- `Pivot hesabat`
- `Audit hesabatı`
- `Çap görünüşü`

`Əhatə və uyğunluq` hissəsi plan, need və requirement coverage göstəricilərini göstərir.

- `training_annual_plans`

### 7.2. Plan status necə dəyişir

- plan yaradılarkən adətən `draft` olur
- auto-generate nəticəsində item yaranarsa plan `review` olur
- plan item qalmayıbsa yenidən `draft` ola bilər
- bütün plan item-lar `approved` olanda plan avtomatik `approved` olur
- `published` ayrıca son mərhələ statusudur

### 7.3. Sistem tövsiyəli planlar

Bu board HR üçün “sistem nələri önə çəkir?” sualını cavablayır.

Score nələrə əsaslanır:

- ehtiyacın mənbəyi
- prioriteti
- requirement-in `mandatory` olması
- role criticality
- eyni kompetensiya boşluğunun neçə dəfə təkrarlandığı
- eyni vəzifədə neçə gap olduğu
- proqramın hazır olması
- due date yaxınlığı

Sistem hər təklif üçün səbəb də göstərir:

- `mandatory`
- `role critical`
- `repeat gap`
- `program ready`
- `overdue`

### 7.4. Plan item review

Plan item seçiləndə HR:

- participant sayını dəyişə bilər
- büdcəni dəyişə bilər
- prioriteti dəyişə bilər
- review note yaza bilər
- `hr_adjusted` edə bilər
- `approved` edə bilər

Bu hissə auto-generated təklifin HR tərəfindən düzəldilmiş final versiyasıdır.

## 8. Təlim təqvimi tabı

Bu tab approved plan item-ları real sessiyaya çevirir.

### 8.1. Session proposal board

Approved item-lardan sistem proposal çıxarır.

Proposal nəyi göstərir:

- hansı plan item-dan gəlib
- hansı proqram üzrə sessiya tövsiyə olunur
- neçə participant var
- hansı tarix daha uyğun görünür
- təxmini büdcə

### 8.2. Proposal-dan nə etmək olar

- `Formaya yerləşdir`
  - session form avtomatik doldurulur
- `Sessiya yarat`
  - bir kliklə real `training_sessions` yaranır

### 8.3. Sessiya formu

Field-lər:

- `Plan`
- `Proqram`
- `Başlıq`
- `Başlanğıc`
- `Bitiş`
- `Məkan`
- `Trainer`
- `Capacity`
- `Planned budget`
- `Auto fill participants`
- `Status`
- `Qeyd`

Yadda saxlananda:

- `training_sessions`

### 8.4. Participant auto-fill

Əgər `Auto fill participants` aktivdirsə:

- approved/planned need-lərdən uyğun əməkdaşlar sessiyaya əlavə olunur

### 8.5. Participant detail

Sessiya seçildikdə:

- participant list çıxır
- search/filter işləyir
- quick attendance toggle var
- bulk status update var
- selected participant-ləri silmək olur

Participant status nümunələri:

- `confirmed`
- `attended`
- `absent`
- `cancelled`

## 9. Nəticələr tabı

Bu tab artıq keçirilmiş sessiyanı bağlamaq üçündür.

### 9.1. Feedback form

Field-lər:

- `Sessiya`
- `Başlıq`
- `Status`
- `Default question type`
  - `rating`
  - `text`
  - `multiple_choice`
- `Suallar`

Yadda saxlananda:

- `training_feedback_forms`

### 9.2. Feedback response

Field-lər:

- `Feedback form`
- `Əməkdaş`
- `Overall score`
- `Şərhlər`
- `Cavablar`

Yadda saxlananda:

- `training_feedback_responses`

### 9.3. Delivered trainings

Sessiya tamamlandıqda:

- `training_delivery_records` yaranır
- bağlı training need `completed` ola bilir

### 9.4. Certificate / document

Delivery record üçün:

- certificate upload etmək olar
- preview etmək olar
- download etmək olar
- replace etmək olar
- delete etmək olar

## 10. Analitika tabı

Bu tab rəhbərlik və HR monitorinqi üçündür.

Burada görünən əsas göstəricilər:

- `Əhatə nisbəti`
- `Uyğunluq nisbəti`
- `Rol tələbi əhatəsi`
- `Ümumi ehtiyac`
- `Təsdiqlənmiş ehtiyac`
- `Planlanan ehtiyac`
- `Mənbə qarışığı`
- `Prioritet qarışığı`
- `Ən çox boşluq olan vəzifələr`

Bu rəqəmlər qərar üçün istifadə olunur:

- hansı sahədə boşluq çoxdur
- hansı mənbədən ehtiyac daha çox gəlir
- hansı vəzifələr risklidir

## 11. Report və export

Modul artıq bunları export edir:

- delivery report
- feedback report

Delivery report artıq certificate sütununu da daşıyır.

## 12. Tipik iş axını

### Ssenari 1: HR sıfırdan plan qurur

1. qrup, səviyyə, kompetensiya, proqram yaradır
2. requirement matrix-i yazır
3. employee profile daxil edir
4. need yaradır və ya system-generated need qəbul edir
5. illik plan yaradır
6. system suggestion-ları yoxlayır
7. plan item-ları `hr_adjusted` və ya `approved` edir
8. proposal-ları sessiyaya çevirir
9. sessiyanı keçirir
10. feedback və certificate bağlayır

### Ssenari 2: Need Performance modulundan gəlir

1. zəif score və ya test nəticəsi yaranır
2. need bu modulda avtomatik görünür
3. HR proqram və target level-i yoxlayır
4. illik planlama tabında suggestion board-a keçir
5. plan item approved olur
6. session proposal yaranır
7. sessiya keçirilir
8. nəticə `completed` və report qatına düşür
