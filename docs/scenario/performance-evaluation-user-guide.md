# Performans qiymətləndirmə istifadəçi bələdçisi

Bu sənəd `Performans qiymətləndirməsi` modulunu ilk dəfə açan istifadəçi üçün tam əməliyyat bələdçisidir. Məqsəd budur ki, istifadəçi:

- modulun ümumi məntiqini başa düşsün
- hansı tabın nə iş gördüyünü bilsin
- hər formda hər field-in nə demək olduğunu anlasın
- `Save` edəndə sistemdə nə yarandığını bilsin
- hansı addımı hansı addımdan sonra etməli olduğunu qarışdırmasın
- forma əsaslı qiymətləndirmə ilə test əsaslı ölçmənin fərqini düzgün qursun
- nəticələrin `Təlim ehtiyacı` moduluna necə təsir etdiyini görə bilsin

Route: `/performance-evaluation`

## 1. Modul nə üçündür

Bu modul iki ayrı, amma bir-birinə bağlı qiymətləndirmə xəttini idarə edir:

1. `Forma əsaslı performans qiymətləndirməsi`
2. `Test əsaslı bilik və bacarıq ölçümü`

Birinci xətt işçi haqqında strukturlaşdırılmış qiymətləndirmə aparır.

İkinci xətt işçiyə test təyin edir, cavab toplayır, açıq cavabları review edir və nəticəyə görə zəif sahələri çıxarır.

Bu modulun əsas dəyəri odur ki, zəif nəticə sadəcə ekranda görünən bal kimi qalmır. Zəif nəticə:

- `weak category`
- `weak competency link`
- `training need`

yarada bilir.

## 2. Modulun böyük məntiqi

### 2.1. Forma əsaslı xətt

`Cycle -> Template -> Section -> Item -> Evaluation Form -> Score -> Final Result -> Weak Link -> Training Need`

### 2.2. Test əsaslı xətt

`Test Bank -> Question -> Test Session -> Attempt -> Answer -> Review -> Finalize -> Skill Gap -> Training Need`

Bu o deməkdir ki:

- `Cycle` zaman çərçivəsini yaradır
- `Template` qiymətləndirmə skeleton-unu yaradır
- `Item` nəyi ölçdüyünü deyir
- `Evaluation Form` həmin skeleton-u konkret əməkdaşa bağlayır
- `Score` nəticəni yaradır
- `Test Bank / Question / Session` test xəttini qurur
- `Attempt / Answer / Review` test nəticəsini formalaşdırır
- zəif nəticə `Təlim ehtiyacı` moduluna ötürülə bilər

## 3. Modulu hansı rollar istifadə edir

### 3.1. HR və ya admin

Bu rol adətən:

- cycle yaradır
- template yaradır
- section və item yaradır
- evaluation form təyin edir
- test bank yaradır
- sual yaradır
- test session yaradır
- export və audit hesabatlarına baxır

### 3.2. Rəhbər və yoxlayan

Bu rol adətən:

- evaluator workspace-də form score doldurur
- test open-answer cavablarını review edir

### 3.3. Əməliyyat qaydası

HR və admin baza qurur.

Rəhbər və yoxlayan real qiymətləndirməni aparır.

Bu ayrım vacibdir. Əgər skeleton hazır olmadan assignment edilsə, aşağıdakı problemlər yaranır:

- form natamam olur
- item seçimi qarışır
- scoring məntiqi zəifləyir
- training link-ləri düzgün yaranmır

## 4. Ekrandakı tablar nə üçündür

Dashboard bu tablardan ibarətdir:

- `Xülasə`
- `Dövrlər`
- `Şablonlar`
- `Qiymətləndirmələr`
- `Testlər`
- `Hesabatlar`
- `Tam siyahılar`

### 4.1. Xülasə

İdarəetmə baxışı üçündür. Burada əməliyyat deyil, ümumi vəziyyət görünür.

### 4.2. Dövrlər

Period və status idarəsi üçündür.

### 4.3. Şablonlar

Template, section və item qurmaq üçündür.

### 4.4. Qiymətləndirmələr

Form assignment və manual score capture üçündür.

### 4.5. Testlər

Test bank, sual, session, cavab toplama, finalize və review üçündür.

### 4.6. Tam siyahılar

Filter, search, detail panel və paginated tam baxış üçündür.

### 4.7. Hesabatlar

Bu tab:

- performans form report-ları
- weak link report-ları
- audit çıxışları
- test sessiya report-ları
- test cəhd report-ları
- test cavab audit report-ları

üçün mərkəzi nöqtədir.

## 5. İstifadəçi üçün doğru iş sırası

Sıfırdan qurursansa, bu ardıcıllığı pozma:

1. `Dövrlər` tabında cycle yarat
2. `Şablonlar` tabında template yarat
3. template daxilində section yarat
4. section daxilində item yarat
5. hər item-i competency ilə bağla
6. `Qiymətləndirmələr` tabında form təyin et
7. evaluator workspace-də bal toplamağa başla
8. zəif nəticələri və weak link-ləri yoxla
9. əgər test xətti də istifadə olunacaqsa, `Testlər` tabında bank yarat
10. bank üçün sual yarat
11. işçiyə test session təyin et
12. cavabları topla
13. open-answer varsa review et
14. attempt-i finalize et
15. training impact-i və hesabatları yoxla

Ən çox səhv bu iki səbəbdən yaranır:

- item competency-yə bağlanmır
- test session bank və sual məntiqi tam qurulmadan cavab toplama mərhələsinə keçilir

## 6. Xülasə tabı

Bu tab əməliyyat aparmaq üçün deyil. Bu tab idarəetmə vitrini kimidir.

Burada görünən əsas göstəricilər:

- `Cycles`
- `Templates`
- `Forms`
- `Links`

Bu sayğaclar nəyi izah edir:

- baza qurulubmu
- real assignment var ya yox
- zəif nəticədən training link yaranıb ya yox

Bu tabdakı kartlar limitlidir. Yəni burada hər şeyin tam siyahısı yoxdur. Çox data olduqda tam nəzarət üçün:

