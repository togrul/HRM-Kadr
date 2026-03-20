# My HR Self-Service və Onboarding Guide

Bu sənəd `My HR` employee self-service platformasının necə qurulacağını, hansı biznes qaydaları ilə işləyəcəyini və hansı mərhələlərlə implement ediləcəyini izah edir.

Məqsəd ayrı-ayrı “kiçik feature”lər toplamaq deyil. Məqsəd budur ki:
- əməkdaş üçün vahid şəxsi kabinet yaransın
- elektron ərizələr bir yerdən idarə olunsun
- onboarding sənədləri və learning content vahid employee experience içində toplansın
- fərdi inkişaf planı employee üçün aydın və izlənə bilən iş sahəsinə çevrilsin
- rəhbər, struktur və tabellik xətti aydın görünsün
- mövcud HR modulları employee-facing contract ilə reuse olunsun

---

## 1. Nə qurulur

Yeni üst məhsul adı:
- `My HR`

Bu portal employee self-service platforması olacaq. V1-də əsas fokus əməkdaşdır. Manager self-service ayrıca sonrakı mərhələ kimi düşünülür, amma arxitektura elə qurulur ki, sonradan rəhbər workspace-i də problemsiz əlavə olunsun.

Portalın əsas bölmələri:
- `Xülasə`
- `Ərizələrim`
- `Bildirişlər`
- `Onboarding`
- `Fərdi inkişaf planım`
- `Öyrənmə materialları`
- `Sənədlərim`
- `Mənim strukturum`

Bu bölmələr employee üçün gündəlik lazım olan HR təcrübəsini vahid entrypoint-ə çevirir.

---

## 2. Mövcud sistemdə nələr reuse olunacaq

Bu sistem sıfırdan paralel HR platforması kimi qurulmayacaq. Mövcud repo-da artıq çoxlu hazır capability var və onlar employee-facing contract ilə reuse olunacaq.

Əsas reuse nöqtələri:
- `UserPersonnelLinkResolver`
  - current user -> personnel mapping üçün authoritative source
- `Leaves`
  - icazə müraciətləri
- `Vacation`
  - məzuniyyət müraciətləri
- `BusinessTrips`
  - ezamiyyət müraciətləri
- `Notifications`
  - database inbox və `read_at` tracking
- `Personnel.files`
  - employee documents tabı üçün mövcud sənəd bazası
- `TrainingNeeds`
  - employee learning history, approved development need və development plan relation-ləri üçün uyğun baza
- `Personnel.parent_id`
  - rəhbər və tabellik xətti üçün əsas source
- `Personnel.structure_id`
  - struktur bölməsi üçün source
- mövcud docs hub
  - sonradan `/docs?focus=my-hr` şəklində inteqrasiya olunacaq

Yəni yeni modulun rolu:
- mövcud HR sistemlərini employee kontekstində birləşdirmək
- missing self-service capability-ləri tamamlamaq
- ortaq portal və workflow qatını vermək

---

## 3. Core məhsul modeli

### 3.1. Şəxsi kabinet

Employee üçün ayrıca route olacaq:
- `/my-hr`

Bu route yalnız auth user üçün işləyəcək və ilk addımda `UserPersonnelLinkResolver` ilə user-in hansı personnel record-a bağlı olduğu müəyyən ediləcək.

Əgər mapping yoxdursa:
- sistem boş qalmayacaq
- “profiliniz kadr qeydi ilə bağlanmayıb” state-i görünəcək
- istifadəçi yönləndirici yardım mətni görəcək

### 3.2. Employee Self-Service contract

Employee portal yalnız “özümə aid data” göstərməlidir.

Bütün read/query qatları bu prinsipə tabe olacaq:
- user resolve olunur
- personnel context çıxarılır
- yalnız həmin personnel üçün data yüklənir

Bu, query və authorization qatında ayrıca `my_*` read service-lərlə qurulacaq.

---

## 4. Ərizələrim mərkəzi

### 4.1. Nə deməkdir

Müştərinin “işçi panelində elektron ərizə doldurmaq” istəyi yeni ayrıca leave sistemi qurmaq demək deyil.

Düzgün professional yanaşma:
- mövcud `Leaves`, `Vacation`, `BusinessTrips` domain-lərini source-of-truth kimi saxlamaq
- employee üçün onların unified self-service façade qatını qurmaq

### 4.2. Nələr görünəcək

`Ərizələrim` bölməsində employee aşağıdakıları görəcək:
- icazə müraciətləri
- məzuniyyət müraciətləri
- ezamiyyət müraciətləri
- status və approval vəziyyəti
- tarix aralığı
- current approver
- düzəliş sorğusu statusu

UI xüsusiyyətləri:
- search
- request type filter
- status filter
- period filter
- detail drawer
- timeline görünüşü

### 4.3. Data contract

Unified read layer:
- `MyRequestsReadService`

Bu service fərqli domain record-larını vahid contract-a çevirəcək:
- `request_type`
- `request_no`
- `submitted_at`
- `effective_period`
- `status`
- `current_approver`
- `submission_source`
- `can_request_correction`

### 4.4. Submission metadata

Mövcud request domain-lərinə minimal metadata əlavə olunacaq:
- `submission_source`
  - `hr`
  - `employee_self_service`
  - `system`
- `submitted_by_user_id`

Bu metadata gələcəkdə:
- audit
- analytics
- employee-origin request reporting

