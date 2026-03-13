# Training Needs User Guide

Bu sənəd `Training Needs` modulunu sistemi ilk dəfə açan istifadəçi üçün izah edir. Məqsəd yalnız ekran adlarını göstərmək deyil. Məqsəd odur ki, HR əməkdaşı bu sənədi oxuyaraq:

- modulun ümumi məntiqini başa düşsün
- hər tab və kartın nə iş gördüyünü bilsin
- hər formdakı field-in niyə lazım olduğunu anlasın
- `Save` edəndən sonra nə baş verdiyini görsün
- hansı işi hansı ardıcıllıqla etməli olduğunu bilsin
- ehtiyacdan sessiyaya, sessiyadan nəticəyə qədər prosesi təkbaşına idarə edə bilsin

Route: `/training-needs`

## 1. Modulun qısa məntiqi

Bu modul “təlim ehtiyacları siyahısı”ndan daha böyükdür. Burada dörd qat bir yerdə yaşayır:

1. baza quruluşu
   - kompetensiya qrupları
   - səviyyələr
   - kompetensiyalar
   - proqramlar
   - proqram-kompetensiya xəritəsi
2. ehtiyacın yaranması
   - rol tələbi
   - əməkdaş profili
   - manual need
   - performans və testdən gələn auto need
3. planlama və icra
   - annual plan
   - plan item
   - session proposal
   - training session
   - participant attendance
4. bağlanış və ölçmə
   - delivery record
   - certificate / document
   - feedback form və response
   - analytics və export

Qısa data axını belədir:

`Kompetensiya kataloqu -> Rol tələbi -> Əməkdaş profili -> Need -> Plan -> Plan item -> Session proposal -> Session -> Participant -> Delivery -> Feedback -> Analytics`

## 2. Yeni istifadəçi üçün tövsiyə olunan iş sırası

Modulu sıfırdan qurursansa, bu sıranı pozma:

1. `Kataloqlar` tabında baza lüğətlərini yarat
2. `Rol tələbləri` tabında vəzifə tələblərini yaz
3. `Profil və plan` tabında əməkdaş profillərini daxil et
4. Eyni tabda need-ləri yarat və ya auto gələnləri yoxla
5. `İllik planlama` tabında plan yarat
6. Plan item-ları review et və təsdiqlə
7. `Təlim təqvimi` tabında proposal-ları sessiyaya çevir
8. İştirakçıları və davamiyyəti idarə et
9. Sessiyanı tamamla
10. `Nəticələr` tabında feedback və certificate hissəsini bağla
11. `Analitika` və `Tam siyahılar` hissəsində nəticəni yoxla

Əgər bu ardıcıllıq pozularsa, sonrakı select-lərdə seçim çıxmır, suggestion keyfiyyəti düşür və analytics boş görünür.

## 3. Tab-lar nə üçündür

- `Xülasə`
  - ümumi vəziyyətə baxış üçündür
- `Kataloqlar`
  - modulun lüğət və referans bazası burada qurulur
- `Rol tələbləri`
  - hər vəzifə üçün lazım olan kompetensiya və səviyyə burada yazılır
- `Profil və plan`
  - əməkdaşın cari səviyyəsi və fərdi need burada formalaşır
- `İllik planlama`
  - need-lər plan item-a çevrilir və HR review edilir
- `Təlim təqvimi`
  - approved item real training session olur
- `Nəticələr`
  - sessiya tamamlanır, delivery və feedback bağlanır
- `Analitika`
  - rəhbərlik və HR üçün xülasə göstəriciləridir
- `Tam siyahılar`
  - paginated, filter-li, detail panel-li list workspace-dir

## 4. Xülasə tabı

Bu tab əməliyyat aparmaq üçün deyil. Bu tab “sistem hazırda hansı vəziyyətdədir?” sualına cavab verir.

Burada görünən sayğaclar:

- `Groups`
- `Levels`
- `Competencies`
- `Programs`
- `Program maps`
- `Requirements`
- `Profiles`
- `Needs`
- `Plans`
- `Plan items`
- `Sessions`
- `Delivered trainings`
- `Feedback forms`

Əhəmiyyətli qeyd:

