# Performance Evaluation User Guide

Bu sənəd `Performance Evaluation` modulunu ilk dəfə görən istifadəçi üçün addım-addım izah edir. Məqsəd odur ki, HR əməkdaşı, rəhbər və reviewer:

- hər tabın nə üçün açıldığını bilsin
- hər formda hansı field-in niyə lazım olduğunu başa düşsün
- `Save` edəndən sonra hansı record-un yarandığını və hansı statusun dəyişdiyini bilsin
- performans score-un `Training Needs` moduluna necə təsir etdiyini anlasın
- form əsaslı qiymətləndirmə ilə test əsaslı skill measurement arasındakı fərqi görə bilsin

Route: `/performance-evaluation`

## 1. Modulun qısa məntiqi

Bu modul iki ayrı, amma əlaqəli axını idarə edir:

1. forma əsaslı performans qiymətləndirməsi
2. test və skill measurement

Qısa data axını belədir:

`Cycle -> Template -> Section -> Item -> Evaluation Form -> Score -> Final Result -> Weak Link -> Training Need`

Test xətti isə belədir:

`Test Bank -> Question -> Test Session -> Attempt -> Review -> Finalize -> Skill Gap -> Training Need`

Bu modulun ən vacib tərəfi budur:

- zəif nəticə sadəcə “bal” kimi qalmır
- aşağı score və ya zəif test nəticəsi `Training Needs` modulunda avtomatik need yarada bilər

## 2. Yeni istifadəçi üçün tövsiyə olunan iş sırası

Sıfırdan işləyirsənsə, bu ardıcıllıqla get:

1. `Dövrlər` tabında cycle yarat
2. `Şablonlar` tabında template yarat
3. template üçün section yarat
4. section üçün item yarat
5. hər item-i düzgün competency ilə bağla
6. `Qiymətləndirmələr` tabında formu əməkdaşa təyin et
7. evaluator score-larını daxil et
8. weak result-ların Training Needs-ə düşdüyünü yoxla
9. test istifadə olunursa, ayrıca bank, question və session qur
10. open answer-ları review et və attempt-i finalize et

Əgər item competency-yə bağlanmasa, score yazılsa belə Training Needs inteqrasiyası işləməyəcək.

## 3. Tab-lar nə üçündür

- `Xülasə`
  - ümumi vəziyyətə baxış
- `Dövrlər`
  - qiymətləndirmənin zaman pəncərəsi
- `Şablonlar`
  - form skeleton-u: template, section, item
- `Qiymətləndirmələr`
  - real əməkdaşlara form təyinatı və score capture
- `Testlər`
  - skill measurement və open answer review
- `Tam siyahılar`
  - paginated, filter-li, detail panel-li tam workspace

## 4. Xülasə tabı

Bu tab əməliyyat aparmaq üçün deyil. Bu tab idarəetmə baxışıdır.

Burada görünən sayğaclar:

- `Cycles`
- `Templates`
- `Sections`
- `Items`
- `Forms`
- `Scores`
- `Need links`

Bu sayğaclar nəyi göstərir:

- baza düzgün qurulubmu
- real form assignment var ya yox
- artıq evaluator score yığılıbmı
- zəif nəticələr Training Needs-ə ötürülüb ya yox

Dashboard kartları limitli gəlir. Tam nəzarət üçün `Tam siyahılar` və export hissəsindən istifadə et.

## 5. Dövrlər tabı

Cycle performans işinin zaman konteyneridir.

### 5.1. Cycle formu

Field-lər:

- `Cycle name`
  - nümunə: `2026 Annual Performance`
- `Cycle type`
  - `annual`, `academic`, `quarterly`
- `Start date`
- `End date`
- `Status`
  - `draft`, `active`, `closed`
- `Auto-generate forms`
  - gələcəkdə avtomatik form generate olunması üçün işarədir
- `Description`

Save olunanda nə olur:

- `performance_cycles` cədvəlinə create və ya update olunur

Bu formdan sonra hara təsir edir:

- evaluation assignment formunda seçilir
- test session formunda seçilir
- recent cycles kartında görünür
- export və list-lərdə period konteksti olur

Praktik tövsiyə:

- eyni dövr üçün aydın adlandırma seç
- aktiv cycle olmadan assignment-a keçmə

### 5.2. Recent cycles kartı

Bu kart:

- dövrün periodunu
- statusunu
- auto generation açıqdır ya yox
- edit və delete action-larını