üçün vacib olacaq.

---

## 5. Düzəliş üçün müraciət axını

Müştərinin “işçi ərizəsinə düzəliş üçün müraciət göndərə bilər” istəyi ayrıca və düzgün qurulmalıdır.

Employee submitted request-i birbaşa edit etməməlidir. Bu həm audit, həm də approval məntiqini pozar.

### 5.1. Yeni domain

Yeni cədvəl:
- `employee_request_change_requests`

Shape:
- `requestable_type`
- `requestable_id`
- `personnel_id`
- `requested_by_user_id`
- `reason`
- `proposed_patch` JSON
- `status`
  - `pending`
  - `approved`
  - `rejected`
  - `withdrawn`
- `reviewed_by`
- `reviewed_at`
- `review_note`

### 5.2. Qaydalar

- employee yalnız öz request-i üçün correction aça bilər
- correction request orijinal request-in özünü əvəz etmir
- reviewer approve etdikdən sonra original record update olunur
- audit qeyd saxlanılır
- correction request history ayrıca görünür

### 5.3. UX

`Ərizələrim` detail içində:
- `Düzəliş istə`

Employee correction request yaradanda:
- səbəb yazır
- dəyişmək istədiyi field-ləri göstərir
- review nəticəsini notification inbox-da alır

---

## 6. Bildirişlər

Employee üçün mövcud `Notifications` modulu reuse olunacaq.

### 6.1. Employee inbox

Yeni `Bildirişlər` tabı:
- unread count
- grouped list
  - `today`
  - `yesterday`
  - `this week`
  - `older`
- deep-link to source

### 6.2. Əsas notification növləri

- request status update
- correction request nəticəsi
- onboarding document assignment
- unread document reminder
- learning content assignment
- HR announcements və targeted notices

### 6.3. Read tracking

Mövcud `read_at` semantikası qalır.

Bu hissə ayrıca paralel inbox qurmur; mövcud notification platforması employee entrypoint ilə reuse olunur.

---

## 7. Fərdi inkişaf planım

Müştərinin yeni əlavəsi olan `fərdi inkişaf planı` employee kabinetində ayrıca görünməlidir.

Bu hissə sadəcə training history deyil. Professional HR sistemində bu ekran employee üçün aşağıdakı sualları cavablandırmalıdır:
- mənim üçün hansı inkişaf istiqamətləri açılıb
- bu istiqamətlər haradan yaranıb
- hansı action planlaşdırılıb
- hansısı icradadır
- hansısı tamamlanıb

### 7.1. Məhsul mənası

Fərdi inkişaf planı employee üçün aşağıdakı komponentləri birləşdirir:
- approved development need-lər
- weak area və ya inkişaf hədəfi
- həmin hədəfə bağlı action-lar
- target quarter / year
- owner
- progress

Bu hissə employee üçün şəffaflıq verir, HR üçün isə follow-up görünüşü yaradır.

### 7.2. Source-of-truth

Yeni paralel development engine qurulmayacaq.

Authoritative source olacaq:
- `TrainingNeeds`
- approved need-lər
- plan item-lər
- delivery/result record-ları
- performance və assessment source-ları

Employee-facing yeni read layer:
- `MyDevelopmentPlanReadService`

### 7.3. Employee UX

Yeni tab:
- `Fərdi inkişaf planım`

Bu tabda section-lər:
- `Aktiv plan`
- `Gözləyən addımlar`
- `Tamamlananlar`
- `İnkişaf tarixçəsi`

Employee burada aşağıdakıları görəcək:
- development goal
- source
- plan type
- target period
- status
- owner
- progress
- bağlı session və ya learning action

### 7.4. Plan item contract

Employee-facing contract:
- `plan_type`
  - `training`
  - `mentoring`
  - `coaching`
  - `reading`
  - `project_assignment`
  - `other`
- `source_type`
  - `performance_review`
  - `manager_request`
  - `hr_request`
  - `self_request`
  - `assessment`
- `title`
- `weakness_or_goal`
- `target_period`
- `status`
  - `planned`
  - `approved`
  - `in_progress`
  - `completed`
  - `cancelled`
- `owner_label`
- `progress_percent`
- `related_session`
- `completion_note`

### 7.5. Məhsul qaydaları

- employee planı birbaşa publish etməyəcək
- employee status-u sərbəst `approved` və ya `completed` edə bilməyəcək
- cancelled və rejected source need-lər active plan görünüşündə qalmayacaq
- completed training delivery varsa plan progress avtomatik sync oluna bilər

### 7.6. Employee action-ları

Bu ekran read-only summary olmayacaq. Yüngül action-lar olacaq:
- detail bax
- bağlı session-a keç
- bağlı materialı aç
- self-note əlavə et
- evidence upload tələb olunursa yüklə

### 7.7. HR/Admin görünüşü

HR/Admin üçün:
- employee development plan snapshot
- source coverage
- progress vəziyyəti
- overdue action-lar
- need-to-action closure

Bu hissə ayrıca paralel HR moduluna çevrilməyəcək; `TrainingNeeds` və bağlı performance nəticələri ilə yaşayacaq.

### 7.8. Automation

Automation qaydaları:
- approved need employee development plan görünüşünə düşür
- completed training delivery bağlı plan item progress-ini yeniləyir
- overdue action-lar reminder yaradır
- stale planned action analytics-ə düşür

