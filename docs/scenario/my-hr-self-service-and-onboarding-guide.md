# My HR Self-Service və Onboarding Guide

Bu sənəd `My HR` employee self-service platformasının necə qurulacağını, hansı biznes qaydaları ilə işləyəcəyini və hansı mərhələlərlə implement ediləcəyini izah edir.

Məqsəd ayrı-ayrı “kiçik feature”lər toplamaq deyil. Məqsəd budur ki:
- əməkdaş üçün vahid şəxsi kabinet yaransın
- elektron ərizələr bir yerdən idarə olunsun
- onboarding sənədləri və learning content vahid employee experience içində toplansın
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
  - employee learning history və content relation-ləri üçün uyğun baza
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

## 7. Onboarding sənədləri

Müştərinin onboarding document tələbi compliance xarakterli bir domendir. Sadəcə “faylı employee-yə göstər” kifayət deyil.

Oxunma tarixi ayrıca izlənməlidir və lazım gəlsə tanış olma təsdiqi ayrıca saxlanmalıdır.

### 7.1. Yeni cədvəllər

- `onboarding_document_templates`
- `onboarding_document_assignments`
- `onboarding_document_receipts`

### 7.2. Template shape

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

### 7.3. Assignment shape

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

### 7.4. Receipt shape

- `assignment_id`
- `opened_at`
- `acknowledged_at`
- `acknowledged_ip`
- `acknowledged_user_agent`

### 7.5. Məhsul qaydası

Oxunma tarixi üçün:
- `opened_at`

Tanış oldum / qəbul etdim xətti üçün:
- `acknowledged_at`

Professional yanaşma budur ki, hər ikisi ayrıca saxlanılsın.

### 7.6. Employee UX

`Onboarding` tabında:
- oxunmalı sənədlər
- açılmış sənədlər
- tanış olunmuş sənədlər
- overdue sənədlər

Action-lar:
- aç
- yüklə
- tanış oldum

### 7.7. HR/Admin UX

- template library
- assignment list
- unread / overdue görünüşü
- version history

### 7.8. Automation

Yeni əməkdaş aktivləşəndə:
- default onboarding pack assign olunur

Reminder job-lar:
- unread docs reminder
- overdue acknowledgement reminder

---

## 8. Mənim strukturum

Müştərinin istəyi:
- əməkdaşın rəhbəri görünsün
- struktur bölməsi görünsün
- tabellik xətti görünsün
- kimə tabedir və kim ona tabedir ierarxiya şəklində görünsün

### 8.1. Authority source

Bu hissə üçün authoritative source:
- `personnels.parent_id`
- `personnels.structure_id`

Burada dolayı inference etməyəcəyik.

### 8.2. Yeni relation-lar

`Personnel` modelinə əlavə ediləcək:
- `manager()`
- `directReports()`

Lazım olduqda recursive helper:
- upstream chain
- downstream direct list

### 8.3. Employee UX

`Mənim strukturum` tabında:
- öz kartı
- rəhbər kartı
- struktur bölməsi
- yuxarı tabellik xətti
- mənə tabe olanlar

Qaydalar:
- `parent_id` boşdursa fallback state görünür
- direct reports yoxdursa boş grid qalmır

### 8.4. Nə üçün vacibdir

Bu hissə employee portal üçün çox dəyərlidir:
- kimə hesabat verir
- hansı strukturda işləyir
- öz komandası varmı

Bu data HR sistemində employee orientation üçün əsasdır.

---

## 9. Öyrənmə materialları, videolar və təqdimatlar

Müştərinin istədiyi welcome videoları və materiallar formal training session-lardan ayrı düşünülməlidir.

Bunlar:
- onboarding content ola bilər
- general awareness material ola bilər
- targeted təqdimat ola bilər
- hər zaman attendance və training delivery yaratmır

### 9.1. Yeni domain

- `employee_content_assets`
- `employee_content_assignments`
- `employee_content_views`

### 9.2. Asset shape

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

### 9.3. Assignment targeting

Targeting imkanları:
- specific personnel
- structure
- position
- new hires cohort
- manual selected group