- dashboard kartları limitlidir
- burada tam siyahı göstərilmir
- tam izləmə üçün `Tam siyahılar`, export və print istifadə olunur

## 5. Kataloqlar tabı

Bu tab modulun təməlidir. Buradakı səhvlər bütün sonrakı mərhələləri korlayır.

### 5.1. Kompetensiya qrupları formu

Field-lər:

- `Qrup adı`
  - kompetensiyanı hansı ailədə topladığını göstərir
  - nümunə: Liderlik, Texniki bacarıqlar, Tədris bacarıqları
- `Açıqlama`
  - qrupun nəyi əhatə etdiyini izah edir
- `Sıra`
  - listələrdə görünüş ardıcıllığı üçündür
- `Aktivdir`
  - deaktiv qrup arxiv kimi qalır, yeni iş üçün istifadə edilməyə bilər

Save olunanda nə olur:

- `training_competency_groups` cədvəlinə yeni yazı düşür
- qrup sonradan `Kompetensiyalar` formunda seçilən lookup olur

Bu formdan sonra hara təsir edir:

- competency create form
- listələr və analytics qruplaşdırmaları

### 5.2. Kompetensiya səviyyələri formu

Field-lər:

- `Səviyyə adı`
  - nümunə: Başlanğıc, Orta, Güclü, Ekspert
- `Bal`
  - sistemin müqayisə üçün istifadə etdiyi ədədi dəyərdir
- `Açıqlama`
  - səviyyənin izahı
- `Sıra`
  - görünüş sırası
- `Standartdır`
  - default səviyyə kimi istifadə oluna bilər

Save olunanda nə olur:

- `training_levels` cədvəlinə yazılır
- `Bal` dəyəri gələcək gap hesablarında və suggestion scoring-də istifadə olunur

Bu formdan sonra hara təsir edir:

- profil formu
- role requirement formu
- need formu
- program map
- analytics

### 5.3. Kompetensiyalar formu

Field-lər:

- `Qrup`
  - kompetensiyanın aid olduğu ailə
- `Kompetensiya adı`
  - sistemin əsas competency identifikatoru
- `Açıqlama`
  - istifadəçi yönlü izah
- `Məcburidir`
  - bəzi ssenarilərdə prioriteti yüksəldə bilər
- `Aktivdir`

Save olunanda nə olur:

- `training_competencies` cədvəlinə yazılır
- bütün sonrakı modullar üçün seçilə bilən competency yaranır

Bu formdan sonra hara təsir edir:

- program map
- role requirement
- employee profile
- training need
- performance item link-ləri
- performance test question-ları

### 5.4. Təlim proqramları formu

Field-lər:

- `Proqram adı`
- `Proqram kodu`
- `Keçirilmə növü`
  - `internal`, `external`, `hybrid`
- `Saat`
- `Açıqlama`
- `Aktivdir`

Save olunanda nə olur:

- `training_programs` cədvəlinə yazılır
- proqram ehtiyac və session form-larında seçilə bilir

Bu formdan sonra hara təsir edir:

- need queue
- plan suggestion
- session form
- delivery və report hissəsi

### 5.5. Təlim proqramı -> kompetensiya xəritəsi formu

Bu form kataloq tabının ən kritik hissələrindən biridir.

Field-lər:

- `Təlim proqramı`
- `Kompetensiya`
- `Hədəf səviyyə`

Save olunanda nə olur:

- `training_program_competency_map` cədvəlinə yazılır

Bu formdan sonra hara təsir edir:

- sistem hansı proqramı hansı gap üçün tövsiyə etsin
- suggestion score hansı proqramı önə çəksin
- need üçün `recommended program` daha düzgün seçilsin
- plan və session proposal keyfiyyəti yüksəlsin

Əgər mapping zəifdirsə:

- recommendation keyfiyyəti düşür
- eyni need üçün yanlış proqram təklif oluna bilər

## 6. Rol tələbləri tabı

Bu tab “bu vəzifədə ideal olaraq hansı competency və hansı səviyyə olmalıdır?” sualına cavab verir.

### 6.1. Role competency requirement formu

Field-lər:

- `Vəzifə`
- `Kompetensiya`
- `Tələb olunan səviyyə`
- `Prioritet`
  - `low`, `medium`, `high`