### 7.9. Niyə ayrıca tab lazımdır

`Öyrənmə materialları` ilə `Fərdi inkişaf planım` eyni şey deyil:
- learning content ümumi və ya targeted materialdır
- fərdi inkişaf planı employee-yə bağlı goal/action/progress xəttidir

Professional HR sistemində bunlar ayrıca qalmalıdır.

---

## 8. Onboarding sənədləri

Müştərinin onboarding document tələbi compliance xarakterli bir domendir. Sadəcə “faylı employee-yə göstər” kifayət deyil.

Oxunma tarixi ayrıca izlənməlidir və lazım gəlsə tanış olma təsdiqi ayrıca saxlanmalıdır.

### 8.1. Yeni cədvəllər

- `onboarding_document_templates`
- `onboarding_document_assignments`
- `onboarding_document_receipts`

### 8.2. Template shape

- `title`
- `document_type`
  - `policy`
  - `internal_regulation`
  - `job_instruction`
  - `security_rule`
  - `welcome_pack`
  - `other`
- `version`
- `file_path` və ya attachment reference
- `is_required`
- `requires_acknowledgement`
- `effective_from`
- `effective_to`
- `target_mode`

### 8.3. Assignment shape

- `template_id`
- `personnel_id`
- `assigned_by`
- `assigned_at`
- `due_at`
- `status`
  - `assigned`
  - `opened`
  - `acknowledged`
  - `overdue`
  - `waived`

### 8.4. Receipt shape

- `assignment_id`
- `opened_at`
- `acknowledged_at`
- `acknowledged_ip`
- `acknowledged_user_agent`

### 8.5. Məhsul qaydası

Oxunma tarixi üçün:
- `opened_at`

Tanış oldum / qəbul etdim xətti üçün:
- `acknowledged_at`

Professional yanaşma budur ki, hər ikisi ayrıca saxlanılsın.

### 8.6. Employee UX

`Onboarding` tabında:
- oxunmalı sənədlər
- açılmış sənədlər
- tanış olunmuş sənədlər
- overdue sənədlər

Action-lar:
- aç
- yüklə
- tanış oldum

### 8.7. HR/Admin UX

- template library
- assignment list
- unread / overdue görünüşü
- version history

### 8.8. Automation

Yeni əməkdaş aktivləşəndə:
- default onboarding pack assign olunur

Reminder job-lar:
- unread docs reminder
- overdue acknowledgement reminder

---

## 9. Mənim strukturum

Müştərinin istəyi:
- əməkdaşın rəhbəri görünsün
- struktur bölməsi görünsün
- tabellik xətti görünsün
- kimə tabedir və kim ona tabedir ierarxiya şəklində görünsün

### 9.1. Authority source

Bu hissə üçün authoritative source:
- `personnels.parent_id`
- `personnels.structure_id`

Burada dolayı inference etməyəcəyik.

### 9.2. Yeni relation-lar

`Personnel` modelinə əlavə ediləcək:
- `manager()`
- `directReports()`

Lazım olduqda recursive helper:
- upstream chain
- downstream direct list

### 9.3. Employee UX

`Mənim strukturum` tabında:
- öz kartı
- rəhbər kartı
- struktur bölməsi
- yuxarı tabellik xətti
- mənə tabe olanlar

Qaydalar:
- `parent_id` boşdursa fallback state görünür
- direct reports yoxdursa boş grid qalmır

### 9.4. Nə üçün vacibdir

Bu hissə employee portal üçün çox dəyərlidir:
- kimə hesabat verir
- hansı strukturda işləyir
- öz komandası varmı

Bu data HR sistemində employee orientation üçün əsasdır.

---

## 10. Öyrənmə materialları, videolar və təqdimatlar

Müştərinin istədiyi welcome videoları və materiallar formal training session-lardan ayrı düşünülməlidir.

Bunlar:
- onboarding content ola bilər
- general awareness material ola bilər
- targeted təqdimat ola bilər
- hər zaman attendance və training delivery yaratmır

### 10.1. Yeni domain

- `employee_content_assets`
- `employee_content_assignments`
- `employee_content_views`

### 10.2. Asset shape

- `title`
- `content_type`
  - `video`
  - `presentation`
  - `pdf`
  - `link`
  - `other`
- `description`
- `storage_disk`
- `storage_path`
- `thumbnail_path`
- `visibility`
- `targeting_mode`
- `is_required`
- `estimated_minutes`

### 10.3. Assignment targeting

Targeting imkanları:
- specific personnel
- structure
- position
- new hires cohort
- manual selected group

### 10.4. View tracking

- `opened_at`
- `completed_at`
- `watch_progress_percent`

### 10.6. Employee UX

`Öyrənmə materialları` tabında:
- mənə təyin edilənlər
- xoş gəldin content-ləri
- tamamlanmamışlar
- tamamlananlar

### 10.7. HR/Admin UX

- content library
- assignment rules
- completion analytics

---

## 11. Sənədlərim

Mövcud `Personnel.files` və `personnel.files` Livewire capability-si reuse ediləcək, amma employee-facing contract ayrıca olacaq.

### 11.1. Məqsəd

Employee öz sənədlərini görə bilməlidir:
- mövcud yüklənmiş sənədlər
- adı
- fayl tipi
- yükləmə
- baxış

### 11.2. Məhdudiyyət

Bu tab employee üçün read-only olacaq.

HR tərəfdə olan tam edit experience burada açılmayacaq.

