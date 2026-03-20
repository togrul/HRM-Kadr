# Personnel Professional Portfolio Modulu Bələdçisi

Bu sənəd yeni `Professional Portfolio` modulunun necə qurulacağını, hansı biznes qaydaları ilə işləyəcəyini və hansı mərhələlərlə implement ediləcəyini izah edir.

Bu sənədin məqsədi:

- tələbi 3 ayrı kiçik qeyd kimi yox, vahid HR domeni kimi çərçivələmək
- tədbir, media və layihə məlumatlarının hansı formada saxlanmalı olduğunu dəqiqləşdirmək
- verification, permissions, timeline və attachment siyasətini əvvəlcədən sabitləmək
- implementasiya başlamazdan əvvəl komandanın eyni məhsul məntiqində olmasını təmin etmək

## Modulun əsas məqsədi

Bu modul personnel üçün `Professional Portfolio` qurur.

Sadə dildə bu modul aşağıdakı suallara cavab verməlidir:

- əməkdaş hansı dəyərli konfrans və seminarlarda iştirak edib
- əməkdaş hansı tədbirlərdə çıxışçı, moderator və ya panelist olub
- əməkdaş mediada harada görünüb, hansı link və arxiv sübutu var
- əməkdaş hansı layihələrdə iştirak edib və oradakı rolu nə olub

Bu modulun məqsədi sadəcə qeydlər toplamaq deyil.

Bu modul HR qərarlarında istifadə ediləcək structured portfolio yaratmalıdır:

- talent review
- promotion və succession
- ekspertlik və təmsilçilik
- peşəkar görünürlük
- layihə təcrübəsi
- inkişaf və kompetensiya xəritəsi

## Niyə vahid portfolio kimi qurulur

Bu tələbi belə qurmaq düzgün deyil:

- personnel kartına 10-15 yeni field əlavə etmək
- text-area içində “qatıldığı seminarlar” saxlamaq
- generic `notes` sistemi ilə hər şeyi qarışdırmaq

Düzgün yanaşma budur:

- personnel üçün ayrıca `Professional Portfolio` workspace qurulur
- altında 3 structured alt domen olur:
  - `Tədbirlər`
  - `Mediada görünürlük`
  - `Layihələr`

Sonra bu record-lar timeline və report qatında birləşdirilir.

## V1 scope

V1-də `personnel-first` model seçilir.

Yəni:

- konfrans / seminar record-ları birbaşa personnel-ə bağlı olacaq
- media mention record-ları birbaşa personnel-ə bağlı olacaq
- layihə iştirak record-ları da birbaşa personnel-ə bağlı olacaq

V1-də ayrıca master registry qurulmur:

- ayrıca `projects` master entity yoxdur
- ayrıca `events` master entity yoxdur
- ayrıca `media outlets` registry yoxdur

Amma schema gələcəkdə bu istiqamətə keçməyə hazır qurulacaq.

## Workspace quruluşu

Personnel profilində ayrıca iş sahəsi açılacaq:

- `Professional Portfolio`

Bu workspace içində 4 tab olacaq:

1. `Tədbirlər`
2. `Media`
3. `Layihələr`
4. `Zaman xətti`

Üst hissədə summary kartları görünəcək:

- verified tədbir sayı
- verified media mention sayı
- verified layihə sayı
- verified speaker çıxışı sayı

## 1. Tədbirlər modulu

### Bu modul nə saxlayır

Bu modul personnel-in peşəkar dəyəri olan tədbir record-larını saxlayır.

Vacib məqam:

Bu modul bütün tədbirləri saxlamayacaq.

### Niyə bütün iştiraklar saxlanmır

HR üçün “hansı seminarda olub” sualı öz-özlüyündə həmişə faydalı deyil.

Ona görə sistem “tədbir jurnalı” olmayacaq.

Sistem yalnız HR qərarlarına dəyər qatan record-ları saxlayacaq.

### Tədbir record-u nə vaxt məntiqlidir

Mütləq saxlanmalıdır:

- `speaker`
- `moderator`
- `panelist`
- `presenter`
- `organizer`

Yalnız seçilərək saxlanmalıdır:

- `participant`