- `Məcburidir`

Save olunanda nə olur:

- `role_competency_requirements` cədvəlinə yazılır

Bu formdan sonra hara təsir edir:

- employee profile ilə müqayisə
- gap hesabı
- need suggestion
- coverage analytics
- top gap positions

Bu tab tamamlanmadan profil və need işləməyə başlasa belə, sistemin “niyə bu əməkdaşın ehtiyacı var?” sualına sübut bazası zəifləyir.

## 7. Profil və plan tabı

Bu tab iki əsas işi görür:

1. əməkdaşın cari competency profilini yazır
2. training need yaradır və idarə edir

### 7.1. Employee competency profile formu

Field-lər:

- `Əməkdaş`
- `Kompetensiya`
- `Mövcud səviyyə`
- `Mənbə`
  - `manual`, `manager_review`, `hr_review`, `exam`
- `Son qiymətləndirmə tarixi`

Save olunanda nə olur:

- `employee_competency_profiles` cədvəlinə yazılır

Bu formdan sonra hara təsir edir:

- sistem əməkdaşın mövcud durumunu bilir
- role requirement ilə fərq hesablanır
- smart suggestion və coverage hesabları daha dəqiq olur

Praktik tövsiyə:

- hər competency üçün ən az bir profil girişi olmalıdır
- tarix sahəsi boş qalmamalıdır; köhnə qiymətləndirmə ilə yeni qərar vermək risklidir

### 7.2. Training need queue formu

Bu form real inkişaf ehtiyacını yaradır.

Field-lər:

- `Əməkdaş`
- `Kompetensiya`
- `Tövsiyə olunan proqram`
- `Hədəf səviyyə`
- `Prioritet`
- `Mənbə`
  - `manual`
  - `manager_request`
  - `employee_request`
  - `manager_review`
  - `hr_review`
  - `performance_gap`
  - `skill_gap`
  - `exam`
- `Status`
  - `draft`, `review`, `approved`, `planned`, `completed`
- `Səbəb`
- `Plan qeydi`
- `Hədəf tamamlanma tarixi`

Save olunanda nə olur:

- `training_need_items` cədvəlinə yazılır
- bu write həm need queue-da, həm də sonrakı plan suggestion-larda görünür

Bu formdan sonra hara təsir edir:

- annual planning board
- suggestion board
- session participant auto-fill
- analytics

Field-lərin praktik mənası:

- `Recommended program`
  - gələcək plan və session üçün operativ seçimi sürətləndirir
- `Source`
  - ehtiyacın haradan gəldiyini göstərir; audit və source mix analytics üçün vacibdir
- `Status`
  - işin mərhələsini göstərir
- `Reason`
  - niyə yaradıldığını sübut edir
- `Plan note`
  - HR-in planlama qeydi və ya next step qeydidir
- `Target completion date`
  - overdue və near due məntiqinə təsir edə bilər

### 7.3. Bu tabda hansı işlər sırayla edilməlidir

Ən sağlam ardıcıllıq:

1. əməkdaşı seç
2. profil yaz
3. eyni competency üçün requirement ilə fərqi yoxla
4. ehtiyac yaranırsa need yarat
5. source və reason hissəsini boş buraxma

Əgər Performance modulundan auto need gəlirsə:

- onu yenidən sıfırdan yaratma
- mövcud need-in source və reason hissəsinə bax
- yalnız planlama qərarı ver

## 8. İllik planlama tabı

Bu tab need-ləri icra oluna bilən plan obyektlərinə çevirir.

### 8.1. Annual training plan formu

Field-lər:

- `Plan adı`
- `Plan ili`
- `Plan rübü`
  - boş və ya `full year`
- `Status`
  - `draft`, `review`, `approved`, `published`
- `Təsdiqlənmiş ehtiyaclardan plan sətirlərini avtomatik yarat`
- `Qeyd`

Save olunanda nə olur:

- `training_annual_plans` cədvəlinə yazılır
- `auto_generate` aktivdirsə `TrainingNeedPlanningService` approved need-lərdən `training_plan_items` yaradır
- plan statusu item-ların vəziyyətinə görə sync olunur