- `Tam siyahılar`
- export report-lar
- print summary

istifadə olunur.

## 7. Dövrlər tabı

Cycle performans prosesinin zaman konteyneridir.

### 7.1. Cycle formundakı field-lər

#### `Cycle name`

Cycle adı.

Nümunə:

- `2026 Annual Performance`
- `Q1 2026 Review`

Bu field:

- listdə görünür
- form assignment zamanı seçilir
- report-larda period etiketi kimi çıxır

#### `Cycle type`

Hazır seçimlər:

- `annual`
- `academic`
- `quarterly`

Bu təsnifat əsasən idarəetmə və reporting konteksti üçündür.

#### `Start date`

Cycle-ın başlanğıc tarixidir.

#### `End date`

Cycle-ın bitiş tarixidir.

Bu iki tarix birlikdə period pəncərəsini yaradır.

#### `Status`

Hazır seçimlər:

- `draft`
- `active`
- `closed`

Praktik məna:

- `draft`: hələ qurulur
- `active`: istifadə olunur
- `closed`: artıq bitib, yeni assignment üçün uyğun deyil

#### `Auto-generate forms`

Bu flag gələcək və ya avtomatlaşdırılmış form generation məntiqinə hazır olmaq üçündür.

Bugünkü istifadəçi üçün qayda:

- iş axınında problem yaratmır
- amma aktiv cycle-larda açıq qalması məntiqlidir

#### `Description`

Cycle-a izah, daxili qeyd və ya business context əlavə etmək üçündür.

### 7.2. Cycle save ediləndə nə olur

`performance_cycles` cədvəlində record yaranır və ya update olunur.

Bu save-dən sonra dəyişən yerlər:

- cycle dropdown-ları
- recent cycles kartı
- evaluation assignment
- test session assignment
- export və summary grouping

### 7.3. HR üçün praktik qayda

Əgər yeni il və ya yeni quarter başlayırsa:

1. əvvəlcə cycle aç
2. status-u `active` et
3. sonra form və test assignment-a keç

## 8. Şablonlar tabı

Bu tab qiymətləndirmənin skeleton-unu qurur.

Burada 3 qat var:

1. `Template`
2. `Section`
3. `Item`

### 8.1. Template formu

#### `Template name`

Şablonun göstərilən adı.

Nümunə:

- `Leadership Review Template`
- `Officer Annual Evaluation`

#### `Template code`

Qısa daxili kod.

Nümunə:

- `LDR-2026`
- `OFC-ANNUAL`

Bu sahə çox template olduqda seçimi asanlaşdırır.

#### `Description`

Template-in nə üçün hazırlandığını izah edir.

#### `Is active`

Template istifadəyə açıqdır ya yox.

### 8.2. Template save ediləndə nə olur

`performance_form_templates` cədvəlinə record yazılır.

Sonra harada istifadə olunur:

- section formunda
- evaluation assignment formunda
- recent templates kartında
- lists tabında

### 8.3. Section formu

Section template daxilində böyük məna blokudur.

Misal:

- `Peşəkar davranış`
- `İdarəetmə bacarıqları`
- `Nəticəyönümlülük`

Field-lər:

#### `Template`

Bu section hansı template-ə aiddir.

#### `Section name`

Section-un adı.

#### `Impact on result (%)`

Section-un yekun hesabdakı çəkisi.

Bu rəqəm nə qədər yüksəkdirsə, həmin blok yekuna daha çox təsir edir.

#### `Sort order`

Section hansı sırada görünəcək.

### 8.4. Section save ediləndə nə olur

`performance_form_template_sections` cədvəlinə record yazılır.

Sonra harada istifadə olunur:

- item formunda section seçimi kimi
- template strukturunda
- scoring zamanı item-lərin məna qruplaşması kimi

### 8.5. Item formu

Item qiymətləndirilən konkret kriteriyadır.

Misal:

- `Etik qaydalara riayət`
- `Tapşırıqları vaxtında icra edir`
- `Komanda ilə koordinasiya`

Field-lər:

#### `Section`

Item hansı section-a aiddir.

#### `Competency`

Ən vacib field-lərdən biridir.

Bu field boş olarsa:

- item qiymətləndirilə bilər
- amma zəif nəticə competency əsaslı training link yaratmaz

#### `Criterion name`

Item-in qısa adı.

#### `Description`

Ətraflı izah.

#### `Impact on result (%)`

Həmin item-in yekun nəticədə çəkisi.

#### `Low score threshold`

Bu item üzrə hansı baldan aşağı nəticə zəif sayılacaq.

Misal:

- threshold `60`-dırsa, `45` score zəif trigger hesab edilir

#### `Requires comment`

Bu field aktivdirsə, evaluator-dan comment gözlənilir.

#### `Sort order`

Section daxilində item-lərin görünmə sırasıdır.

### 8.6. Item save ediləndə nə olur

`performance_form_template_items` cədvəlinə record yazılır.

Sonra harada istifadə olunur:

- score capture formunda
- evaluator workspace-də
- weak score detection məntiqində
- Təlim ehtiyacı inteqrasiyasında

### 8.7. Şablonlar tabında düzgün iş qaydası

Doğru sıra budur:

1. template yarat
2. həmin template üçün section yarat
3. həmin section üçün item yarat
4. item competency və threshold dəyərlərini yoxla
5. yalnız bundan sonra evaluation assignment et

## 9. Qiymətləndirmələr tabı

Bu tab real əməliyyat qatıdır.

Burada iki əsas iş görülür:

1. form təyinatı
2. manual score capture

Sağdakı `Son formalar` kartı isə bu iki işin nəticəsini izləmək üçündür.

### 9.1. Form təyinatı bloku

Bu blok yeni `performance_form` yaradır.

Field-lər:

#### `Cycle`

Form hansı qiymətləndirmə dövrünə aiddir.

#### `Template`

Form hansı skeleton-dan qurulacaq.

#### `Employee`

Qiymətləndirilən əməkdaş.

#### `Manager`

Manager evaluator. Bu istifadəçi manager score-larını dolduracaq.

#### `HR reviewer`