---

## 12. Permissions

Yeni permission-lar:
- `show-my-hr`
- `submit-self-service-leaves`
- `submit-self-service-vacations`
- `submit-self-service-business-trips`
- `view-own-request-timeline`
- `request-own-request-correction`
- `view-own-notifications`
- `view-own-onboarding-documents`
- `acknowledge-own-onboarding-documents`
- `view-own-development-plan`
- `view-own-development-history`
- `view-own-learning-content`
- `view-own-hierarchy`
- `manage-onboarding-document-templates`
- `assign-onboarding-documents`
- `manage-development-plan-assignments`
- `review-request-change-requests`
- `manage-employee-content-library`
- `assign-employee-content`
- `view-my-hr-analytics`

Default prinsip:
- employee yalnız öz data-sını görür
- HR/Admin assignment və review capability-lərinə sahib olur

Manager self-service üçün ayrıca permission qatı sonra genişlənəcək.

---

## 13. Livewire və UI strukturu

Yeni shell:
- `my-hr.dashboard`

Child komponentlər:
- `my-hr.summary`
- `my-hr.requests`
- `my-hr.notifications`
- `my-hr.onboarding`
- `my-hr.development-plan`
- `my-hr.learning`
- `my-hr.documents`
- `my-hr.hierarchy`

UI prinsipləri:
- server-driven UI
- lazy tab loading
- shared field shell
- shared async button
- shared status chip
- detail drawer
- toast dispatch

Portal ayrıca employee-facing olacaq; HR-heavy mutation ekranları mövcud modullarda qalacaq.

---

## 14. Reporting və automation

Bu sistemin dəyəri yalnız form submission deyil. Tracking və automation vacibdir.

### 14.1. Reporting

Yeni admin/report capability-lər:
- `My HR adoption summary`
- request volume by type/status
- correction request aging
- onboarding read completion
- overdue acknowledgements
- development plan progress və overdue action-lar
- development source mix
- learning content completion

### 14.2. Automation

Job-lar:
- `my-hr:assign-onboarding`
- `my-hr:remind-unread-docs`
- `my-hr:enforce-request-policies`
- `my-hr:sync-development-plan-progress`
- `my-hr:remind-learning-content`

### 14.3. Policy sərtləşdirmə

- employee başqa employee data-sını görə bilməz
- restricted content ayrıca permission ilə qorunur
- archived və expired content ayrıca state alır
- correction request-lər stale qalmamalıdır
- stale development action-lar ayrıca analytics və reminder xəttinə düşməlidir

---

## 15. İcra ardıcıllığı

### Faza 1. Foundation

- `/my-hr` route və shell
- `UserPersonnelLinkResolver` contract hardening
- permissions
- employee-safe guards

### Faza 2. Ərizələrim

- unified request timeline
- leave/vacation/business trip self-service
- correction request workflow
- request status notification deep-link

### Faza 3. Fərdi inkişaf planı

- `MyDevelopmentPlanReadService`
- employee-facing development plan tabı
- training/performance linked plan summary
- progress sync qaydaları

### Faza 4. Onboarding docs

- template/assignment/receipt domain
- employee acknowledgement UX
- HR assignment panel
- reminder automation

### Faza 5. Struktur və profil konteksti

- rəhbər və direct reports chain
- structure breadcrumb
- own profile summary
- own documents tab

### Faza 6. Learning content

- storage-backed content library
- targeted assignments
- read/watch tracking
- welcome area

### Faza 7. Analytics və docs

- admin dashboard-lar
- acceptance checklist
- docs hub integration

---

## 16. Test plan

### Mapping

- linked user `/my-hr` açır və öz personnel context-i yüklənir
- mapping olmayan user remediation state görür

### Security

- employee başqa employee-nin request-lərini görə bilmir
- restricted onboarding və content assignment permission olmadan görünmür

### Requests

- employee leave yaradır
- employee vacation yaradır
- employee business trip request yaradır
- own request unified timeline-da görünür
- correction request yaradır, reviewer approve/reject edir

### Notifications

- request status update inbox-a düşür
- onboarding unread reminder düşür
- read tracking işləyir

### Development plan

- approved need employee development plan-da görünür
- completed training delivery progress-i yeniləyir
- cancelled source need active view-dən çıxır
- employee yalnız öz plan item-lərini görür

### Onboarding

- required document assignment görünür
- `opened_at` yazılır
- `acknowledged_at` yazılır
- overdue state ayrıca görünür

### Hierarchy

- `parent_id` üzərindən rəhbər görünür
- direct reports görünür
- manager olmayan employee fallback state alır

### Learning content

- target edilmiş asset yalnız uyğun employee-yə görünür
- open və completed tracking yazılır

### UI

- tab-lar lazy işləyir
- submit sonrası toast dispatch olur
- detail drawer employee-safe contract ilə dolur

### Docs

- `/docs?focus=my-hr` düzgün section-a gedir

---

## 17. Default qərarlar

- Bu plan yalnız `Employee Self-Service` üçündür
- `Leaves`, `Vacation`, `BusinessTrips` source-of-truth olaraq qalır
- correction request ayrıca generic workflow kimi qurulur
- hierarchy üçün source `personnels.parent_id` olur
- onboarding üçün həm `opened_at`, həm `acknowledged_at` saxlanır
- fərdi inkişaf planı üçün source-of-truth `TrainingNeeds` və bağlı delivery/result data olur
- employee development plan statusunu sərbəst dəyişmir
- welcome content `TrainingNeeds` session-larına qarışdırılmır
- ayrıca `employee_content` storage disk qurulur
- employee portal read/write contract ilə gəlir, HR master ekranlarını əvəz etmir

