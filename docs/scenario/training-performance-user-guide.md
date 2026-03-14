# Təlim və performans istifadəçi bələdçisi

Bu sənəd `Təlim ehtiyacı` və `Performans qiymətləndirməsi` modullarını birlikdə başa düşmək üçün əsas giriş bələdçisidir. Məqsəd odur ki, istifadəçi:

- iki modulun hansı məqsədlə yaradıldığını anlasın
- hansı işi hansı modulda etməli olduğunu qarışdırmasın
- nəticələrin moduldan-modula necə keçdiyini görsün
- əvvəl hansı sənədi, sonra hansı hissəni oxumalı olduğunu bilsin
- gündəlik əməliyyat və idarəetmə baxışını bir-birindən ayıra bilsin

## 1. Bu sənəd kim üçündür

- HR əməkdaşı
- təlim və inkişaf və ya təlim koordinatoru
- performans prosesi quran admin
- yoxlayan və rəhbər
- sistemin canlı istifadəsinə başlamazdan əvvəl prosesi tam anlamaq istəyən son istifadəçi

## 2. İki modulun ümumi məntiqi

Bu iki modul bir-birindən ayrı görünür, amma faktiki olaraq eyni inkişaf dövrünün iki fərqli hissəsidir.

### 2.1. Performans modulu nə edir

`Performans qiymətləndirməsi` modulu:

- dövr yaradır
- şablon qurur
- form təyin edir
- bal toplayır
- test sessiyası yaradır
- test cavablarını və review nəticələrini bağlayır
- zəif sahələri çıxarır

### 2.2. Təlim modulu nə edir

`Təlim ehtiyacı` modulu:

- kompetensiya və proqram bazasını saxlayır
- rol tələblərini yazır
- əməkdaş profili və training need yaradır
- illik plan və sessiya planlaşdırır
- sessiyanı keçirir
- nəticə, rəy və sənədləri bağlayır
- rəhbər hesabatlarını çıxarır

### 2.3. Modulların bir-birinə bağlandığı nöqtə

Ən vacib inteqrasiya xətti budur:

`Performans nəticəsi -> zəif sahə / kompetensiya boşluğu -> təlim ehtiyacı -> plan -> sessiya -> nəticə`

Bu o deməkdir ki, performans sadəcə ölçmür. Ölçdüyü nəticəni inkişaf işinə çevirə bilir.

## 3. Hansı işi hansı modulda etməliyəm

### 3.1. Bu hallar təlim modulundadır

- kompetensiya kataloqu qurmaq
- proqram yaratmaq
- rol tələbləri yazmaq
- training need yaratmaq
- illik plan qurmaq
- sessiya keçirmək
- davamiyyət və rəy toplamaq
- illik / rüblük training hesabatına baxmaq

### 3.2. Bu hallar performans modulundadır

- qiymətləndirmə dövrü açmaq
- forma şablonu qurmaq
- form təyin etmək
- evaluator balı toplamaq
- test bank yaratmaq
- sual əlavə etmək
- test sessiyası təyin etmək
- açıq cavabı review etmək
- test transcript və audit hesabatına baxmaq

## 4. Tövsiyə olunan oxu sırası

Bu paketi belə oxu:

1. əvvəl bu ümumi bələdçini oxu
2. sonra istifadə etdiyin əsas modulu oxu
3. sonra digər modulu oxu ki inteqrasiyanı görəsən
4. sonda admin / ops guide-a keç

Oxu sırası:

1. `Təlim ehtiyacı` istifadəçi bələdçisi  
   Bax: `/docs/scenario/training-needs-user-guide.md`
2. `Performans qiymətləndirməsi` istifadəçi bələdçisi  
   Bax: `/docs/scenario/performance-evaluation-user-guide.md`
3. Admin / əməliyyat bələdçisi  
   Bax: `/docs/scenario/training-performance-admin-ops-guide.md`

## 5. Rola görə praktik yol xəritəsi

### 5.1. HR / təlim və inkişaf üçün

1. Təlim kataloqlarını oxu
2. rol tələbi və employee profile hissəsini oxu
3. performansda form təyinatı və test təyinatı hissəsini oxu
4. təlimdə planlama, təqvim və hesabat hissəsini oxu

### 5.2. Yoxlayan / rəhbər üçün

1. performans evaluator iş sahəsi hissəsini oxu
2. test review və test workspace hissəsini oxu
3. zəif nəticənin training need-ə necə düşdüyünü oxu

### 5.3. Admin üçün

1. əvvəl hər iki modulun ümumi məntiqini oxu
2. sonra dashboard tab-larını və form field-lərini oxu
3. sonda admin / operations guide-a keç

## 6. İstifadə zamanı ən vacib prinsip

Bu iki modulu “ayrı ekranlar” kimi yox, bir-birinə bağlı sistem kimi düşün:

- təlim modulunda kompetensiya zəif yazılıbsa, performans nəticəsi düzgün şərh olunmur
- performans tərəfində item və ya test sualı competency-yə bağlanmayıbsa, zəif nəticə təlim tərəfinə düzgün düşmür
- təlim planı qurulub amma sessiya bağlanmayıbsa, nəticə və rəhbər hesabatları boş görünə bilər

## 7. Gündəlik istifadə üçün qısa sxem

### Ssenari A: form əsaslı performans qiymətləndirməsi

1. performansda dövr yarat
2. template, section və item qur
3. form təyin et
4. evaluator balı daxil et
5. zəif link yaranıb-yaranmadığını yoxla
6. lazım gələrsə təlim modulunda həmin ehtiyacı planlaşdır

### Ssenari B: test əsaslı ölçmə

1. performansda bank yarat
2. sualları əlavə et və ya import et
3. session təyin et
4. əməkdaş test həll etsin
5. open-answer varsa review et
6. finalize et
7. zəif nəticənin təlim ehtiyacı yaratdığını yoxla

### Ssenari C: təlim planı və icra

1. təlim modulunda ehtiyacları review et
2. plan yarat
3. plan item-ları təsdiqlə
4. təklifləri session-a çevir
5. iştirakçı və davamiyyəti idarə et
6. nəticə, rəy və sertifikatı bağla
7. rəhbər hesabatında icranı yoxla

## 8. Bu paket nəyi tam izah edir

- hər modulun ümumi məntiqi
- tab, kart və əsas komponentlərin rolu
- form field-lərinin məqsədi
- save olunanda sistemdə nə yarandığı
- məlumatın hansı siyahı, summary və report-a təsir etdiyi
- düzgün iş sırası
- HR, yoxlayan və rəhbər baxışından tipik ssenarilər

## 9. Bu sənədi necə istifadə etməli

- modulu ilk dəfə açırsansa əvvəl ümumi bələdçini oxu
- sonra həmin modulun tam user guide hissəsinə keç
- işi icra edərkən ekranı açıq saxlayıb addım-addım müqayisə et
- qarışıq gələn yerlərdə əvvəl data axınını, sonra form field-lərini yoxla
- təlim və performans bir-birinə bağlı olduğuna görə zəif nəticə və təlim ehtiyacı axınını birlikdə düşün