HR evaluator. Bu istifadəçi HR baxışı və əlavə review üçün istifadə olunur.

### 9.2. Form təyinatı save ediləndə nə olur

`performance_forms` cədvəlində record yaranır və ya edit rejimində update olunur.

Bunun təsiri:

- `Son formalar` kartında görünür
- evaluator workspace-də assignment kimi görünür
- score capture dropdown-ında seçilə bilir
- sonradan form reports-a düşür

### 9.3. Score capture bloku

Bu blok admin/workbench səviyyəsində manual score yazmaq üçündür.

Bu, evaluator workspace-in alternativi deyil. Daha çox əməliyyat və düzəliş qatıdır.

Field-lər:

#### `Evaluation form`

Bal yazılacaq form.

#### `Item`

Bal yazılacaq konkret kriteriya.

#### `Evaluator type`

Hazır seçimlər:

- `self`
- `manager`
- `hr`

Bu field score-un hansı baxışa aid olduğunu təyin edir.

#### `Score`

Verilən bal.

#### `Comment`

Əlavə izah və əsaslandırma.

### 9.4. Score save ediləndə nə olur

`performance_form_scores` cədvəlində record yaranır və ya update olunur.

Sonra nə baş verir:

- həmin item üzrə score yadda qalır
- final nəticə hesablanması üçün baza yaranır
- zəif threshold aşağı düşərsə weak link səbəbi yarana bilər
- evaluator summary və `Son formalar` kartı yenilənir

### 9.5. Sağdakı `Son formalar` kartı nəyi göstərir

Bu kart son yaradılmış və ya son işlənən formaları göstərir.

Kartda görünən əsas məlumat:

- işçi adı
- cycle
- template
- manager və HR reviewer
- self/manager/hr status-ları
- yekun kateqoriya
- yekun score

Bu kart:

- tam arxiv deyil
- qısa summary vitrini-dir

Tam və filtrli baxış üçün `Tam siyahılar` tabına keçmək lazımdır.

## 10. Evaluator workspace

Route:

- `/performance-evaluation/evaluator`

Bu ayrıca iş sahəsidir və manager/reviewer üçün daha təmiz istifadə ssenarisidir.

### 10.1. Sol panel: `Mənə təyin olunan formalar`

Bu panel evaluator-a aid formaları göstərir.

Filter-lər:

#### `Search`

İşçi adı, cycle, template üzrə axtarış üçündür.

#### `Role filter`

Hazır seçimlər:

- `all`
- `manager`
- `hr`

#### `Status filter`

Hazır seçimlər:

- `all`
- `pending`
- `submitted`

Bu paneldə kartların üzərində qalan meyar sayı da görünür.

### 10.2. Orta panel: `Bal formu`

Bu panel seçilmiş form üzrə meyar-me yar score daxil etmək üçündür.

Əsas field-lər:

#### `Qiymətləndirmə forması`

Seçilmiş form.

#### `Meyar`

Seçilmiş item.

#### `Bal`

Verilən score.

#### `Şərh`

İzah.

`Balı yadda saxla` edəndə:

- form score yenilənir
- həmin form üzrə progress badge yenilənir
- dashboard-dakı `Son formalar` summary-si də yenilənir

### 10.3. Sağ panel: `Assigned reviews`

Bu panel reviewer-ə düşən open-answer cavablarını göstərir.

Bu hissə test review xəttinə aiddir.

## 11. Testlər tabı

Bu tab test xəttini idarə edir.

Burada 6 əsas blok var:

1. `Test bank setup`
2. `Test question setup`
3. `Test session setup`
4. `Attempt capture`
5. `Attempt finalize`
6. `Open answer review`

Sağda isə `TestsSummary` child kartı var.

### 11.1. Test bank setup

Bu blok test bank yaradır.

Field-lər:

#### `Test bank name`

Bankın adı.

#### `Test bank code`

Qısa identifikator.

#### `Pass score`

Keçid balı.

#### `Duration minutes`

İmtahan müddəti.

#### `Max attempts`

İcazə verilən maksimum cəhd sayı.

#### `Description`

Bankın izahı.

#### `Is active`

Bank istifadəyə açıqdır ya yox.

### 11.2. Test bank save ediləndə nə olur

`performance_test_banks` cədvəlində record yaranır.

Sonra:

- sual formunda selectable olur
- session formunda selectable olur
- `Son test bankları` kartında görünür

### 11.3. Test question setup

Bu blok bank daxilində sual yaradır.

Field-lər:

#### `Test bank`

Sual hansı banka aiddir.

#### `Competency`

Sual hansı competency-ni ölçür.

Bu sahə sonradan skill gap və training link üçün vacibdir.

#### `Question type`

Hazır seçimlər:

- `multiple_choice`
- `open_answer`
- `case_study`
- `behavioral`

#### `Prompt`

Sual məzmunu.

#### `Description`

Əlavə izah.

#### `Max score`

Maksimal bal.

#### `Sort order`

Sual sırası.

#### `Options text`

Variantlı suallar üçün seçimlər.

Format bu məntiqə uyğundur:

- hər variant ayrıca sətirdə
- düzgün cavab ayrıca formatla işarələnir

#### `Is active`

Sual istifadəyə açıqdır ya yox.

### 11.4. Test question save ediləndə nə olur

`performance_test_questions` və sual tipindən asılı olaraq option record-ları yaranır.

Sonra:

- session bankı ilə əlaqəli istifadə olunur
- attempt capture mərhələsində selectable olur
- competency bağlantısı skill measurement üçün baza yaradır

### 11.5. Test session setup

Bu blok testin konkret işçiyə verilməsini qurur.

Field-lər:

#### `Cycle`

Session hansı cycle-a aiddir.

#### `Test bank`

Hansı bank işçiyə veriləcək.

#### `Personnel`

İmtahan verən əməkdaş.

#### `Reviewer`

Open-answer və manual review cavablarını yoxlayan şəxs.

#### `Scheduled at`

Planlanan tarix.

#### `Available until`

Son istifadə tarixi.

#### `Pass score`

Bank default dəyərini override edə bilər.

#### `Duration minutes`