---

## 18. Qısa yekun

Bu tələbin düzgün professional forması belədir:
- ayrı-ayrı xırda button və form yığımı yox
- employee üçün vahid `My HR` self-service platforması

Bu platforma:
- request mərkəzi
- inbox
- onboarding compliance
- fərdi inkişaf planı
- learning content
- hierarchy
- own documents

xəttlərini birləşdirir və mövcud HR sistemini employee-facing təcrübəyə çevirir.

---

## 19. Sprint task breakdown

Bu bölmə implementasiyanı mərhələli və icra edilə bilən backlog-a çevirir. Məqsəd odur ki, iş paralel və təhlükəsiz şəkildə bölünsün, amma məhsul axını qırılmasın.

### Sprint 1. Foundation və shell

- [ ] `My HR` route yarat
- [ ] `My HR` navigation entry əlavə et
- [ ] `my-hr.dashboard` Livewire shell yarat
- [ ] `UserPersonnelLinkResolver` əsasında employee context bootstrap et
- [ ] mapping olmayan user üçün remediation empty-state yarat
- [ ] `show-my-hr` permission əlavə et
- [ ] employee-only authorization guard-larını qur
- [ ] docs hub üçün `focus=my-hr` section hazırlığını et

### Sprint 2. Ərizələrim workspace

- [ ] `MyRequestsReadService` yarat
- [ ] `Leaves`, `Vacation`, `BusinessTrips` üçün unified request contract qur
- [ ] source metadata sahələrini əlavə et:
  - `submission_source`
  - `submitted_by_user_id`
- [ ] `Ərizələrim` tab UI-ni qur
- [ ] request type, status, date filter-ləri əlavə et
- [ ] detail drawer contract-ını tamamla
- [ ] employee leave self-service create flow qur
- [ ] employee vacation self-service create flow qur
- [ ] employee business trip self-service create flow qur
- [ ] submit sonrası toast/notify dispatch-lərini bağla

### Sprint 3. Düzəliş müraciəti workflow-u

- [ ] `employee_request_change_requests` cədvəlini yarat
- [ ] correction request model və policy qatını qur
- [ ] `Düzəliş istə` action-ını request detail içində əlavə et
- [ ] proposed patch JSON contract-ını təyin et
- [ ] reviewer üçün correction review paneli qur
- [ ] approve/reject/writeback axınını əlavə et
- [ ] audit və notification sync-ni bağla
- [ ] stale correction request policy-lərini əlavə et

### Sprint 4. Bildirişlər və inbox

- [ ] employee inbox tabını mövcud notifications query-ləri ilə bağla
- [ ] unread count widget-i dashboard-a çıxar
- [ ] request status update bildirişlərini employee deep-link ilə bağla
- [ ] correction result bildirişlərini əlavə et
- [ ] onboarding assignment bildirişlərini əlavə et
- [ ] unread onboarding reminder bildirişlərini əlavə et
- [ ] learning content assignment bildirişlərini əlavə et
- [ ] `read_at` əsaslı grouped inbox görünüşünü employee panelində tamamla

### Sprint 5. Fərdi inkişaf planı

- [ ] `MyDevelopmentPlanReadService` yarat
- [ ] approved need, plan item və delivery nəticələrini vahid employee contract-a çevir
- [ ] `Fərdi inkişaf planım` tabını qur
- [ ] `Aktiv plan`, `Gözləyən addımlar`, `Tamamlananlar`, `İnkişaf tarixçəsi` bölmələrini əlavə et
- [ ] progress status və source badge-lərini qur
- [ ] related session/material deep-link-lərini əlavə et
- [ ] self-note və evidence upload qaydasını dəqiqləşdirib UI əlavə et
- [ ] completed delivery -> progress sync automation-ını qur
- [ ] overdue development action reminder-lərini əlavə et

### Sprint 6. Onboarding sənədləri

- [ ] `onboarding_document_templates` cədvəlini yarat
- [ ] `onboarding_document_assignments` cədvəlini yarat
- [ ] `onboarding_document_receipts` cədvəlini yarat
- [ ] HR/Admin template library UI-ni qur
- [ ] employee onboarding tabını qur
- [ ] `opened_at` tracking-i əlavə et
- [ ] `acknowledged_at` tracking-i əlavə et
- [ ] overdue state və due date semantics-ını əlavə et
- [ ] new-hire auto-assignment job-ını qur
- [ ] unread/overdue reminder job-larını əlavə et

### Sprint 7. Hierarchy və profile context

- [ ] `Personnel` modelinə `manager()` relation əlavə et
- [ ] `Personnel` modelinə `directReports()` relation əlavə et
- [ ] structure breadcrumb/query helper-lərini əlavə et
- [ ] `Mənim strukturum` tabını qur
- [ ] rəhbər kartı görünüşünü əlavə et
- [ ] direct reports list görünüşünü əlavə et
- [ ] upstream tabellik chain görünüşünü əlavə et
- [ ] manager olmayan employee üçün fallback state əlavə et
- [ ] dashboard summary-də rəhbər/struktur qısa info widget-i əlavə et