göstərir.

## 6. Şablonlar tabı

Bu tab form skeleton-unu qurur. Burada üç qat var:

1. template
2. section
3. item

### 6.1. Template formu

Field-lər:

- `Template name`
- `Template code`
- `Description`
- `Is active`

Save olunanda nə olur:

- `performance_form_templates` cədvəlinə yazılır

Bu formdan sonra hara təsir edir:

- section formunda select olur
- evaluation assignment formunda select olur
- recent template kartlarında görünür

Praktik qayda:

- code sahəsini boş qoymaq olar
- amma çox template olduqda seçim üçün code çox faydalıdır

### 6.2. Section formu

Section template daxilində böyük məna blokudur.

Field-lər:

- `Template`
- `Section name`
- `Impact on result (%)`
- `Sort order`

Save olunanda nə olur:

- `performance_form_template_sections` cədvəlinə yazılır

Bu formdan sonra hara təsir edir:

- item formunda section select olur
- final scoring strukturunda section çəkisi kimi istifadə olunur
- recent section kartında görünür

### 6.3. Item formu

Item ölçülən konkret kriteriyadır.

Field-lər:

- `Section`
- `Competency`
- `Criterion name`
- `Description`
- `Impact on result (%)`
- `Low score threshold`
- `Requires comment`
- `Sort order`

Field-lərin praktik mənası:

- `Competency`
  - Training Needs inteqrasiyasının əsas körpüsüdür
- `Low score threshold`
  - zəif nəticənin trigger həddidir
- `Requires comment`
  - evaluator-dan əlavə əsaslandırma gözlənilir
- `Impact on result (%)`
  - yekun hesabda rol oynayır

Save olunanda nə olur:

- `performance_form_template_items` cədvəlinə yazılır

Bu formdan sonra hara təsir edir:

- score capture formunda item seçilir
- final score hesabına daxil olur
- threshold altına düşərsə training need yarada bilir
- recent item kartında görünür

Ən vacib qayda:

- item competency-yə bağlanmalıdır
- əks halda zəif score görünəcək, amma auto training need yaranmayacaq

### 6.4. Şablonlar tabında iş sırası

Doğru sıra budur:

1. template yarat
2. həmin template üçün section yarat
3. hər section üçün item yarat
4. hər item üçün threshold və competency-ni yoxla

Bu sıra pozularsa evaluation assignment zamanı natamam skeleton yaranır.

## 7. Qiymətləndirmələr tabı

Bu tab real əməliyyat qatıdır. Burada iki əsas iş görülür:

1. form assignment
2. evaluator score capture

### 7.1. Evaluation assignment formu

Field-lər:

- `Cycle`
- `Template`
- `Employee`
- `Manager`
- `HR reviewer`

Save olunanda nə olur:

- `performance_forms` cədvəlinə create və ya update olunur
- eyni cycle + template + employee varsa `updateOrCreate` məntiqi işləyir

Bu formdan sonra hara təsir edir:

- evaluator score formunda selectable olur
- recent forms kartında görünür
- evaluator workspace üçün iş yaradır

Praktik qayda:

- employee seçmədən əvvəl template-in tam hazır olduğuna əmin ol
- manager və HR reviewer sahələri sonrakı iş sahəsi üçün vacibdir

### 7.2. Evaluator score formu

Field-lər:

- `Evaluation form`
- `Criterion`
- `Evaluator`
  - `self`, `manager`, `hr`
- `Score`
- `Comment`

Save olunanda nə olur:

- `performance_form_scores` cədvəlinə update-or-create yazılır
- formun uyğun evaluator statusu `submitted` olur
- formun final nəticəsi yenilənir
- weak-area sync servisi işləyir
- lazım gələrsə `training_need_items` və `performance_training_need_links` yaranır
- əvvəl yaranmış auto link artıq zəif deyilsə silinə bilər

Bu formdan sonra hara təsir edir:

- recent forms
- weak links kartı
- Training Needs modulu
- export report-lar

### 7.3. Score save olunanda konkret nə baş verir

Əgər score normal həddədirsə:

- score yazılır
- evaluator statusu yenilənir
- formun nəticəsi refresh olunur

Əgər score `Low score threshold` dəyərindən aşağıdırsa:

1. sistem bunu zəif nəticə kimi qəbul edir
2. item competency-yə bağlıdırsa həmin competency ilə əlaqəli training need yarada bilər
3. `performance_training_need_links` cədvəlində link yaranır
4. `Training Needs` modulunda source=`performance_gap` olan need görünür