Bank default dəyərini override edə bilər.

#### `Max attempts`

Bank default dəyərini override edə bilər.

#### `Status`

Hazır seçimlər:

- `assigned`
- `in_progress`
- `completed`
- `closed`

### 11.6. Test session save ediləndə nə olur

`performance_test_sessions` cədvəlində record yaranır.

Sonra:

- attempt capture-da seçilə bilir
- tests summary-də nəticələrə bağlanır
- reviewer queue üçün kontekst yaradır

### 11.7. Attempt capture

Bu blok test cavablarını yazmaq üçündür.

Bu, bugünkü moduldə admin/workbench və ya manual əməliyyat qatı kimidir.

Field-lər:

#### `Test session`

Cavab hansı təyinata aiddir.

#### `Question`

Hansı sual cavablanır.

#### `Attempt no`

Neçənci cəhddir.

#### `Option`

Auto-scored sual üçün seçilən variant.

#### `Answer text`

Open-answer, case-study və behavioral suallar üçün mətn cavabı.

### 11.8. Attempt answer save ediləndə nə olur

Əvvəlcə uyğun `performance_test_attempt` record-u tapılır və ya yaradılır.

Sonra `performance_test_attempt_answers` record-u yaranır və ya update olunur.

Sual auto-scored-dursa:

- düzgün variantla müqayisə edilə bilər
- ilkin score sistemi doldura bilər

Sual manual review tələb edirsə:

- cavab `pending review` növbəsinə düşür

### 11.9. Attempt finalize

Bu blok cəhdi yekunlaşdırır.

Field:

#### `Attempt`

Yekunlaşdırılacaq cəhd.

`Finalize` sonrası:

- attempt status-u dəyişir
- score və percentage hesablanır
- open-answer pending qalırsa review pending status-u saxlanır
- zəif nəticə varsa competency əsaslı training link yarana bilər

### 11.10. Open answer review

Bu blok open-answer cavablarına manual score vermək üçündür.

Field-lər:

#### `Answer`

Review ediləcək cavab.

#### `Review score`

Reviewer-in verdiyi bal.

#### `Feedback`

Reviewer rəyi.

Save sonrası:

- answer review status-u yenilənir
- attempt-in ümumi nəticəsi yenidən hesablanır
- tests summary kartı yenilənir

## 12. Test məlumatları bu gün harada toplanır

Bu sual çox vacibdir, çünki test xətti artıq yalnız qısa summary kartları ilə məhdud deyil.

### 12.1. Test banklar

Banklar iki yerdə görünür:

- `Testlər` tabında `Son test bankları` kartında
- `Tam siyahılar` tabında `test_banks` entity siyahısında

Yəni həm qısa vitrin, həm də ayrıca paginated arxiv var.

### 12.2. Sual bankı

Sual datası da ayrıca görünür:

- `Testlər` tabında sual yaratma workbench-ində
- `Tam siyahılar` tabında `test_questions` entity siyahısında

Detail panel açıldıqda:

- sual tipi
- bank
- competency
- max score
- option-lar

görünür.

### 12.3. Test təyinatları

Yəni `test sessions`.

Bunlar üç səviyyədə izlənir:

- `Testlər` tabında session setup formu
- `Tam siyahılar` tabında `test_sessions` arxivi
- `Test workspace` daxilində işçiyə görünən təyinat kartları

Yəni session artıq ayrıca arxiv kimi də izlənir.

### 12.4. Cavablar və cəhdlər

Bunlar indi bir neçə yerdə görünür:

- `TestsSummary` daxilində son cəhdlər
- `Tam siyahılar` tabında `attempts`
- `Tam siyahılar` tabında `test_answers`
- `Test workspace` daxilində `Attempt history`
- printable `test transcript`

Bu o deməkdir ki, həm operativ baxış, həm də tam audit izi mövcuddur.

### 12.5. Pending review cavablar

Bunlar:

- `TestsSummary`
- `Evaluator workspace`
- `test_answers` arxiv siyahısı

üzərində görünür.

### 12.6. Professional nəticə

Test authoring, session assignment, attempt history, answer audit və report qatları artıq ayrıca görünən səviyyəyə çıxıb.

Bugünkü strukturda artıq bunlar hazırdır:

- `Test banks list`
- `Question bank list`
- `Test sessions list`
- `Attempts and answers list`
- `Test reporting dashboard`

Bu boşluq bağlanıb.

## 13. Bu test axını peşəkar baxımdan nə qədər düzgündür

Qısa cavab:

- bugünkü arxitektura peşəkar xəttə çox yaxındır
- çünki artıq `authoring`, `assignment`, `test taking`, `review`, `reporting` ayrılıb
- admin daxilində qalan `manual answer capture` isə artıq əsas user journey deyil, ops/demo workbench rolundadır

### 13.1. Nə doğrudur

Peşəkar sistemlərdə adətən ayrıca olur:

1. `Authoring`
   - bank yaratmaq
   - sual yaratmaq
   - scoring rule yaratmaq
2. `Delivery`
   - testi konkret istifadəçiyə vermək
   - işçinin testi ayrıca test ekranında etməsi
3. `Review`
   - open-answer review
4. `Reporting`
   - nəticə və analitika

### 13.2. Bugünkü sistem nə edir

Bugünkü sistemdə:

- `Authoring` var
- `Assignment` var
- `Test workspace` var
- `Review` var
- `Reporting` var

Yəni əsas xətt artıq ayrılıb.

Admin daxilində qalan `attempt answer capture` hələ mövcuddur, amma bu daha çox:

- daxili simulyasiya
- data bərpası
- admin dəstək müdaxiləsi

üçündür.

### 13.3. Bugünkü yanaşma tam səhvdirmi

Yox.

Hazırda ən doğru bölgü belədir:

- `Admin dashboard`: bank, sual, import, session, report
- `Candidate/Test taker screen`: real test həlli
- `Reviewer workspace`: manual review
- `Reports screen`: analitika və audit

Bu bölgü artıq mövcuddur.

### 13.4. Tövsiyə

Hazırkı memarlıqda əsas növbəti tövsiyələr bunlardır:

1. explicit `user -> personnel` link-lərini admin səviyyəsində görünən və idarə olunan etmək
2. test report-lara daha çox analitika əlavə etmək
3. import şablonunu istifadəçi üçün daha çox nümunə dataset ilə zənginləşdirmək
4. əgər siyasət tələb edirsə, `attempt answer capture` admin workbench-ini yalnız səlahiyyətli rollara açıq saxlamaq

## 14. Hazırda hansı hesabatlar var

Bugünkü moduldə mövcud report-lar bunlardır:

### 14.1. `Forms report`

Hər form üzrə:

- personnel
- tabel no
- cycle
- template
- manager
- HR reviewer
- final score
- final category
- self status
- manager status
- HR status

### 14.2. `Summary report`

Cycle + template qruplaşdırması ilə:

- forms count
- average score
- high count
- medium count
- weak count

### 14.3. `Weak links report`

Competency üzrə zəif link-ləri göstərir.

### 14.4. `Weak pivot report`

Zəif sahələri competency, priority və status üzrə pivot edir.

### 14.5. `Audit report`

Əsas əməliyyat izlərini çıxarır.

### 14.6. `Print summary`

Brauzerdən PDF kimi saxlanıla bilən çap görünüşüdür.

## 15. Test report-larında bu gün nə var, nə hələ genişlənə bilər

Bu gün hazır olan test report-ları:

1. `Test sessions report`
   - hansı bank kimə verilib
   - reviewer kimdir
   - scheduled / available until
   - status
2. `Attempt results report`
   - hansı cəhd neçə bal alıb
   - passed / failed
   - neçə faiz toplayıb
3. `Answer detail report`
   - hansı testdə hansı sual cavablanıb
   - open-answer review status-u
   - final score
4. `Printable transcript`
   - attempt summary
   - sual-sual breakdown
   - reviewer feedback timeline
5. `Personnel test history`
   - `Test workspace` daxilində cəhd tarixçəsi

Hələ genişlənə biləcək professional əlavələr:

- competency üzrə trend qrafiki
- ən çox səhv cavablanan suallar
- reviewer turnaround time
- department / structure üzrə test performansı
- keçid həddi ilə real nəticə fərqi

Yəni əsas report qatı hazırdır, amma analitika dərinliyi gələcəkdə daha da zənginləşdirilə bilər.

## 16. Tam siyahılar tabı

Bu tab uzunmüddətli nəzarət üçündür.

Hazır entity-lər:

- `forms`
- `templates`
- `items`
- `test_banks`
- `test_questions`
- `test_sessions`
- `attempts`
- `test_answers`
- `weak_links`

Bu o deməkdir ki, hazırda tam paginated baxış bunlar üçündür:

- performans formaları
- template-lər
- item-lər
- test bankları
- test sualları
- test sessiyaları
- test cəhdləri
- test cavabları
- zəif sahə link-ləri

Bu boşluq artıq bağlanıb. Test authoring və delivery datası da ayrıca arxiv şəklində görünür.

## 17. Hesabatlar tabı

Bu tab report mərkəzidir.

Burada iki böyük qrup var:

1. performans və weak link report-ları
2. test delivery və answer audit report-ları

Hazır report-lar:

- forms report
- summary report
- weak links report
- weak pivot report
- audit report
- test sessions report
- test attempts report
- test answers report
- print summary

## 18. Save edəndə nə nəyə təsir edir

Bu bölmə ən vacib bölmələrdən biridir.

### 18.1. Cycle save

Yenilənən yerlər:

- cycle select-lər
- recent cycles
- assignment və session context

### 18.2. Template save

Yenilənən yerlər:

- template select-lər
- recent templates
- section və evaluation dependency

### 18.3. Section save

Yenilənən yerlər:

- section select
- recent sections
- item setup dependency

### 18.4. Item save

Yenilənən yerlər:

- item select
- recent items
- score capture
- future training link logic

### 18.5. Evaluation form save

Yenilənən yerlər:

- `Son formalar`
- evaluator workspace assigned forms
- score capture form options

### 18.6. Score save

Yenilənən yerlər:

- form progress
- final nəticə
- weak link logic
- `Son formalar`
- evaluator workspace progress badge

### 18.7. Test bank save

Yenilənən yerlər:

- bank options
- `Son test bankları`
- question və session dependency

### 18.8. Test question save

Yenilənən yerlər:

- question options
- bank scoring logic
- attempt capture dependency

### 18.9. Test session save

Yenilənən yerlər:

- session options
- attempt capture dependency
- reviewer queue konteksti

### 18.10. Attempt answer save

Yenilənən yerlər:

- attempt record
- tests summary
- pending review queue

### 18.11. Attempt finalize

Yenilənən yerlər:

- attempt nəticəsi
- percentage
- passed/failed
- weak link / training integration
- tests summary

### 18.12. Review answer save

Yenilənən yerlər:

- cavab review status-u
- attempt totals
- reviewer queue
- tests summary

## 19. Test xəttinin tam əməliyyat bələdçisi

Bu bölmə yalnız `Testlər` xətti üçündür.

Əgər istifadəçi uşağa izah edirmiş kimi sadə dildə başa düşmək istəyirsə, bu məntiqi belə yadda saxla:

- `Test bank` bir imtahan qovluğudur
- `Question` həmin qovluqdakı suallardır
- `Test session` həmin imtahanın konkret əməkdaşa verilməsidir
- `Attempt` işçinin imtahanı neçə dəfə həll etdiyini göstərən cəhddir
- `Answer` hər sual üzrə verilən cavabdır
- `Review` açıq cavabların insan tərəfindən yoxlanmasıdır
- `Finalize` həmin cəhdin bağlanmasıdır
- `Report` sonradan nə baş verdiyini izləmək üçündür

### 19.1. Test xəttində kim nə edir

#### `HR / Admin`

Bu rol:

- test bank yaradır
- sual yaradır
- test session təyin edir
- report və audit nəzarəti aparır

#### `İşçi`

Bu rol:

- özünə təyin olunan testi `Test workspace`-də açır
- cavab yazır
- qaralamanı saxlayır və ya auto-save ilə davam edir
- vaxt bitmədən attempt-i yekunlaşdırır

