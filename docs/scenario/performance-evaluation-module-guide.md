# Performans Qiymətləndirməsi Modulu Bələdçisi

Bu sənəd `Performans qiymətləndirməsi` modulunu sadə və aydın dildə izah edir.

Məqsəd odur ki, istifadəçi:

- modulun nə etdiyini başa düşsün
- hansı tabın nə iş gördüyünü ayıra bilsin
- forma əsaslı və test əsaslı xətləri qarışdırmasın
- nəticələrin necə formalaşdığını anlaya bilsin

Əsas giriş:

- `/performance-evaluation`

Əsas dashboard:

- [dashboard.blade.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/PerformanceEvaluation/Resources/views/livewire/performance-evaluation/dashboard.blade.php)

## 1. Modul nə üçündür

Bu modul əməkdaşların performansını ölçmək və zəif sahələri sistemli şəkildə üzə çıxarmaq üçündür.

Sadə dildə iki əsas işi görür:

1. forma əsaslı qiymətləndirmə aparır
2. test əsaslı bilik və bacarıq ölçür

Bu nəticələr sonradan `Təlim ehtiyacı` moduluna ötürülə bilər.

## 2. Modulun iki əsas xətti

### Forma əsaslı xətt

`Cycle -> Template -> Section -> Item -> Evaluation Form -> Score -> Final Result`

Bu xətt daha çox rəhbər, evaluator və HR qiymətləndirməsi üçündür.

### Test əsaslı xətt

`Test Bank -> Question -> Session -> Attempt -> Answer -> Review -> Finalize`

Bu xətt bilik və bacarıq ölçümü üçündür.

## 3. Modul kimlər üçündür

### HR / admin

Bu rol:

- cycle yaradır
- template və section yaradır
- item-lar qurur
- evaluation form assign edir
- test bank və test session hazırlayır

### Evaluator / rəhbər

Bu rol:

- form score daxil edir
- açıq cavabları review edir
- nəticəni bağlayır

### Rəhbərlik və analitik baxış

Bu rol:

- nəticələrə
- zəif sahələrə
- form/test hesabatlarına

baxır.

## 4. Tab-lar nə üçündür

Dashboard əsasən bu hissələrdən ibarətdir:

- `Xülasə`
- `Dövrlər`
- `Şablonlar`
- `Qiymətləndirmələr`
- `Testlər`
- `Hesabatlar`
- `Tam siyahılar`

### Xülasə

Ümumi vəziyyət və əsas sayğaclar.

### Dövrlər

Qiymətləndirmə periodları burada idarə olunur.

### Şablonlar

Template, section və item skeleton-u burada qurulur.

### Qiymətləndirmələr

Forma assignment və score capture burada aparılır.

### Testlər

Test bank, sual, session, cavab və review burada idarə olunur.

### Hesabatlar və Tam siyahılar

Filtrli izləmə, audit və nəticə görünüşləri burada toplanır.

## 5. Yeni istifadəçi üçün doğru iş sırası

Bu modulu ilk dəfə qurursansa, ən doğru sıra belədir:

1. `Dövrlər` tabında cycle yarat
2. `Şablonlar` tabında template yarat
3. template daxilində section yarat
4. item-ları competency və scoring məntiqi ilə qur
5. `Qiymətləndirmələr` tabında form assign et
6. evaluator score toplasın
7. ehtiyac varsa `Testlər` tabında bank və session qur
8. answer/review/finalize axınını bağla
9. hesabat və zəif nəticələri yoxla

## 6. Gündəlik istifadə ssenariləri

### Performans formu yaratmaq

1. cycle seçilir
2. template seçilir
3. əməkdaşa form assign olunur
4. evaluator bal daxil edir

### Test session keçirmək

1. test bank seçilir
2. session açılır
3. əməkdaş test həll edir
4. açıq cavab varsa review olunur
5. attempt finalize edilir

### Zəif nəticəni izləmək

1. score və ya test nəticəsi baxılır
2. weak area və weak competency müəyyən edilir
3. lazım gələrsə training need yaranır

## 7. Ən vacib anlayışlar

### Cycle

Qiymətləndirmənin zaman pəncərəsidir.

### Template

Qiymətləndirmə skeleton-u və ölçü quruluşudur.

### Section

Template daxilində qruplaşdırılmış qiymətləndirmə blokudur.

### Item

Faktiki ölçülən meyardır.

### Evaluation form

Template-in konkret əməkdaşa bağlanmış real qiymətləndirmə formasıdır.

### Test bank

Test suallarının mənbəyidir.

### Attempt

İşçinin konkret test icra qeydi və cavab nəticəsidir.

## 8. Problem olanda haraya baxmaq lazımdır

### Form görünür, amma nəticə zəif və ya natamamdırsa

Yoxlanmalı yerlər:

- cycle statusu
- template section/item quruluşu
- item competency mapping-i
- evaluator score completeness

### Test nəticəsi bağlanmırsa

Yoxlanmalı yerlər:

- bank və sual strukturu
- open-answer review
- finalize mərhələsi

### Training need yaranmırsa

Yoxlanmalı yerlər:

- weak link logic
- competency mapping
- nəticənin həqiqətən weak threshold altına düşüb-düşməməsi

## 9. Təlim modulu ilə əlaqəsi

Bu modul tək işləyə bilər, amma real dəyəri çox vaxt `Təlim ehtiyacı` modulu ilə birlikdə çıxır.

Əsas axın:

`Performans və test nəticəsi -> zəif sahə -> training need`

Bu səbəbdən item və competency mapping burada çox kritikdir.

## 10. Hansı sənədlə birlikdə oxumaq lazımdır

Bu sənəd üst səviyyə bələdçidir.

Daha detallı əməliyyat üçün:

- [Performance Evaluation User Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/performance-evaluation-user-guide.md)
- [Təlim və Performans Ümumi Bələdçisi](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/training-performance-user-guide.md)
- [Training / Performance Admin Ops Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/training-performance-admin-ops-guide.md)

## 11. Qısa nəticə

`Performans qiymətləndirməsi` modulu sadəcə bal yazmaq üçün deyil.

Bu modul:

- qiymətləndirmə periodu qurur
- ölçmə skeleton-u yaradır
- form və test nəticələrini toplayır
- zəif sahələri çıxarır
- nəticəni təlim ehtiyacına çevirə bilir

Ən vacib yadda saxlanmalı fikir:

`Bu modul performansı ölçür, zəifliyi aşkarlayır və inkişaf üçün əsas yaradır.`