### Sprint 8. Öyrənmə materialları və welcome content

- [ ] `employee_content_assets` cədvəlini yarat
- [ ] `employee_content_assignments` cədvəlini yarat
- [ ] `employee_content_views` cədvəlini yarat
- [ ] `employee_content` disk config əlavə et
- [ ] HR/Admin content library UI-ni qur
- [ ] targeting rules əlavə et:
  - personnel
  - structure
  - position
  - new hires cohort
- [ ] employee learning content tabını qur
- [ ] `opened_at`, `completed_at`, `watch_progress_percent` tracking-i əlavə et
- [ ] required content semantics və reminder-ləri qur
- [ ] welcome content block-u dashboard-a əlavə et

### Sprint 9. Sənədlərim

- [ ] mövcud `Personnel.files` read layer-i employee contract-a uyğunlaşdır
- [ ] `Sənədlərim` tabını read-only qur
- [ ] preview/download link-lərini employee-safe şəkildə təqdim et
- [ ] file type, ölçü, title və uploaded date görünüşünü sabitləşdir
- [ ] HR-only mutating actions-ı employee səthindən çıxart

### Sprint 10. Analytics, policy və docs

- [ ] `My HR` adoption summary analytics qur
- [ ] request type/status analytics qur
- [ ] correction request aging analytics qur
- [ ] onboarding read completion analytics qur
- [ ] development plan progress analytics qur
- [ ] learning content completion analytics qur
- [ ] scheduler command-ları əlavə et:
  - `my-hr:assign-onboarding`
  - `my-hr:remind-unread-docs`
  - `my-hr:enforce-request-policies`
  - `my-hr:sync-development-plan-progress`
  - `my-hr:remind-learning-content`
- [ ] permission matrix-i seeding və role mapping ilə tamamla
- [ ] `/docs?focus=my-hr` docs section-u əlavə et
- [ ] employee acceptance checklist və admin ops checklist yaz

### Cross-cutting task-lar

- [ ] bütün self-service form-larda validation attribute map əlavə et
- [ ] bütün save/update/review action-larında `notify` dispatch olduğundan əmin ol
- [ ] query budget command-ları əlavə et
- [ ] render benchmark command-ları əlavə et
- [ ] employee-facing tab-lar üçün lazy placeholder və loading state-ləri qur
- [ ] mobile-first layout yoxlaması et
- [ ] notification deep-link və return URL axınlarını sabitləşdir
- [ ] audit log coverage-ni yoxla

### Final acceptance sprint

- [ ] employee link olan user `/my-hr`-ə girə bilir
- [ ] employee öz leave/vacation/business trip müraciətini yarada bilir
- [ ] employee öz request-i üçün correction request yarada bilir
- [ ] onboarding sənədi açılır, read və acknowledgement yazılır
- [ ] fərdi inkişaf planı düzgün source-lardan yığılır
- [ ] hierarchy görünüşündə rəhbər və tabellik xətti görünür
- [ ] targeted learning content yalnız uyğun employee-yə görünür
- [ ] inbox-da bütün self-service bildirişləri görünür
- [ ] HR/Admin assignment və review axınları işləyir
- [ ] docs və in-app help keçidləri doğru yerə gedir

---

## 20. Implementation backlog appendix

Bu bölmə sprint-ləri implementasiya səviyyəsində konkretləşdirir. Buradakı fayl adları və yerləşimlər repo-nun hazırkı modul strukturuna uyğun seçilib.

### Sprint 1. Foundation və shell

Yeni route və provider inteqrasiyası:
- `app/Modules/Personnel/Routes/web.php`
- `app/Modules/Personnel/Providers/PersonnelServiceProvider.php`
- `app/Support/Permissions/PermissionDescriptionCatalog.php`
- `database/seeders/PersonnelSeeder.php`

Yeni Livewire komponentlər:
- `app/Modules/Personnel/Livewire/MyHr/MyHrDashboard.php`
- `app/Modules/Personnel/Livewire/MyHr/MyHrSummary.php`

Yeni blade view-lər:
- `app/Modules/Personnel/Resources/views/livewire/personnel/my-hr/dashboard.blade.php`
- `app/Modules/Personnel/Resources/views/livewire/personnel/my-hr/summary.blade.php`

Mümkün shared support qatları:
- `app/Modules/Personnel/Support/MyHr/MyHrAccess.php`
- `app/Modules/Personnel/Support/MyHr/MyHrTabs.php`

Testlər:
- `tests/Feature/Personnel/MyHr/MyHrDashboardTest.php`
- `tests/Feature/Personnel/MyHr/MyHrAccessTest.php`

### Sprint 2. Ərizələrim workspace

Mövcud model dəyişiklikləri:
- `app/Models/Leave.php`
- `app/Models/PersonnelVacation.php`
- `app/Models/PersonnelBusinessTrip.php`

Yeni migration-lar:
- `app/Modules/Leaves/Database/Migrations/*_add_self_service_submission_fields_to_leaves_table.php`
- `app/Modules/Vacation/Database/Migrations/*_add_self_service_submission_fields_to_personnel_vacations_table.php`
- `app/Modules/BusinessTrips/Database/Migrations/*_add_self_service_submission_fields_to_personnel_business_trips_table.php`

Yeni service-lər:
- `app/Modules/Personnel/Application/Services/MyHr/MyRequestsReadService.php`
- `app/Modules/Personnel/Application/Services/MyHr/MyRequestPresenter.php`