Bu formdan sonra hara təsir edir:

- recent plans
- suggested plan board
- HR review panel
- session proposal board

### 8.2. Plan status necə işləyir

- `draft`
  - plan yaradılıb, amma hələ review və approval mərhələsinə keçməyib
- `review`
  - item-lar yaranıb və HR baxışı tələb olunur
- `approved`
  - review hissəsi tamamlanıb
- `published`
  - təşkilat daxilində tətbiq üçün yekun mərhələ

### 8.3. Suggested plan board

Bu board HR üçün qərar dəstək qatıdır. Sistem ehtiyacları score-layıb ön sıralayır.

Sistem nələrə baxır:

- prioritet
- məcburi requirement
- role criticality
- eyni gap-in təkrarlanması
- proqramın hazır olması
- due date yaxınlığı
- evidence-based source-lar

HR burada nə etməlidir:

- təklifləri oxumaq
- hansı need-lərin plan item olmağa layiq olduğunu anlamaq
- approved need-lərin faktiki plan item-a çevrilməsini izləmək

### 8.4. HR review panel

Bu hissə auto-generated və ya mövcud plan item üzərində son qərar üçündür.

Field-lər:

- `Participant count`
- `Estimated budget`
- `Priority`
- `Review note`

Action-lar:

- `Mark HR adjusted`
- `Approve`

Save olunanda nə olur:

- `training_plan_items` üzərində participant count, budget, priority, review note yenilənir
- `review_status` `hr_adjusted` və ya `approved` olur
- `reviewed_by` və `reviewed_at` yazılır
- plan statusu yenidən sync olunur

Bu hissənin mənası:

- sistem təklif verir
- HR isə onu əməliyyata yararlı plana çevirir

### 8.5. Bu tabda doğru iş sırası

1. plan yarat
2. auto-generated item-ları yoxla
3. participant count və budget düzəlt
4. review note yaz
5. `HR adjusted` və ya `Approve` et
6. yalnız bundan sonra calendar tab-a keç

## 9. Təlim təqvimi tabı

Bu tab approved item-ları real session-a çevirir.

### 9.1. Session proposal board

Proposal board approved plan item-lardan yaranır. Burada sistem belə suallara cavab verir:

- hansı item sessiyaya çevrilməlidir
- hansı proqram daha uyğundur
- neçə iştirakçı gözlənilir
- təxmini büdcə və tarix nədir

Action-lar:

- `Select visible proposals`
- `Clear proposals`
- `Apply to form`
- `Create session`
- `Create sessions from selected`

Bu action-ların mənası:

- `Apply to form`
  - session formu doldurulur, amma hələ save olmur
- `Create session`
  - birbaşa `training_sessions` yaranır
- `Create sessions from selected`
  - toplu şəkildə bir neçə proposal real sessiyaya çevrilir

### 9.2. Training session formu

Field-lər:

- `Plan`
- `Program`
- `Session title`
- `Start date`
- `End date`
- `Location`
- `Trainer`
- `Capacity`
- `Planned budget`
- `Actual budget`
- `Auto-fill participants from approved needs`
- `Status`
  - `draft`, `scheduled`, `in_progress`, `completed`, `cancelled`
- `Notes`

Save olunanda nə olur:

- `training_sessions` cədvəlinə yazılır
- form proposal-dan gəlibsə `training_plan_item_id` də yazılır
- `auto_fill_participants` aktivdirsə uyğun need-lərdən iştirakçılar auto əlavə olunur
- plan varsa, onun statusu yenidən sync olunur

Bu formdan sonra hara təsir edir:

- session participants
- upcoming sessions
- delivery snapshot
- results tab

Praktik qayda:

- proposal-dan gələn sessiyanı create etməzdən əvvəl tarix, trainer və budget-i yoxla
- `Planned budget` planlanan xərci, `Actual budget` isə sessiya həqiqətən bitəndən sonra faktiki xərci göstərir
- sessiya hələ keçirilməyibsə `Actual budget` sahəsini boş saxlamaq daha düzgündür
- `Status` heç vaxt səbəbsiz `completed` qoyulmasın; əvvəl session reallıqda keçirilməlidir

