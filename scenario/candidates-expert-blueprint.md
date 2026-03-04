# Candidates Modulu - Expert Blueprint (HRM)

Bu sənəd HRM layihəsi üçün `Candidates` modulunu professional, genişlənə bilən və həm hərbi, həm də mülki qurumlara uyğun şəkildə qurmaq üçün referansdır.

## 1. Məqsəd

Candidates bölməsi yalnız "müraciət edən siyahısı" olmamalıdır. Bu modul aşağıdakıları təmin etməlidir:

1. Namizədin müraciətdən işə qəbul təsdiqinə qədər bütün lifecycle-i izləmək.
2. İşə qəbul qərarını audit-lənən, ölçülə bilən pipeline üzərindən idarə etmək.
3. `Orders` və `Personnel` modulları ilə itkisiz inteqrasiya qurmaq.
4. Hərbi və mülki fərqli tələbləri eyni platformada idarə etmək.

## 2. Cari vəziyyət (layihədə mövcud olan)

Mövcud modul:

1. `candidates` cədvəli üzərindən əsas namizəd məlumatlarını saxlayır.
2. `status_id` (`appeal_statuses`) ilə vəziyyəti izləyir.
3. Əsas Livewire komponentləri var:
   1. `CandidateList`
   2. `AddCandidate`
   3. `EditCandidate`
   4. `DeleteCandidate`
4. Orders confirm axınında candidate -> personnel keçidi var.

Bu baza işləkdir, amma enterprise ATS səviyyəsi üçün əlavələr lazımdır.

## 3. Hədəf domen modeli (hərbi + mülki birlikdə)

Tövsiyə olunan model: **Single Candidate Core + Profile Extensions**.

1. `candidates` nüvə (hamıda ortaq sahələr).
2. Profile tipinə görə əlavə cədvəllər:
   1. `candidate_profile_military`
   2. `candidate_profile_civil`
3. Pipeline və tarixçə hər ikisi üçün ortaq qalır.

Niyə bu yanaşma:

1. Kod təkrarını azaldır.
2. Hərbi sahələri mülki axına zorla salmır.
3. UI və validation profile-ə görə dəyişir.
4. Gələcəkdə 3-cü profil əlavə etmək asandır.

## 4. Migration səviyyəsində konkret DB schema

Aşağıdakı cədvəllər prioritetdir.

### 4.1. `candidates` (core)

Minimum sütunlar:

1. `id`
2. `candidate_code` (unikal, external reference)
3. `profile_type` enum: `military|civil`
4. `name`, `surname`, `patronymic`
5. `birthdate`, `gender`
6. `phone`, `email`
7. `fin` (və ya uyğun identifikator; unikal indeks siyasəti ilə)
8. `source` (referral, portal və s.)
9. `structure_id` (müraciət etdiyi struktur)
10. `position_id` (müraciət etdiyi vəzifə)
11. `status_id`
12. `owner_user_id` (recruiter)
13. `created_by`, `updated_by`, `deleted_by`
14. `created_at`, `updated_at`, `deleted_at`

Indexlər:

1. `unique(candidate_code)`
2. `index(status_id, profile_type)`
3. `index(structure_id, position_id)`
4. `index(owner_user_id, created_at)`
5. `index(fin)` (iş qaydasına görə unique və ya partial unique)

### 4.2. `candidate_profile_military`

1. `id`
2. `candidate_id` (FK, unique)
3. `height`
4. `military_service`
5. `knowledge_test`
6. `physical_fitness_exam`
7. `research_date`, `research_result`
8. `attitude_to_military`
9. `hhk_date`, `hhk_result`
10. `discrediting_information`
11. `useless_info`
12. timestamps

### 4.3. `candidate_profile_civil`

1. `id`
2. `candidate_id` (FK, unique)
3. `experience_years`
4. `education_level`
5. `salary_expectation`
6. `notice_period_days`
7. `skills_json`
8. timestamps

### 4.4. `candidate_applications`

Bir namizəd bir neçə vakansiyaya müraciət edə bilər.

1. `id`
2. `candidate_id`
3. `vacancy_id` (və ya `structure_id + position_id`)
4. `applied_at`
5. `status_id` (application-level)
6. `source`
7. `priority`
8. notes
9. timestamps

Index:

1. `index(candidate_id, applied_at)`
2. `index(vacancy_id, status_id)`

### 4.5. `candidate_status_histories`

1. `id`
2. `candidate_id`
3. `from_status_id`
4. `to_status_id`
5. `reason`
6. `changed_by`
7. `changed_at`
8. `payload_json`

Index:

1. `index(candidate_id, changed_at)`
2. `index(to_status_id, changed_at)`

### 4.6. `candidate_documents`

1. `id`
2. `candidate_id`
3. `doc_type` (cv, id_card, diploma, military_doc ...)
4. `file_path`
5. `file_checksum`
6. `mime`, `size`
7. `is_verified`
8. `verified_by`, `verified_at`
9. timestamps

### 4.7. `candidate_interviews`

1. `id`
2. `candidate_id`
3. `stage` (screening/hr/tech/final)
4. `scheduled_at`
5. `interviewer_user_id`
6. `result` (pass/fail/hold)
7. `score`
8. `feedback`
9. timestamps

### 4.8. `candidate_scorecards`

1. `id`
2. `candidate_id`
3. `interview_id` (nullable)
4. `criteria_key`
5. `score`
6. `weight`
7. `scored_by`
8. timestamps

### 4.9. `candidate_notes`