Yeni Livewire komponent:
- `app/Modules/Personnel/Livewire/MyHr/MyRequests.php`

Yeni blade view:
- `app/Modules/Personnel/Resources/views/livewire/personnel/my-hr/requests.blade.php`

Testlər:
- `tests/Feature/Personnel/MyHr/MyRequestsTest.php`
- `tests/Feature/Leaves/LeaveSelfServiceSubmissionTest.php`
- `tests/Feature/Vacation/VacationSelfServiceSubmissionTest.php`
- `tests/Feature/BusinessTrips/BusinessTripSelfServiceSubmissionTest.php`

### Sprint 3. Düzəliş müraciəti workflow-u

Yeni model və migration:
- `app/Models/EmployeeRequestChangeRequest.php`
- `app/Modules/Personnel/Database/Migrations/*_create_employee_request_change_requests_table.php`

Yeni service/policy qatları:
- `app/Modules/Personnel/Application/Services/MyHr/RequestChangeRequestService.php`
- `app/Modules/Personnel/Policies/EmployeeRequestChangeRequestPolicy.php`

Yeni Livewire komponentlər:
- `app/Modules/Personnel/Livewire/MyHr/RequestChangeRequestForm.php`
- `app/Modules/Personnel/Livewire/MyHr/RequestChangeReviewPanel.php`

Yeni blade view-lər:
- `app/Modules/Personnel/Resources/views/livewire/personnel/my-hr/request-change-request-form.blade.php`
- `app/Modules/Personnel/Resources/views/livewire/personnel/my-hr/request-change-review-panel.blade.php`

Testlər:
- `tests/Feature/Personnel/MyHr/RequestChangeRequestTest.php`
- `tests/Feature/Personnel/MyHr/RequestChangeReviewTest.php`

### Sprint 4. Bildirişlər və inbox

Reuse ediləcək mövcud qat:
- `app/Modules/Notifications/Livewire/NotificationList.php`

Yeni employee-facing qat:
- `app/Modules/Personnel/Livewire/MyHr/MyNotifications.php`
- `app/Modules/Personnel/Resources/views/livewire/personnel/my-hr/notifications.blade.php`
- `app/Modules/Personnel/Application/Services/MyHr/MyNotificationDeepLinkService.php`

Mümkün yeni notification class-ları:
- `app/Notifications/OnboardingDocumentAssigned.php`
- `app/Notifications/RequestChangeReviewed.php`
- `app/Notifications/LearningContentAssigned.php`

Testlər:
- `tests/Feature/Personnel/MyHr/MyNotificationsTest.php`
- `tests/Feature/Notifications/MyHrNotificationFlowTest.php`

### Sprint 5. Fərdi inkişaf planı

Yeni read qat:
- `app/Modules/Personnel/Application/Services/MyHr/MyDevelopmentPlanReadService.php`
- `app/Modules/Personnel/Application/Services/MyHr/MyDevelopmentPlanPresenter.php`

Yeni Livewire komponent:
- `app/Modules/Personnel/Livewire/MyHr/MyDevelopmentPlan.php`

Yeni blade view:
- `app/Modules/Personnel/Resources/views/livewire/personnel/my-hr/development-plan.blade.php`

Mümkün supporting concern:
- `app/Modules/Personnel/Livewire/MyHr/Concerns/InteractsWithDevelopmentPlan.php`

Əgər evidence/self-note tələb olunarsa:
- `app/Modules/Personnel/Database/Migrations/*_create_employee_development_plan_notes_table.php`
- `app/Models/EmployeeDevelopmentPlanNote.php`

Testlər:
- `tests/Feature/Personnel/MyHr/MyDevelopmentPlanTest.php`
- `tests/Feature/TrainingNeeds/MyDevelopmentPlanSyncTest.php`

### Sprint 6. Onboarding sənədləri

Yeni model-lər:
- `app/Models/OnboardingDocumentTemplate.php`
- `app/Models/OnboardingDocumentAssignment.php`
- `app/Models/OnboardingDocumentReceipt.php`

Yeni migration-lar:
- `app/Modules/Personnel/Database/Migrations/*_create_onboarding_document_templates_table.php`
- `app/Modules/Personnel/Database/Migrations/*_create_onboarding_document_assignments_table.php`
- `app/Modules/Personnel/Database/Migrations/*_create_onboarding_document_receipts_table.php`

Yeni service-lər:
- `app/Modules/Personnel/Application/Services/MyHr/OnboardingAssignmentService.php`
- `app/Modules/Personnel/Application/Services/MyHr/OnboardingReceiptService.php`

Yeni Livewire komponentlər:
- `app/Modules/Personnel/Livewire/MyHr/MyOnboarding.php`
- `app/Modules/Personnel/Livewire/MyHr/OnboardingTemplateManager.php`
- `app/Modules/Personnel/Livewire/MyHr/OnboardingAssignmentsManager.php`

Yeni blade view-lər:
- `app/Modules/Personnel/Resources/views/livewire/personnel/my-hr/onboarding.blade.php`
- `app/Modules/Personnel/Resources/views/livewire/personnel/my-hr/onboarding-template-manager.blade.php`
- `app/Modules/Personnel/Resources/views/livewire/personnel/my-hr/onboarding-assignments-manager.blade.php`