`participant` rolunda record yalnız aşağıdakı hallarda qəbul olunmalıdır:

- prestijli və ya strateji tədbirdir
- qurumu təmsil edib
- sertifikat və ya akkreditasiya verib
- fərdi inkişaf planı ilə bağlıdır
- iş nəticəsinə ölçülə bilən təsiri var

### Tədbir entity-si

`personnel_event_records`

Sahələr:

- `personnel_id`
- `event_type`
  - `conference`
  - `seminar`
  - `forum`
  - `workshop`
  - `panel`
  - `other`
- `participation_role`
  - `participant`
  - `speaker`
  - `moderator`
  - `panelist`
  - `organizer`
- `title`
- `topic`
- `organizer_name`
- `start_date`
- `end_date`
- `location`
- `country_id` nullable
- `attendance_format`
  - `offline`
  - `online`
  - `hybrid`
- `strategic_level`
  - `informational`
  - `development`
  - `representation`
  - `strategic`
- `result_summary`
- `impact_summary`
- `certificate_attachment_id` nullable
- `agenda_attachment_id` nullable
- `source_url` nullable
- `visibility`
  - `internal`
  - `public`
- `verification_status`
  - `pending`
  - `verified`
  - `rejected`
- `entered_by`
- `verified_by`
- `verified_at`
- `notes` nullable

### Tədbir form davranışı

Əgər `participation_role = participant` seçilərsə, əlavə field açılmalıdır:

- `Niyə HR üçün dəyərlidir?`

Bu field required olmalıdır.

Məqsəd:

- sistemi hər tədbiri doldurmağa məcbur etməmək
- yalnız record-worthy iştirakları saxlamaq

### Tədbir list və detail görünüşü

Listdə minimum görünməlidir:

- tədbir adı
- tədbir tipi
- rol
- tarix
- təşkilatçı
- verification status

Detail drawer içində:

- mövzu
- format
- məkan
- strategic level
- nəticə və təsir xülasəsi
- source link
- sertifikat / agenda attachment

## 2. Media mentions modulu

### Bu modul nə saxlayır

Personnel haqqında mediada çıxan materialları structured şəkildə saxlayır.

Bu record:

- link
- mənbə
- arxiv sübutu
- qısa məzmun
- verification

ilə birlikdə saxlanmalıdır.

### Əsas prinsip

Yalnız URL saxlamaq kifayət deyil.

Link sonradan ölə bilər.

Ona görə media record üçün `archive evidence` məcburidir.

### Media entity-si

`personnel_media_mentions`

Sahələr:

- `personnel_id`
- `headline`
- `publisher_name`
- `publisher_type`
  - `tv`
  - `website`
  - `newspaper`
  - `magazine`
  - `youtube`
  - `social_media`
  - `official_portal`
  - `other`
- `mention_type`
  - `interview`
  - `news_mention`
  - `article_author`
  - `appearance`
  - `quote`
  - `press_release`
  - `other`
- `published_at`
- `url` nullable
- `summary`
- `sentiment`
  - `positive`
  - `neutral`
  - `negative`
- `language`
- `archive_attachment_id` required
- `screenshot_attachment_id` nullable
- `visibility`
  - `internal`
  - `public`
  - `restricted`
- `verification_status`
  - `pending`
  - `verified`
  - `rejected`
  - `broken_link`
  - `archived_only`
- `entered_by`
- `verified_by`
- `verified_at`
- `notes` nullable

### Media form davranışı

Required minimum:

- `headline`
- `publisher_name`
- `published_at`
- `summary`
- `archive evidence`

Əgər URL varsa saxlanacaq.

Əgər URL sonradan işləmirsə:

- record silinməyəcək
- `verification_status = broken_link` və ya `archived_only` kimi qalacaq

### Media list və detail görünüşü

Listdə görünməlidir:

- başlıq
- media qurumu
- tarix
- mention type
- sentiment
- verification status

Detail drawer:

- link aç
- archive faylı aç
- screenshot bax
- summary
- visibility
- verification log

## 3. Layihə iştirakı modulu

### Bu modul nə saxlayır

Personnel-in iştirak etdiyi layihələri və oradakı rolunu structured şəkildə saxlayır.