#### `Reviewer`

Bu rol:

- açıq cavabları yoxlayır
- review score verir
- feedback yazır

### 19.2. Düzgün iş sırası

Test xəttində professional ardıcıllıq belə olmalıdır:

1. əvvəl `Test bank` yarat
2. sonra həmin banka sualları əlavə et
3. variantlı sualların option-larını düzgün qur
4. açıq suallarda max score və məqsədi düzgün təyin et
5. yalnız bundan sonra `Test session` ilə əməkdaşa test təyin et
6. işçi testi `Test workspace`-də həll etsin
7. open-answer varsa reviewer onu yoxlasın
8. cəhd yekunlaşsın
9. nəticəni `Tam siyahılar` və `Hesabatlar` tabında audit et

Ən böyük səhv budur:

- bankı yarımçıq saxlayıb session təyin etmək
- sual option-ları düzgün deyilkən testi istifadəyə vermək
- open-answer review gözləyən attempt üçün yekun nəticəni hazır hesab etmək

### 19.3. Test bank nədir

`Test bank` eyni mövzu və ya eyni bacarıq qrupu üzrə imtahan skeletidir.

Misal:

- `İllik imtahan`
- `Excel bacarıqları`
- `İş etikası testi`

Bir bank:

- adını daşıyır
- keçid balını daşıyır
- default vaxt limitini daşıyır
- maksimum cəhd sayını daşıyır
- onun içindəki bütün sualların konteyneri olur

### 19.4. Test bank field-ləri nə deməkdir

#### `Name`

Testin ekranda görünən əsas adıdır.

Bu ad:

- session kartında görünür
- test workspace-də görünür
- report-larda görünür

#### `Code`

Daxili identifikasiya üçündür.

Bu field:

- qısa kodlama
- arxiv və audit
- eyni adda bankları fərqləndirmək

üçün faydalıdır.

#### `Description`

Bu bankın hansı məqsəd üçün yaradıldığını yazmaq üçündür.

Nümunə:

- yeni işə qəbul üçün ilkin bilik yoxlaması
- daxili etik davranış testi
- proqram bilik ölçümü

#### `Pass score`

Keçid həddidir.

Bu faizdir. Məsələn `60` o deməkdir ki, yekun faiz `60%` və ya daha çox olmalıdır.

#### `Duration minutes`

İşçiyə verilən vaxt limitidir.

Bu dəyər:

- test başlayanda sayğac üçün əsas olur
- vaxt bitəndə auto-submit davranışını müəyyən edir

#### `Max attempts`

Eyni test üzrə neçə dəfə cəhd etmək olar.

Misal:

- `1` → yalnız bir dəfə həll edilə bilər
- `2` → birinci cəhd uğursuz olsa və siyasət icazə verirsə ikinci cəhd açılar

#### `Is active`

Bank hazırda istifadəyə açıqdır ya yox.

Aktiv olmayan bank:

- yeni session assignment üçün istifadə edilməməlidir

### 19.4.A. Sualları import ilə əlavə etmək necə işləyir

Əgər sual sayı çoxdursa, onları bir-bir yazmaq əvəzinə import etmək daha doğrudur.

Bu modulda professional yol belədir:

1. `Testlər` tabında `Sual importu` blokuna keç
2. əvvəl `Excel şablonunu yüklə`
3. şablondakı sütun adlarını pozmadan doldur
4. istəyirsənsə import-u mövcud banka bağla, istəmirsənsə sistem `bank_code` və `bank_name` ilə bankı özü yaratsın
5. faylı yüklə
6. `Suaları import et` düyməsini bas

Şablonda əsas sütunlar bunlardır:

- `bank_code`
- `bank_name`
- `question_type`
- `prompt`
- `description`
- `competency_name`
- `max_score`
- `sort_order`
- `option_1` ... `option_4`

Variantlı sualda `option_*` sütunları istifadə olunur.

Open-answer, case-study və behavioral suallarda isə əsasən:

- sual mətni
- competency
- max score

kifayət edir.

Import nə edir:

- lazım olsa bank yaradır
- competency-ni ada görə tapır
- eyni bankda eyni prompt varsa onu update edir
- variantlı sual üçün option-ları yenidən sinxronlaşdırır

Import zamanı ən böyük risk:

- competency adının sistemdə olmaması
- sual tipinin yanlış yazılması
- option-ların natamam verilməsi

Ona görə importdan əvvəl template preview kartındakı sütun quruluşunu yoxlamaq vacibdir.

### 19.5. Sual tipləri ayrı-ayrı necə işləyir

#### `Multiple choice`

Bu, variantlı sualdır.

İstifadəçi variantlardan birini seçir.

Bu tip sualda:

- option-lar olmalıdır
- hər option-un düzgünlüyü qeyd olunmalıdır
- hər option-un `score_value` dəyəri ola bilər

Bu o deməkdir ki, variantlı sual yalnız `doğru / yanlış` məntiqi ilə yox, qismən bal ilə də işləyə bilər.

Misal:

- düzgün variant `100`
- natamam doğru variant `50`
- tam yanlış variant `0`

Bu tip suallar auto-scored olur.

#### `Open answer`

Bu, mətn şəklində cavab verilən sualdır.

İşçi sərbəst cavab yazır.

Bu tip sualda sistem:

- mətni saxlayır
- auto-final score vermir
- reviewer qərarını gözləyir

Yəni nəticə yalnız insan review-dən sonra tamamlanır.

#### `Case study`

Bu, ssenariyə əsaslanan açıq cavabdır.

Texniki olaraq open-answer ailəsinə yaxındır, amma business baxımdan istifadəçiyə real vəziyyət verib qərar, analiz və həll tələb edir.

Bu tip də manual review tələb edir.

#### `Behavioral`

Davranış və situasiya əsaslı açıq cavabdır.

İşçinin:

- necə düşünməsi
- necə reaksiya verməsi
- hansı davranışı seçməsi

yoxlanılır.

Bu tip də manual review tələb edir.