Əgər sonradan score düzəlirsə:

- auto-created link təmizlənə bilər
- artıq zəif olmayan nəticə need ilə bağlı qalmaz

Əgər item competency-yə bağlı deyilsə:

- score yenə də yazılır
- amma training need inteqrasiyası işləmir
- sistem bunu ayrıca xəbərdarlıq mesajı ilə bildirir

### 7.4. Recent forms kartı

Bu kartda dörd status xətti var:

- `self_status`
- `manager_status`
- `hr_status`
- `final_category`

İlk üçü evaluator mərhələsini göstərir:

- `draft`
- `submitted`

Sonuncu isə nəticə kateqoriyasıdır:

- `high`
- `medium`
- `weak`

Bu dörd badge eyni şey deyil.

### 7.5. Weak links kartı

Bu kart zəif nəticədən yaranan training need bağlantılarını göstərir.

Burada istifadəçi görə bilər:

- hansı form
- hansı item
- hansı competency
- hansı əməkdaş
- hansı zəif nəticə

Bu kart Training Needs inteqrasiyasının canlı sübutudur.

### 7.6. Evaluator workspace

Bu ayrıca istifadəçi iş sahəsidir. Rəhbər və HR reviewer özünə düşən işləri burada idarə edir.

Buradakı iş axını:

1. soldakı assigned forms siyahısından formanı tap
2. `Open score form` ilə sağdakı score formunu doldur
3. item seç
4. score və comment yaz
5. save et

Open-answer review hissəsi də eyni məntiqlə işləyir:

1. pending review cavabını tap
2. `Open review form` et
3. bal və rəy yaz
4. review et

## 8. Testlər tabı

Bu tab skill measurement üçündür.

### 8.1. Test bank formu

Field-lər:

- `Test bank name`
- `Test bank code`
- `Pass score (%)`
- `Duration (minutes)`
- `Description`
- `Max attempts`
- `Is active`

Save olunanda nə olur:

- `performance_test_banks` cədvəlinə yazılır

Bu formdan sonra hara təsir edir:

- question formunda select olur
- test session formunda select olur
- recent bank kartlarında görünür

### 8.2. Test question formu

Field-lər:

- `Test bank`
- `Competency`
- `Question type`
  - `multiple_choice`
  - `open_answer`
  - `case_study`
  - `behavioral`
- `Question prompt`
- `Description`
- `Maximum score`
- `Sort order`
- `Is active`
- `Options`

Xüsusi qeyd:

- `Options` əsasən multiple-choice suallar üçün vacibdir
- multiple-choice seçilərsə variantlar boş qala bilməz

Save olunanda nə olur:

- `performance_test_questions` cədvəlinə yazılır
- option-lar ayrıca sync olunur

Bu formdan sonra hara təsir edir:

- attempt answer formunda question select olur
- competency link-i varsa zəif nəticə Training Needs-ə gedə bilər

### 8.3. Test session formu

Field-lər:

- `Cycle`
- `Test bank`
- `Employee`
- `Reviewer`
- `Scheduled at`
- `Available until`
- `Pass score`
- `Duration`
- `Max attempts`
- `Status`
  - `assigned`, `in_progress`, `completed`, `closed`

Save olunanda nə olur:

- `performance_test_sessions` cədvəlinə yazılır

Bu formdan sonra hara təsir edir:

- attempt answer formu
- recent attempts
- review queue

### 8.4. Attempt answer formu

Field-lər:

- `Test session`
- `Question`
- `Attempt number`
- `Option`
- `Answer text`

Məntiq:

- auto-scored suallarda option seçilməlidir
- open-answer tipində text cavabı boş qala bilməz
- seçilən sual seçilən bank-a aid olmalıdır

Save olunanda nə olur:

- `performance_test_attempts` lazım gələrsə yaradılır
- `performance_test_attempt_answers` update-or-create ilə yazılır
- cavab open-answer-dırsa review status `pending` olur
- auto-score olursa `auto_ready` olur

### 8.5. Finalize attempt action-ı

Bu action seçilmiş cəhdi bağlayır.

Finalize olunanda nə olur:

- auto-score olunan suallar hesablanır
- cəhdin yekun faizi çıxır
- pass/fail məntiqi işləyir
- zəif skill nəticəsi varsa Training Needs inteqrasiyası işləyə bilər