### 9.4. View tracking

- `opened_at`
- `completed_at`
- `watch_progress_percent`

### 9.6. Employee UX

`Öyrənmə materialları` tabında:
- mənə təyin edilənlər
- xoş gəldin content-ləri
- tamamlanmamışlar
- tamamlananlar

### 9.7. HR/Admin UX

- content library
- assignment rules
- completion analytics

---

## 10. Sənədlərim

Mövcud `Personnel.files` və `personnel.files` Livewire capability-si reuse ediləcək, amma employee-facing contract ayrıca olacaq.

### 10.1. Məqsəd

Employee öz sənədlərini görə bilməlidir:
- mövcud yüklənmiş sənədlər
- adı
- fayl tipi
- yükləmə
- baxış

### 10.2. Məhdudiyyət

Bu tab employee üçün read-only olacaq.

HR tərəfdə olan tam edit experience burada açılmayacaq.

---

## 11. Permissions

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
- `view-own-learning-content`
- `view-own-hierarchy`
- `manage-onboarding-document-templates`
- `assign-onboarding-documents`
- `review-request-change-requests`
- `manage-employee-content-library`
- `assign-employee-content`
- `view-my-hr-analytics`

Default prinsip:
- employee yalnız öz data-sını görür
- HR/Admin assignment və review capability-lərinə sahib olur

Manager self-service üçün ayrıca permission qatı sonra genişlənəcək.

---

## 12. Livewire və UI strukturu

Yeni shell:
- `my-hr.dashboard`

Child komponentlər:
- `my-hr.summary`
- `my-hr.requests`
- `my-hr.notifications`
- `my-hr.onboarding`
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

## 13. Reporting və automation

Bu sistemin dəyəri yalnız form submission deyil. Tracking və automation vacibdir.

### 13.1. Reporting

Yeni admin/report capability-lər:
- `My HR adoption summary`
- request volume by type/status
- correction request aging
- onboarding read completion
- overdue acknowledgements
- learning content completion

### 13.2. Automation

Job-lar:
- `my-hr:assign-onboarding`
- `my-hr:remind-unread-docs`
- `my-hr:enforce-request-policies`
- `my-hr:remind-learning-content`

### 13.3. Policy sərtləşdirmə

- employee başqa employee data-sını görə bilməz
- restricted content ayrıca permission ilə qorunur
- archived və expired content ayrıca state alır
- correction request-lər stale qalmamalıdır

---

## 14. İcra ardıcıllığı

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

### Faza 3. Onboarding docs

- template/assignment/receipt domain
- employee acknowledgement UX
- HR assignment panel
- reminder automation

### Faza 4. Struktur və profil konteksti

- rəhbər və direct reports chain
- structure breadcrumb
- own profile summary
- own documents tab

### Faza 5. Learning content

- storage-backed content library
- targeted assignments
- read/watch tracking
- welcome area

### Faza 6. Analytics və docs

- admin dashboard-lar
- acceptance checklist
- docs hub integration

---

## 15. Test plan

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

## 16. Default qərarlar

- Bu plan yalnız `Employee Self-Service` üçündür
- `Leaves`, `Vacation`, `BusinessTrips` source-of-truth olaraq qalır
- correction request ayrıca generic workflow kimi qurulur
- hierarchy üçün source `personnels.parent_id` olur
- onboarding üçün həm `opened_at`, həm `acknowledged_at` saxlanır
- welcome content `TrainingNeeds` session-larına qarışdırılmır
- ayrıca `employee_content` storage disk qurulur
- employee portal read/write contract ilə gəlir, HR master ekranlarını əvəz etmir

---

## 17. Qısa yekun

Bu tələbin düzgün professional forması belədir:
- ayrı-ayrı xırda button və form yığımı yox
- employee üçün vahid `My HR` self-service platforması

Bu platforma:
- request mərkəzi
- inbox
- onboarding compliance
- learning content
- hierarchy
- own documents

xəttlərini birləşdirir və mövcud HR sistemini employee-facing təcrübəyə çevirir.