### 9.3. Session participant formu

Field-lər:

- `Session`
- `Employee`
- `Training need`
- `Attendance status`

Save olunanda nə olur:

- `training_session_participants` cədvəlinə update-or-create yazılır
- eyni session + personnel üçün dublikat yaradılmır
- status `attended` olarsa `attended_at` avtomatik dolur

### 9.4. Session detail və participant management

Sessiya seçildikdən sonra bu əməliyyatlar var:

- participant search
- attendance filter
- participant source filter
- visible participant-ləri seçmək
- bulk attendance status tətbiq etmək
- selected participant-ləri silmək
- tək participant üçün quick status dəyişmək

Bu əməliyyatların təsiri:

- attendance vəziyyəti delivery nəticəsinə təsir edir
- `complete session` zamanı attended olanlar üçün delivery yaradılır

### 9.5. Complete session action-ı

Bu action calendar tabının ən kritik addımıdır.

`Complete session` basılanda nə olur:

- sessiya tamamlanmış kimi işlənir
- `TrainingDeliveryService` attended participant-lər üçün `training_delivery_records` yaradır
- statistik məlumat yenilənir

Yəni certificate və nəticə tabına keçməzdən əvvəl session tamamlanmalıdır.

## 10. Nəticələr tabı

Bu tab “sessiya keçirildi, indi necə bağlayıram?” sualına cavab verir.

### 10.1. Feedback form setup

Field-lər:

- `Session`
- `Feedback form title`
- `Status`
  - `draft`, `open`, `closed`
- `Default question type`
  - `rating`, `text`, `multiple_choice`
- `Feedback questions`

Save olunanda nə olur:

- `training_feedback_forms` cədvəlinə yazılır
- daxil edilən suallar sətir-sətir ayrılıb strukturlaşır

### 10.2. Feedback response formu

Field-lər:

- `Feedback form`
- `Employee`
- `Overall score`
- `Comments`
- `Answers`

Save olunanda nə olur:

- `training_feedback_responses` cədvəlinə update-or-create yazılır
- eyni form + employee üçün cavab yenilənə bilər

Bu formdan sonra hara təsir edir:

- feedback session summary
- average feedback analytics
- export report-lar

### 10.3. Delivered trainings kartı

Bu kart completed delivery record-ları göstərir.

Burada istifadəçi:

- delivery record seçə bilər
- certificate-i preview edə bilər
- document-i download edə bilər
- certificate-i replace və ya delete edə bilər

### 10.4. Delivery documents formu

Field-lər:

- `Delivery record`
- `Certificate / document`

Save olunanda nə olur:

- fayl `public` diskə yazılır
- `training_delivery_records.certificate_path` və `certificate_name` yenilənir
- əvvəlki fayl varsa replace olunur

Delete olunanda nə olur:

- storage-dan fayl silinir
- record üzərində certificate sahələri `null` olur

### 10.5. Export reports kartı

Buradan bunlar çıxarılır:

- delivered trainings report
- delivery summary report
- delivery pivot report
- feedback report
- audit report
- print summary

Bu hissə əməliyyat aparmır; reporting üçündür.

## 11. Analitika tabı

Bu tab rəhbərlik və HR planlaması üçün nəzərdə tutulub.

Əsas bloklar:

- `Coverage ratio`
- `Program fit ratio`
- `Requirement coverage`
- `Total / approved / planned needs`
- `Source mix`
- `Priority mix`
- `Top gap positions`

Bu tabın məqsədi:

- harada daha çox boşluq var
- hansı source daha çox need yaradır
- plan və requirement coverage nə səviyyədədir

## 12. Rəhbər hesabatları tabı

Bu tab rəhbərlik, HR rəhbəri və L&D məsulu üçün qurulub. Məqsəd sadəcə say göstərmək deyil; plan, icra, nəticə və büdcəni bir ekranda bağlamaqdır.

Bu tabda aşağıdakı suallara cavab verilir:

- bu il neçə təlim keçirilib?
- hansı rübdə delivery daha güclü olub?
- ümumi təlim saatı nə qədərdir?
- daxili / xarici / hibrid balansı necədir?
- nəticə və rəy səviyyəsi nə yerdədir?
- planlanan büdcə ilə faktiki xərc arasında fərq nə qədərdir?
- yaranan təlim ehtiyacının hansı hissəsi planlanıb, hansı hissəsi sessiyaya çevrilib, hansı hissəsi tamamlanıb?

### 12.1. Filtr sətri

Field-lər:

- `Hesabat ili`
- `Hesabat rübü`

Məntiq:

- `Hesabat ili` bütün aşağıdakı KPI və cədvəllərin əsas filtridir
- `Hesabat rübü` seçilərsə executive summary və employee-based report-lar həmin rübə daralır
- rüb boş qalarsa sistem ilin tam nəticəsini göstərir

### 12.2. Executive KPI kartları

Bu üst kartlar ilk baxış üçün hazırlanıb.

Kartlar:

- `Keçirilən təlim sayı`
- `Ümumi təlim saatı`
- `Davamiyyət faizi`
- `Orta rəy balı`

Bu göstəricilər necə hesablanır:

- keçirilən təlim sayı: completed sessiyaların sayı
- ümumi təlim saatı: attended iştirakçıların topladığı saatların cəmi
- davamiyyət faizi: attended participant / total participant nisbəti
- orta rəy balı: feedback response-lardan gələn orta nəticə

### 12.3. İllik icra kəsiyi

Bu cədvəl il üzrə ümumi icra görünüşüdür.

Sütunlar:

- `Hesabat ili`
- `Keçirilən təlim sayı`
- `İştirakçı sayı`
- `Ümumi təlim saatı`
- `Fərq`

`Fərq` burada `planned budget total - actual budget total` kimi oxunur.

### 12.4. Rüblük icra kəsiyi

Bu blok seçilən ili Q1, Q2, Q3, Q4 üzrə bölür.

Hər rüb kartında:

- sessiya sayı
- iştirakçı sayı
- attended hours
- planned budget
- actual budget
- average feedback score

göstərilir.

### 12.5. Daxili / xarici / hibrid bölgü

Bu hissə delivery type bölgüsünü göstərir.

Hər type üçün:

- neçə sessiya keçirildiyi
- neçə participant iştirak etdiyi
- neçə attended saat toplandığı
- orta rəy balının neçə olduğu

göstərilir.

### 12.6. Təlim nəticələri dashboard-u

Bu hissə “təlim keçirildi, bəs nəticə necə oldu?” sualına cavab verir.

Hər nəticə sətrində:

- sessiya adı
- delivery type
- participant sayı
- attendance rate
- average feedback score

görünür.

### 12.7. Büdcə analitikası

Bu blok üç əsas rəqəmi göstərir:

- `Plan büdcəsi`
- `Faktiki büdcə`
- `Fərq`

Burada vacib qayda:

- `Planned budget` plan mərhələsində daxil edilir
- `Actual budget` sessiya real keçirilib yekunlaşdıqdan sonra daxil edilir

### 12.8. Təlim ehtiyacı vs icra

Bu, ehtiyacla delivery arasında comparison ekranıdır.

Burada:

- `Total needs`
- `Approved needs`
- `Planned needs`
- `Session-linked needs`
- `Completed needs`
- `Open needs`
- `Planning coverage ratio`
- `Delivery coverage ratio`

göstərilir.

### 12.9. Kompetensiya üzrə əhatə

Burada hər kompetensiya üzrə:

- ümumi need sayı
- planned need sayı
- delivered record sayı

görünür.

### 12.10. Proqram üzrə əhatə

Burada hər proqram üçün:

- delivery type
- sessions count
- recommended needs
- delivered records

göstərilir.

### 12.11. Əməkdaş üzrə təlim saatları

Bu cədvəl employee-based hesabatdır.

Sütunlar:

- `Əməkdaş`
- `Tabel nömrəsi`
- `Keçirilən təlim sayı`
- `Ümumi təlim saatı`
- `Daxili / xarici / hibrid`
- `Orta rəy balı`

### 12.12. Bu tab hansı formaların nəticəsi ilə dolur?

Bu tab özü əməliyyat aparmır, amma aşağıdakı formaların nəticəsini oxuyur:

- session formu
- participant attendance
- complete session
- feedback response
- planned budget
- actual budget