Yeni command-lar:
- `app/Modules/Personnel/Console/Commands/AssignOnboardingDocumentsCommand.php`
- `app/Modules/Personnel/Console/Commands/RemindUnreadOnboardingDocumentsCommand.php`

Testlər:
- `tests/Feature/Personnel/MyHr/MyOnboardingTest.php`
- `tests/Feature/Personnel/MyHr/OnboardingAssignmentAdminTest.php`

### Sprint 7. Hierarchy və profile context

Mövcud model update:
- `app/Models/Personnel.php`

Yeni read/service qat:
- `app/Modules/Personnel/Application/Services/MyHr/MyHierarchyReadService.php`

Yeni Livewire komponent:
- `app/Modules/Personnel/Livewire/MyHr/MyHierarchy.php`

Yeni blade view:
- `app/Modules/Personnel/Resources/views/livewire/personnel/my-hr/hierarchy.blade.php`

Testlər:
- `tests/Feature/Personnel/MyHr/MyHierarchyTest.php`

### Sprint 8. Öyrənmə materialları və welcome content

Yeni model-lər:
- `app/Models/EmployeeContentAsset.php`
- `app/Models/EmployeeContentAssignment.php`
- `app/Models/EmployeeContentView.php`

Yeni migration-lar:
- `app/Modules/Personnel/Database/Migrations/*_create_employee_content_assets_table.php`
- `app/Modules/Personnel/Database/Migrations/*_create_employee_content_assignments_table.php`
- `app/Modules/Personnel/Database/Migrations/*_create_employee_content_views_table.php`

Config:
- `config/personnel.php` və ya ayrıca `config/my_hr.php`
- `config/filesystems.php` içində `employee_content` disk

Yeni service-lər:
- `app/Modules/Personnel/Application/Services/MyHr/EmployeeContentAssignmentService.php`
- `app/Modules/Personnel/Application/Services/MyHr/EmployeeContentTrackingService.php`

Yeni Livewire komponentlər:
- `app/Modules/Personnel/Livewire/MyHr/MyLearning.php`
- `app/Modules/Personnel/Livewire/MyHr/EmployeeContentLibraryManager.php`

Yeni blade view-lər:
- `app/Modules/Personnel/Resources/views/livewire/personnel/my-hr/learning.blade.php`
- `app/Modules/Personnel/Resources/views/livewire/personnel/my-hr/employee-content-library-manager.blade.php`

Yeni command:
- `app/Modules/Personnel/Console/Commands/RemindEmployeeLearningContentCommand.php`

Testlər:
- `tests/Feature/Personnel/MyHr/MyLearningContentTest.php`
- `tests/Feature/Personnel/MyHr/EmployeeContentAssignmentAdminTest.php`

### Sprint 9. Sənədlərim

Reuse ediləcək mövcud qat:
- `app/Modules/Personnel/Livewire/Files.php`
- `app/Models/PersonnelDocument.php`

Yeni employee-facing qat:
- `app/Modules/Personnel/Application/Services/MyHr/MyDocumentsReadService.php`
- `app/Modules/Personnel/Livewire/MyHr/MyDocuments.php`
- `app/Modules/Personnel/Resources/views/livewire/personnel/my-hr/documents.blade.php`

Testlər:
- `tests/Feature/Personnel/MyHr/MyDocumentsTest.php`

### Sprint 10. Analytics, policy və docs

Yeni analytics qat:
- `app/Modules/Personnel/Application/Services/MyHr/MyHrAnalyticsService.php`

Yeni Livewire komponent:
- `app/Modules/Personnel/Livewire/MyHr/MyHrAnalytics.php`
- `app/Modules/Personnel/Resources/views/livewire/personnel/my-hr/analytics.blade.php`

Yeni command-lar:
- `app/Modules/Personnel/Console/Commands/EnforceMyHrPoliciesCommand.php`
- `app/Modules/Personnel/Console/Commands/SyncMyHrDevelopmentPlanProgressCommand.php`

Docs inteqrasiyası:
- `app/Http/Controllers/TrainingPerformanceGuideController.php`
- `resources/views/docs/partials/guide-overview.blade.php`
- `resources/views/docs/partials/guide-my-hr.blade.php`
- `routes/web.php`

Testlər:
- `tests/Feature/Personnel/MyHr/MyHrAnalyticsTest.php`
- `tests/Feature/Docs/MyHrGuidePageTest.php`

### Shared UI komponent backlog-u

Yeni reusable komponentlər:
- `resources/views/components/my-hr/request-status-chip.blade.php`
- `resources/views/components/my-hr/timeline-card.blade.php`
- `resources/views/components/my-hr/acknowledgement-panel.blade.php`
- `resources/views/components/my-hr/content-assignment-card.blade.php`
- `resources/views/components/my-hr/hierarchy-card.blade.php`

Mümkün shared translation faylları:
- `app/Modules/Personnel/Resources/lang/az/my_hr.php`
- `app/Modules/Personnel/Resources/lang/en/my_hr.php`

### Query/render guard backlog-u

Yeni benchmark və budget command-ları:
- `app/Modules/Personnel/Console/Commands/MyHrQueryBudgetCommand.php`
- `app/Modules/Personnel/Console/Commands/MyHrRenderBenchmarkCommand.php`

Testlər:
- `tests/Feature/Console/MyHrQueryBudgetCommandTest.php`
- `tests/Feature/Console/MyHrRenderBenchmarkCommandTest.php`