### 19.6. Variantlı sual option-ları necə qurulmalıdır

Variantlı sualda option-lar düzgün qurulmasa bütün test xətti zədələnir.

Diqqət ediləcək qaydalar:

- label aydın olmalıdır
- doğru variant işarələnməlidir
- score_value siyasətə uyğun verilməlidir
- eyni sualda bir neçə option-un yüksək bal verməsi yalnız qəsdən edilirsə istifadə olunmalıdır

Professional tövsiyə:

- sual yazan şəxs ilə review edən şəxs mümkün qədər ayrı olsun
- variantlı suallarda qeyri-müəyyən label-lardan qaç

### 19.7. Test session nədir

`Test session` bankın konkret işçiyə verilməsidir.

Bank özü ümumi şablondur.

Session isə həmin bankın:

- hansı əməkdaşa
- hansı reviewer-lə
- hansı periodda
- hansı limitlə

verildiyini göstərir.

### 19.8. Test session field-ləri nə deməkdir

#### `Cycle`

Bu session hansı performans dövrünə bağlıdır.

Boş qala bilər, amma period context üçün doldurulması məsləhətdir.

#### `Test bank`

Hansı bankın istifadə ediləcəyini göstərir.

Bu seçimdən sonra suallar da dolayı yolla həmin bankdan gəlir.

#### `Personnel`

Testi həll edəcək şəxsdir.

Ən vacib field-lərdən biridir. Yanlış personnel seçilərsə:

- yanlış şəxsin workspace-ində test görünər
- report səhv şəxsin üzərinə düşər

#### `Reviewer`

Açıq cavabları kim yoxlayacaq.

Əgər testdə open-answer, case-study və ya behavioral sual varsa, reviewer field-i xüsusilə vacibdir.

#### `Scheduled at`

Planlaşdırılmış tarixdir.

Bu field idarəetmə və planlama baxışı üçündür.

#### `Available until`

Bu session-un son istifadə tarixidir.

İstifadəçi bu tarixdən sonra testi açmamalıdır.

#### `Pass score`

Session səviyyəsində ayrıca keçid həddi verilirsə, bankdakı default dəyəri override edir.

#### `Duration minutes`

Session səviyyəsində ayrıca vaxt limiti verilirsə, bankdakı default dəyəri override edir.

#### `Max attempts`

Session səviyyəsində ayrıca cəhd sayı verilirsə, bankdakı default dəyəri override edir.

#### `Status`

Adətən bu statuslar istifadə olunur:

- `assigned`
- `in_progress`
- `completed`
- `closed`

Məna:

- `assigned` → verilib, amma aktiv cəhd başlamayıb
- `in_progress` → cəhd başlayıb
- `completed` → session üzrə aktiv iş bitib
- `closed` → artıq istifadə üçün bağlıdır

### 19.9. Test workspace necə işləyir

`Test workspace` işçi üçün ayrılmış həll ekranıdır.

Burada sistem belə davranır:

1. istifadəçinin özünə uyğun personnel record-u explicit `user -> personnel` link ilə tapılır
2. ona təyin olunan session-lar yüklənir
3. prioritet olaraq hələ həll edilə bilən session seçilir
4. aktiv cəhd varsa həmin cəhd davam edir
5. yoxdursa işçi `Testə başla` ilə yeni cəhd açır

#### `User -> personnel` link niyə vacibdir

Test workspace testləri `user` üzərindən açır, amma session-lar `personnel` üzərinə təyin olunur.

Bu səbəbdən sistem bu iki varlığı düzgün bağlamalıdır.

Bugünkü professional yanaşma budur:

- əvvəl explicit link yoxlanılır
- uyğun link varsa testlər birbaşa həmin personnel üçün açılır
- fallback resolve yalnız ilkin bağ yaratmaq üçündür

Bu nəyi həll edir:

- yanlış şəxsə test görünməsi riski azalır
- email və ad fərqlərinə görə testin itməsinin qarşısı alınır
- workspace daha stabil və daha sürətli işləyir

### 19.10. Test workspace içində görünən hissələr

#### `Assigned test sessions`

Soldakı sessiya kartlarıdır.

Buradan:

- hansı testin verildiyini
- statusunu
- son tarixi
- max attempts və pass score məlumatını

görmək olur.

#### `Attempt history`

Bu blok əvvəlki cəhdləri göstərir.

Buradan görünür:

- attempt nömrəsi
- status
- bal
- faiz
- təhvil vaxtı

Bu blok audit üçün çox vacibdir.

#### `Attempt analytics`

Tamamlanmış cəhdlərdə sistem artıq analitik görünüş də verir.

Burada:

- sual-sual nəticə
- doğru / yanlış işarəsi
- final score
- reviewer feedback
- review timeline

görünür.

Bu blok xüsusilə open-answer və mixed testlər üçün çox faydalıdır.

#### `Printable transcript`

Bu, cəhdin çap və audit görünüşüdür.

Transcript daxilində:

- attempt summary
- sual breakdown
- seçilmiş variant və ya yazılmış cavab
- correct answer
- reviewer feedback
- timeline

yer alır.

#### `Test runner`

Əsas həll panelidir.

Burada:

- cari test adı
- status
- cavab sayı
- timer
- sual naviqasiyası
- aktiv sual
- cavab sahəsi
- save/finalize aksiyaları

yer alır.

### 19.11. Timer necə işləyir

Timer yalnız aktiv cəhd üçün işləyir.

Qaydalar:

- cəhd başlamayıbsa sayğac işləmir
- cəhd başlayanda countdown başlayır
- cəhd tamamlananda timer dayanır
- vaxt bitəndə sistem cəhdi auto-submit edir

İstifadəçi baxımından düzgün davranış belə olmalıdır:

- tamamlanmış cəhd üçün hələ də geri sayım getməməlidir
- vaxt bitibsə cavablar itirilməməlidir
- auto-submit baş veribsə bunu mesajla anlatmaq lazımdır

### 19.12. Auto-save necə işləyir

Auto-save istifadəçinin yazdığı cavabların itirilməməsi üçündür.

Bu sistemdə:

- cavab dəyişəndə draft dirty state yaranır
- heartbeat zamanı auto-save işləyir
- son auto-save vaxtı istifadəçiyə göstərilir

Bu, xüsusilə açıq suallarda vacibdir.

#### Auto-save nəyi saxlayır

- selected option
- answer text
- flag state

#### Auto-save nəyi etmir

- cəhdi yekunlaşdırmır
- review yaratmır
- nəticəni final hesab etmir

### 19.13. Sual işarələmə nədir

`Flag question` istifadəçi üçün şəxsi marker-dir.

Bu, sualın səhv olduğunu demir.

Sadəcə istifadəçinin:

- sonra qayıtmaq istədiyi
- əmin olmadığı
- yenidən baxmaq istədiyi

sualı qeyd etməsidir.

İşarələnmiş suallar:

- navigation-da fərqli tonla görünür
- active cəhd meta-sında saxlanılır
- cəhd davam edəndə itməməlidir

### 19.14. Attempt nə vaxt yaranır

Attempt bu hallarda yaranır:

- istifadəçi testə başlayır
- və ya ilk cavabı verəndə sistem writable attempt açır

Attempt yaradıldıqdan sonra:

- `started_at` yazılır
- session `assigned` idisə `in_progress` olur
- answer rows həmin attempt-ə yazılır

### 19.15. Yekunlaşdırma zamanı nə baş verir

`Cəhdi yekunlaşdır` basıldıqda sistem:

1. tələb olunan cavabları yoxlayır
2. çatışmayan sual varsa error verir
3. bütün cavabları draft-dan DB-yə yazır
4. boş qalan suallar üçün də answer row materialize edir
5. duration hesablayır
6. auto-scored sualları hesablayır
7. open-answer varsa `review_pending` vəziyyəti yaradır
8. yoxdursa `completed` statusuna keçir
9. faiz və keçid nəticəsini hesablayır
10. weak-area / training link məntiqini işlədir

### 19.16. `Time is up -> auto submit` necə işləyir

Vaxt bitəndə sistem:

- aktiv cəhdin expired olduğunu görür
- draft cavabları son dəfə yazır
- auto-submit edir
- session-u bağlayır
- istifadəçiyə bunun avtomatik baş verdiyini göstərir

Bu davranış professional baxımdan vacibdir, çünki:

- istifadəçi cavabını itirmir
- timer sonsuza qədər işləyib yanlış state yaratmır
- report-larda cəhd yarımçıq qalmır

### 19.17. Nə vaxt yeni test açılmalıdır

Əgər bir session tamamlanıbsa və başqa actionable session varsa, workspace növbəti uyğun testi açmalıdır.

Bu o deməkdir:

- köhnə test runner-da ilişib qalmamaq
- istifadəçini əl ilə hər dəfə geri dönməyə məcbur etməmək

Əgər başqa uyğun session yoxdursa, son tamamlanmış cəhd read-only görünüşdə qalmalıdır.

### 19.18. Reviewer xətti necə işləyir

Open-answer, case-study və behavioral suallar dərhal final nəticə vermir.

Bu suallar:

- answer kimi saxlanılır
- `pending review` olur
- reviewer queue-yə düşür

Reviewer sonra:

- cavabı oxuyur
- review score verir
- feedback yazır

və həmin attempt-in yekun nəticəsi yenilənir.

### 19.19. Test report-ları nə üçündür

`Hesabatlar` tabında test xətti üçün ayrıca report-lar var:

- `Test sessions report`
- `Test attempts report`
- `Test answers report`

Bu report-lar ilə cavab tapılır:

- hansı test kimə verilib
- kim neçə dəfə cəhd edib
- neçə bal toplayıb
- hansı cavab open-review gözləyib
- hansı sualda nə cavab yazılıb

### 19.20. Test xəttində professional qaydalar

- bank authoring ilə test həll ekranını qarışdırma
- open-answer olan testdə reviewer xəttini əvvəlcədən planla
- assignment etməzdən əvvəl bankı tam yoxla
- score_value siyasətini əvvəlcədən sabitləşdir
- report audit olmadan test nəticəsini son qərar kimi istifadə etmə

## 20. Ən çox edilən səhvlər

- cycle yaratmadan assignment-a keçmək
- template hazır olmadan form təyin etmək
- item-i competency-siz saxlamaq
- threshold-u mənasız vermək
- wrong bank ilə wrong question seçmək
- attempt finalize etmədən nəticə gözləmək
- open-answer review etmədən tam nəticə gözləmək
- test bank, question və session çoxaldıqca `Tam siyahılar` və `Hesabatlar` tabından istifadə etməmək

## 21. Yeni istifadəçi üçün ən qısa düzgün yol

Əgər modulu ilk dəfə açırsansa:

1. əvvəlcə `Dövrlər` tabını oxu və bir cycle yarat
2. sonra `Şablonlar` tabında template, section, item qur
3. item competency əlaqələrini yoxla
4. `Qiymətləndirmələr` tabında form təyin et
5. evaluator workspace-də bal topla
6. zəif nəticələri və training impact-i yoxla
7. əgər test xətti istifadə olunacaqsa, bank -> sual -> session -> cavab -> review -> finalize ardıcıllığı ilə get
8. sonda `Tam siyahılar` və `Hesabatlar` tabı ilə nəzarət et

## 22. Son nəticə

Bu modulun məntiqi belə yadda saxlanmalıdır:

- `Dövrlər` vaxtı idarə edir
- `Şablonlar` nəyi ölçəcəyini qurur
- `Qiymətləndirmələr` form əsaslı nəticəni yaradır
- `Testlər` bilik və bacarıq nəticəsini yaradır
- `Weak links` nəticəni inkişaf ehtiyacına çevirir
- `Reports` isə bütün bu əməliyyatı ölçülə bilən idarəetmə məlumatına çevirir

Bugünkü sistem işə yarayan və production-ready bir əməliyyat platformasıdır.

Amma test xətti böyüyəcəksə, gələcək professional hədəf belə olmalıdır:

- authoring ayrı
- delivery ayrı
- review ayrı
- reporting ayrıca analitika səviyyəsində