Bu modul sonradan aşağıdakılar üçün dəyər yaradacaq:

- layihə təcrübəsi profili
- gələcək assignment qərarları
- promotion / succession
- capability review

### Layihə entity-si

`personnel_project_records`

Sahələr:

- `personnel_id`
- `project_name`
- `project_code` nullable
- `project_type`
  - `internal`
  - `interagency`
  - `international`
  - `digital`
  - `security`
  - `infrastructure`
  - `other`
- `role_title`
- `responsibility_summary`
- `team_name` nullable
- `sponsor_unit_id` nullable
- `partner_organizations` nullable
- `start_date`
- `end_date` nullable
- `is_ongoing`
- `outcome_summary`
- `impact_summary`
- `reference_url` nullable
- `evidence_attachment_id` nullable
- `verification_status`
  - `pending`
  - `verified`
  - `rejected`
- `entered_by`
- `verified_by`
- `verified_at`
- `notes` nullable

### Gələcək uyğunluq

V1-də ayrıca `projects` master registry yoxdur.

Amma aşağıdakı üçlük gələcəkdə dedupe və registry üçün əsas olacaq:

- `project_name`
- `project_code`
- `sponsor_unit_id`

### Layihə list və detail görünüşü

Listdə:

- layihə adı
- rol
- layihə tipi
- tarix aralığı
- ongoing status
- verification status

Detail drawer:

- məsuliyyət xülasəsi
- nəticə
- təsir
- partner organization-lar
- reference link
- evidence attachment

## 4. Zaman xətti

### Bu tab nə edir

`Zaman xətti` verified record-ları birləşdirir:

- tədbir
- media mention
- layihə

### Niyə vacibdir

Bu görünüş personnel-in peşəkar yolunu tək timeline kimi oxumağa imkan verir.

Məsələn:

- 2024: seminar speaker çıxışı
- 2025: mediada müsahibə
- 2026: strateji layihədə rol

### Timeline kartı minimum nə göstərməlidir

- tarix
- record növü
- başlıq
- personnel rolu
- qısa nəticə
- verification status

Burada xam admin texniki data görünməməlidir.

Bu HR üçün oxunaqlı executive history olmalıdır.

## Verification workflow

Bu modul sərbəst publish sistemi olmayacaq.

Bütün alt modullar üçün eyni axın:

1. HR və ya məsul şəxs record əlavə edir
2. record `pending` olur
3. verifier record-u baxır
4. `verified` və ya `rejected` edir
5. default görünüş yalnız `verified` record-ları göstərir
6. filter ilə `pending` və `rejected` də görünə bilir

### Niyə verification lazımdır

- məlumat keyfiyyətini qorumaq
- media və tədbir record-larında reputasiya riskini azaltmaq
- HR report-larında yalnız etibarlı qeydləri göstərmək

## Permissions

Yeni permission-lar:

- `view-professional-portfolio`
- `manage-personnel-event-records`
- `manage-personnel-media-records`
- `manage-personnel-project-records`
- `verify-professional-portfolio-records`

Media üçün əlavə sərtləşdirmə:

- `view-restricted-media-records`

### Permission mənası

`view-professional-portfolio`

- portfolio workspace-i görür

`manage-personnel-event-records`

- tədbir record yaradır və redaktə edir

`manage-personnel-media-records`

- media mention yaradır və redaktə edir

`manage-personnel-project-records`

- layihə record yaradır və redaktə edir

`verify-professional-portfolio-records`

- pending record-u təsdiq və ya rədd edir

`view-restricted-media-records`

- restricted visibility olan media record-ları görür

## Attachments siyasəti

Bu modulda fayl və sübut çox olacaq.

Ona görə ayrıca attachment həlli lazımdır:

- `professional_record_attachments`

və ya mövcud attachable attachment pattern reuse ediləcək.

Attachment-lar üçün minimum metadata:

- `attachable_type`
- `attachable_id`
- `kind`
- `file_path`
- `original_name`
- `mime_type`
- `uploaded_by`

### Attachment kind nümunələri

Tədbirlər:

- `certificate`
- `agenda`

Media:

- `archive`
- `screenshot`