Bu addım edilmədən test nəticəsi tam bağlanmış hesab olunmur.

### 8.6. Open answer review formu

Field-lər:

- `Answer`
- `Review score`
- `Feedback`

Save olunanda nə olur:

- reviewer əl ilə bal verir
- cavabın review hissəsi bağlanır
- test nəticəsi daha düzgün yekunlaşır

Bu hissə yalnız review permission olan istifadəçi üçündür.

## 9. Training Needs ilə inteqrasiya

Bu modul iki fərqli source ilə training need yarada bilər:

1. `performance_gap`
   - form əsaslı aşağı score
2. `skill_gap`
   - test və measurement nəticəsi zəifdir

Need yaranması üçün lazım olan şərtlər:

- item və ya question competency-yə bağlı olmalıdır
- employee müəyyən olmalıdır
- nəticə threshold-dan aşağı düşməlidir

Need yaranandan sonra nə olur:

- Training Needs modulunda need queue-ya düşür
- planlama və session axınına qoşula bilir

## 10. Tam siyahılar tabı

Bu tab dashboard kartlarının geniş versiyasıdır.

Burada:

- search
- filter
- pagination
- detail panel

ilə aşağıdakı varlıqlar görünür:

- forms
- templates
- items
- weak links
- test attempts

Dashboard kartı kifayət etmirsə, həmişə bu hissəyə keç.

## 11. Export və report hissəsi

Moduldan bu report-lar çıxarıla bilir:

- forms report
- summary report
- weak links report
- weak pivot report
- audit report
- print summary

Bu report-lar gündəlik edit işi üçün deyil; audit, rəhbərlik və arxiv üçündür.

## 12. Save edəndə təsir xəritəsi

Qısa xatırlatma:

- `Cycle`
  - period konteynerini yaradır
- `Template`
  - form skeleton-u yaradır
- `Section`
  - nəticə strukturunu qurur
- `Item`
  - real ölçmə kriteriyasını və weak trigger-i yaradır
- `Evaluation form`
  - əməkdaş assignment-i yaradır
- `Score`
  - evaluator statusunu, nəticəni və weak link-i yeniləyir
- `Test bank`
  - measurement bazasını yaradır
- `Question`
  - competency bağlı test materialı yaradır
- `Test session`
  - real employee test assignment-i yaradır
- `Attempt answer`
  - cavab bazasını qurur
- `Finalize`
  - cəhdi bağlayır və nəticəni formalaşdırır
- `Review`
  - open-answer hissəsini yekunlaşdırır

## 13. Tipik iş ssenariləri

### Ssenari A: İllik performans qiymətləndirməsi

1. cycle yarat
2. template yarat
3. section və item-ları tamamla
4. hər item-in competency və threshold-unu yoxla
5. evaluation formu əməkdaşa təyin et
6. manager score daxil et
7. HR review score daxil et
8. weak link yaranıbsa Training Needs-ə keç və oradakı need-i planlaşdır

### Ssenari B: Skill measurement ilə gap aşkar etmək

1. test bank yarat
2. sualları competency-lərlə bağla
3. test session yarat
4. cavabları daxil et
5. open-answer varsa review et
6. attempt-i finalize et
7. weak skill nəticəsi varsa Training Needs-də yaranan need-i yoxla

### Ssenari C: Manager günlük işi

1. evaluator workspace-ə gir
2. assigned formu seç
3. score formunu aç
4. item üzrə bal ver
5. lazım olduqda comment yaz
6. submit et

## 14. Ən çox edilən səhvlər

- cycle yaratmadan form təyinatına keçmək
- template hazır olmadan employee assignment etmək
- item-ı competency-siz saxlamaq
- threshold-u boş və ya mənasız vermək
- test question-u yanlış bank ilə qarışdırmaq
- attempt finalize etmədən nəticə gözləmək
- weak link yarananda Training Needs tərəfini yoxlamamaq

## 15. Son qayda

Bu modulu belə yadda saxla:

- `Cycle` vaxtı müəyyən edir
- `Template` formu qurur
- `Item` nəyi ölçdüyünü deyir
- `Score` nəticəni yaradır
- `Weak result` inkişaf ehtiyacına çevrilə bilər

Yəni bu modul sadəcə qiymətləndirmə ekranı deyil. Bu modul performans nəticəsini inkişaf qərarına çevirən idarəetmə qatıdır.