1. `id`
2. `candidate_id`
3. `note`
4. `visibility` (`private|team`)
5. `author_id`
6. timestamps

### 4.10. `candidate_tags` və `candidate_tag_pivot`

1. Tag taxonomy + çoxlu filtrləmə üçün.

### 4.11. `candidate_conversions`

Candidate -> Personnel keçidini audit etmək üçün.

1. `id`
2. `candidate_id`
3. `personnel_id`
4. `order_log_id`
5. `converted_by`
6. `converted_at`
7. `metadata_json`

## 5. Status machine (qaydalı keçid)

Statuslar sərbəst dəyişməməlidir. Cədvəl və ya config ilə keçid matrisi olmalıdır.

Tövsiyə olunan statuslar:

1. `new`
2. `screening`
3. `interview`
4. `shortlist`
5. `reserve`
6. `rejected`
7. `offer_prepared`
8. `hired_pending`
9. `hired_confirmed`

Hər keçid üçün:

1. icazə (policy)
2. məcburi reason (bəzilərində)
3. event dispatch
4. history write

## 6. Livewire component xəritəsi (ekran/action/event)

### 6.1. Mövcud komponentlər (qorunur, refactor olunur)

1. `CandidateList`
2. `AddCandidate`
3. `EditCandidate`
4. `DeleteCandidate`

### 6.2. Tövsiyə olunan yeni komponentlər

1. `CandidatesBoard` (kanban)
   1. Action: stage move
   2. Event: `candidateStatusChanged`
2. `CandidateProfile`
   1. Tabs: Overview, Applications, Interviews, Documents, Notes, Timeline
3. `CandidateApplicationEditor`
   1. Action: add/update application
   2. Event: `candidateApplicationSaved`
4. `CandidateInterviewPlanner`
   1. Action: schedule/reschedule, result submit
   2. Event: `candidateInterviewUpdated`
5. `CandidateDocumentManager`
   1. Action: upload/verify/delete
   2. Event: `candidateDocumentChanged`
6. `CandidateConvertToPersonnel`
   1. Action: prepare order/confirm conversion
   2. Event: `candidateConverted`

### 6.3. Event contract nümunəsi

1. `candidateCreated`
2. `candidateUpdated`
3. `candidateStatusChanged`
4. `candidateInterviewScheduled`
5. `candidateDocumentVerified`
6. `candidateConvertedToPersonnel`

## 7. Hərbi və mülki axını bir sistemdə necə ayırmaq

Əsas qayda: **ayrılıq UI və validation-da olmalıdır, core data model parçalanmamalıdır**.

1. `profile_type` seçimi məcburi.
2. Dynamic form schema:
   1. military schema
   2. civil schema
3. Hər bir schema üçün ayrıca validation ruleset.
4. Görünən sütunlar/filtrlər profile-ə görə dəyişir.
5. Eyni pipeline, fərqli mərhələ qaydaları mümkündür.

Nəticə:

1. Hərbi qurum üçün mövcud funksiya itmir.
2. Mülki qurum üçün əlavə field-lər eyni modulda işləyir.
3. Kod bazası iki ayrı modul kimi bölünmür, maintain etmək asan qalır.

## 8. Orders + Personnel inteqrasiyası (vacib)

1. Candidate qəbul order-i təsdiqlənəndə:
   1. candidate status `hired_confirmed`
   2. personnel yaradılır/aktivləşir
   3. `is_pending=false`
   4. qalıcı `tabel_no` generate olunur
2. `candidate_conversions` yazılır.
3. Labor activity və staff schedule update event-ləri dispatch olunur.

## 9. Security, audit, compliance

1. PII sahələrə role-based access.
2. Download/upload log-ları.
3. Status change və conversion audit trail (silinməz iz).
4. Soft delete + retention policy.
5. Export action-ları audit olunmalıdır.

## 10. Performance tələbləri

1. List endpoint N+1 olmamalıdır.
2. Candidate list üçün lazy eager loading.
3. Ağır filtrlərdə composite index.
4. Document və timeline ayrı sorğularla (tab açılmadan yüklənməsin).
5. Kanban üçün page-size limit + cache (status counters).

## 11. Mərhələli tətbiq planı

### Phase A - Foundation

1. `profile_type` + profile extension cədvəlləri.
2. `candidate_status_histories` və state transition servisi.
3. Mövcud Add/Edit komponentlərinin profile-aware form-a keçirilməsi.

### Phase B - Pipeline + UX

1. Kanban board.
2. Interview planner və scorecard.
3. Document checklist.

### Phase C - Conversion hardening

1. `candidate_conversions` tam audit.
2. Orders integration guard-ları.
3. KPI və report layer.

## 12. KPI-lar

1. Time-to-hire
2. Stage conversion rate
3. Source effectiveness
4. Rejection reasons distribution
5. Pending -> confirmed delay

## 13. Acceptance criteria (definition of done)

1. Yeni candidate həm military, həm civil profil ilə problemsiz yaradılır.
2. Hər status dəyişimi history-yə düşür.
3. Conversion zamanı personnel + tabel_no + labor activity düzgün yaranır.
4. N+1 yox, list sorğuları stabil query budget içindədir.
5. Audit və export log-ları tamdır.

## 14. Bu sənədin istifadəsi

Bu sənəd roadmap kimi istifadə olunur:

1. DB migration planı çıxarmaq.
2. Livewire task breakdown hazırlamaq.
3. QA test case-ləri yazmaq.
4. Production rollout checklist etmək.