Layihələr:

- `evidence`
- `reference`

## UI prinsipləri

Bu modul personnel profilində ayrıca `Professional Portfolio` shell kimi qurulacaq.

Child komponentlər:

- `EventsManager`
- `MediaMentionsManager`
- `ProjectParticipationManager`
- `PortfolioTimelinePanel`

UI qaydaları:

- tab-lar lazy/island işləməlidir
- add/edit modal-lar eyni məhsul dilində olmalıdır
- submit sonrası toast çıxmalıdır
- status chip-lər shared component olmalıdır
- field shell, async button, verification panel, attachment row shared component-lərlə qurulmalıdır

## Reporting və gələcək genişlənmə

Bu schema sonradan aşağıdakılara imkan verməlidir:

- neçə speaker çıxışı var
- neçə media mention verified-dir
- hansı illərdə daha aktiv olub
- hansı strukturlar daha çox layihə record-u yaradır
- struktur üzrə representation səviyyəsi

V2 və sonrakı mərhələlərdə əlavə edilə bilər:

- ayrıca `projects` master registry
- ayrıca `events` registry
- media outlet registry
- analytics panel
- broken link auto-check
- reminder / notification

## Anti-pattern-lər

Bu tələbi belə implement etməmək lazımdır:

- personnel cədvəlinə əlavə text field-lər atmaq
- generic `notes` cədvəlində hər şeyi qarışdırmaq
- yalnız URL saxlamaq
- verification olmadan hər şeyi public etmək
- hər tədbiri sistemə doldurmağa çalışmaq

Xüsusən tədbirlər modulunda əsas prinsip budur:

- sistem tədbir jurnalı deyil
- sistem HR üçün dəyərli peşəkar fəaliyyət reyestridir

## Delivery plan

### Phase 1

- `Professional Portfolio` workspace shell
- `Tədbirlər` modulu
- `Media` modulu
- `Layihələr` modulu
- verification workflow
- attachment support
- summary kartlar
- docs faylı

### Phase 2

- unified timeline
- analytics və reports
- deeper permission matrix
- registry-lərə keçid üçün hazırlıq
- broken link və archive health qaydaları

## Test plan

### Domain test-lər

- speaker tədbiri verified olanda timeline-da görünür
- participant tədbiri strategic justification olmadan save olmur
- media mention archive evidence olmadan save olmur
- restricted media permission yoxdursa görünmür
- ongoing project `end_date` olmadan save olur
- rejected record default list-də görünmür

### Workflow test-lər

- pending record yalnız verifier tərəfindən approve olunur
- verified record summary counters-a düşür
- rejected record default report-a daxil olmur
- broken link media record arxiv üzərindən dəyərli qalır

### UI test-lər

- tab-lar lazy/island işləyir
- add/edit modal-lar submit sonrası toast çıxarır
- attachment preview və open işləyir
- timeline mixed record-ları düzgün sıralayır
- docs deep-link düzgün açılır

### Acceptance ssenariləri

1. Əməkdaş seminar speaker kimi əlavə olunur, verify edilir, timeline-da görünür
2. Əməkdaş media mention link və archive ilə əlavə olunur, verify edilir, detail açılır
3. Əməkdaş layihə iştirakçısı kimi əlavə olunur, ongoing status-da görünür
4. Verifier pending record-u approve edir
5. Restricted media normal HR istifadəçiyə görünmür

## Default qaydalar

- V1-də ayrıca project registry yoxdur
- tədbirlərin hamısı yox, yalnız HR üçün dəyərli olanları saxlanır
- media üçün archive evidence məcburidir
- default list-lər yalnız `verified` record göstərir
- personnel self-service giriş V1 scope-da deyil

## Yekun qərar

Bu tələb ən sağlam şəkildə belə qurulmalıdır:

- vahid `Professional Portfolio`
- altında 3 structured alt modul
- tədbirlərdə bütün iştiraklar yox, yalnız dəyərli record-lar
- media üçün archive-first siyasəti
- layihələr üçün gələcək registry-yə açıq schema
- verification-first workflow

Bu sənəd implementasiya üçün əsas məhsul və texniki ssenari kimi istifadə olunacaq.