## 13. Tam siyahılar tabı

Bu tab dashboard kartlarından fərqli olaraq tam əməliyyat nəzarəti üçündür.

Burada:

- search
- filter
- pagination
- detail panel

ilə full list-lər görünür.

Bu tabı nə vaxt istifadə etməlisən:

- dashboard kartı limitə görə yetmirsə
- konkret record tapmaq lazımdırsa
- detail panel ilə bir record-u dərin oxumaq istəyirsənsə

## 14. Save edəndə təsir xəritəsi

Qısa xatırlatma:

- `Group / Level / Competency / Program`
  - baza lüğətini böyüdür
- `Program map`
  - recommendation keyfiyyətini artırır
- `Requirement`
  - gap ölçməsini legitim edir
- `Profile`
  - əməkdaşın cari vəziyyətini formalaşdırır
- `Need`
  - planlama üçün əsas queue yaradır
- `Plan`
  - need-ləri plan item-a çevirir
- `Plan item review`
  - HR qərarını rəsmi hala salır
- `Session`
  - icra mərhələsini açır
- `Participant`
  - attendance və delivery bazasını yaradır
- `Complete session`
  - delivery record yaradır
- `Feedback / response`
  - nəticə ölçümünü bağlayır
- `Certificate`
  - sübut sənədini bağlayır

## 15. Tam iş ssenarisi

### Ssenari A: Sıfırdan modul qurmaq

1. competency group-ları yarat
2. levels yarat
3. competencies yarat
4. programs yarat
5. program mapping yaz
6. role requirements doldur
7. employee profile daxil et
8. need yarat
9. annual plan yarat
10. plan item-ları review et
11. approved item-ları session-a çevir
12. participant-ləri idarə et
13. session-ı complete et
14. certificate və feedback ilə bağla

### Ssenari B: Performance-dan gələn auto need ilə işləmək

1. `Profil və plan` tabında source=`performance_gap` və ya `skill_gap` olan need-i tap
2. reason hissəsini oxu
3. recommended program və target level yoxla
4. annual plan-a daxil et
5. review-dan keçir
6. session yarat
7. completion və delivery-ni bağla

### Ssenari C: Real sessiyanı bağlamaq

1. calendar tabında participant attendance-i yenilə
2. `attended` olanları yoxla
3. `Complete session` et
4. results tabında delivery record-u aç
5. certificate yüklə
6. lazım olsa feedback form yarat
7. feedback response-ları daxil et

### Ssenari D: Rəhbər üçün hesabat hazırlamaq

1. `Rəhbər hesabatları` tabını aç
2. əvvəl `Hesabat ili` seç
3. lazım olsa `Hesabat rübü` seç
4. executive KPI kartlarından ilk snapshot götür
5. `İllik icra kəsiyi` və `Rüblük icra kəsiyi` ilə trendi yoxla
6. `Büdcə analitikası` ilə planned vs actual fərqini oxu
7. `Təlim ehtiyacı vs icra` blokunda coverage ratio-ları yoxla
8. `Əməkdaş üzrə təlim saatları` cədvəlindən employee-based dəlil götür

## 16. Ən çox edilən səhvlər

- program mapping yazmadan need-lərlə işləmək
- requirement matrix boş ikən analytics gözləmək
- profile yazmadan need yaratmaq
- plan item review etmədən session yaratmaq
- session complete etmədən certificate yükləmək
- source və reason hissəsini boş buraxmaq
- `Actual budget` sahəsini sessiya bitmədən faktiki xərc kimi doldurmaq
- report tabında boşluq görüb səbəbi report-un özündə axtarmaq; çox vaxt problem session, attendance və ya feedback datalarındadır

## 17. Son qayda

Əgər “mən indi hansı mərhələdəyəm?” sualında çaşırsansa, bunu xatırla:

- `Kataloq` bazanı qurur
- `Need` problemi yazır
- `Plan` qərarı verir
- `Session` icraya çevirir
- `Result` sübut və ölçünü bağlayır

Bu məntiq saxlanarsa, modul təkcə data saxlamaq üçün yox, real HR inkişaf prosesini idarə etmək üçün işləyir.
