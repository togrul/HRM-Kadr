# HR CRM / HCM boşluq analizi və inkişaf yol xəritəsi

> **Status (10.10.2026):** bu sənədin işarələri fevral–may aylarından sonra yenilənməyib; açıq qalan bəndlərin çoxu real vəziyyəti əks etdirməyə bilər. Aktiv iş siyahısı `~/Desktop/projects/ISLER_PLANI.md`-dir. Bəndi icra etməzdən əvvəl kodda yoxlayın.
>
> **Tutuşdurma (11.10.2026):** 82/82 bənd kodla yoxlanıb: 36 `[x]` (dəlil bəndin yanında), 46 açıq. Lifecycle, ATS, My HR, goals/succession əsasən qurulub; benefits, employee relations və bir sıra analitika/avtomatlaşdırma bəndləri kodda yoxdur.

Bu sənəd mövcud HRM layihəsində artıq olan əsas İK nüvəsini daha tam `HR CRM / HCM` səviyyəsinə çıxarmaq üçün çatışmayan sahələri və onların necə qurulmalı olduğunu izah edir.

Hazırkı sistemin güclü tərəfləri:

- personal və şəxsi işlər
- əmrlər
- məzuniyyət / icazə / ezamiyyət
- davamiyyət
- təlim ehtiyacı və təlim hesabatları
- performans qiymətləndirməsi və test axını

Hazırkı əsas boşluqlar:

1. işçi lifecycle idarəetməsi
2. ATS / recruitment CRM
3. employee self-service / manager self-service
4. goal management / OKR / succession / career path
5. benefits / insurance / dependents / total rewards
6. employee relations / disciplinary case management

Bu sənədin məqsədi:

- hər boşluq üçün nə etmək lazım olduğunu göstərmək
- modul, ekran, workflow və data model səviyyəsində düşüncə vermək
- işi `must-have / should-have / nice-to-have` qaydasında prioritetləşdirmək
- icra üçün konkret to-do siyahısı vermək

---

## 1. İşə giriş, oriyentasiya, probation və çıxış lifecycle modulu

### Problem nədir

Hazırkı sistemdə işçi qeydi, sənədlər və əmrlər var, amma `employee lifecycle orchestration` yoxdur.

Yəni bunlar görünmür:

- preboarding checklist
- IT / access account açılışı
- onboarding task ownership
- probation period goal və review
- exit checklist
- asset return
- exit interview
- separation reason analytics

### Nə qurulmalıdır

Ayrı `Employee Lifecycle` modulu yaradılmalıdır.

Bu modul 4 hissədən ibarət olmalıdır:

1. `Preboarding / Onboarding`
2. `Probation`
3. `Internal movement lifecycle`
4. `Offboarding`

### Data modeli

Tövsiyə olunan cədvəllər:

- `employee_lifecycle_events`
- `employee_onboarding_plans`
- `employee_onboarding_tasks`
- `employee_onboarding_task_logs`
- `employee_probation_periods`
- `employee_probation_reviews`
- `employee_exit_cases`
- `employee_exit_tasks`
- `employee_exit_interviews`
- `employee_asset_returns`

### Əsas workflow

#### A. Onboarding

1. işə qəbul təsdiqlənir
2. işçidən personal record yaranır və ya pending employee açılır
3. sistem onboarding plan yaradır
4. HR, IT, admin, direct manager üçün task-lar paylanır
5. task-lar tamamlandıqca status dəyişir
6. onboarding tamamlananda employee tam aktiv olur

#### B. Probation

1. işə başlama tarixi ilə probation period açılır
2. probation goal-ları əlavə olunur
3. manager review və HR review tarixləri planlanır
4. ara qiymətləndirmə və son qiymətləndirmə daxil edilir
5. nəticə:
   - confirm
   - extend
   - fail / exit

#### C. Offboarding

1. çıxış qərarı və ya resignation açılır
2. exit case yaranır
3. checklist:
   - asset return
   - access closure
   - sənəd təslimi
   - financial clearance
4. exit interview keçirilir
5. case bağlanır

### Ekranlar

Lazım olan əsas ekranlar:

1. Lifecycle dashboard
2. Onboarding pipeline board
3. Employee onboarding detail page
4. Probation calendar / queue
5. Probation review form
6. Exit case list
7. Exit checklist workspace
8. Exit analytics report

### İnteqrasiyalar

- `Candidates` -> hire olduqda onboarding başlasın
- `Personnel` -> employee profile ilə bağlı olsun
- `Services / Users` -> hesab açılışı task-ları ilə bağlı olsun
- `Orders` -> təyinat və işdən çıxma əmrləri ilə bağlı olsun
- `Attendance` -> join/leave tarixləri effekt versin

### Must-have to-do

- [x] `Employee Lifecycle` modulunu yarat — kod: app/Modules/EmployeeLifecycle/ (marşrut /employee-lifecycle, Livewire/Dashboard.php)
- [x] onboarding entity-lərini və migration-ları əlavə et — kod: app/Modules/EmployeeLifecycle/Database/Migrations/2026_05_03_120000_create_employee_lifecycle_tables.php
- [x] onboarding checklist template məntiqi qur — kod: app/Modules/EmployeeLifecycle/Database/Migrations/2026_05_03_122000_create_employee_lifecycle_plan_templates.php, Application/Services/LifecyclePlanTemplateService.php
- [x] role-based task owner məntiqi qur — kod: LifecyclePlanTemplateService::launchForPersonnel() (owner_type hr/manager/it/employee, ownerUserId)
- [x] probation period və probation review data model-i əlavə et — kod: app/Modules/EmployeeLifecycle/Database/Migrations/2026_05_03_123000_create_employee_lifecycle_probation_reviews.php, LifecyclePlanTemplateService::scheduleProbationReview()/completeProbationReview()
- [x] exit case + exit checklist + exit interview model-lərini əlavə et — kod: app/Modules/EmployeeLifecycle/Database/Migrations/2026_05_03_125000_create_employee_lifecycle_offboarding_cases.php, LifecyclePlanTemplateService::openOffboardingCase()/completeOffboardingCase()
- [x] employee detail ilə lifecycle timeline əlaqəsini qur — kod: app/Modules/Personnel/Application/Services/Personnel360TimelineService.php::lifecycleEvents()
- [x] notification və deadline reminder axını əlavə et — kod: app/Modules/EmployeeLifecycle/Application/Services/LifecycleReminderService.php, Console/Commands/SendLifecycleDeadlineRemindersCommand.php, tests/Feature/EmployeeLifecycle/LifecycleDeadlineReminderCommandTest.php

### Should-have to-do

- [ ] onboarding SLA tracking — kodda tapılmadı (EmployeeLifecycle-də SLA izləmə yoxdur)
- [ ] overdue task analytics — qismən: LifecycleDashboardReadService::overdueTasks() gecikmiş tapşırıq siyahısını verir; trend/analitika yoxdur
- [x] probation extension decision flow — kod: app/Modules/EmployeeLifecycle/Livewire/Dashboard.php (`probation_decision` in:confirm,extend,terminate), LifecyclePlanTemplateService::completeProbationReview()
- [ ] exit səbəb kateqoriyaları və trend report — qismən: offboarding_cases.reason sərbəst mətn saxlanır; səbəb kateqoriyası və trend hesabatı yoxdur
- [ ] asset return integration — qismən: offboarding checklist-də 'verify_handover_assets' tapşırığı var (LifecyclePlanTemplateService::defaultOffboardingChecklist()); aktiv/inventar modulu ilə inteqrasiya yoxdur

### Nice-to-have to-do

- [ ] onboarding buddy assignment — kodda tapılmadı
- [ ] e-signature workflow — kodda tapılmadı (yalnız OnboardingDocumentReceipt qəbz qeydi var, e-imza yoxdur)
- [ ] survey-based onboarding feedback — kodda tapılmadı

---

## 2. ATS / Recruitment CRM modulu

### Problem nədir

Namizədlər modulu var, amma əsasən list + CRUD səviyyəsindədir. Tam recruitment CRM üçün lazım olan xətt görünmür.

Olmalı olan amma çatmayan hissələr:

- job requisition
- requisition approval
- vacancy pipeline
- recruitment stages
- interview scheduling
- interviewer panel
- scorecard
- offer management
- source tracking
- hire conversion analytics

### Nə qurulmalıdır

Mövcud `Candidates` modulu tam `ATS / Recruitment` moduluna çevrilməlidir.

Ən doğru struktur:

1. `Requisitions`
2. `Vacancies`
3. `Candidate Pipeline`
4. `Interview Center`
5. `Offer Management`
6. `Hiring Analytics`

### Data modeli

Tövsiyə olunan cədvəllər:

- `job_requisitions`
- `job_requisition_approvals`
- `job_openings`
- `candidate_pipeline_stages`
- `candidate_stage_histories`
- `candidate_sources`
- `candidate_interviews`
- `candidate_interview_panelists`
- `candidate_interview_scorecards`
- `candidate_offers`
- `candidate_offer_approvals`
- `candidate_hires`

### Əsas workflow

1. manager requisition yaradır
2. HR / finance / rəhbərlik approval verir
3. approved requisition vacancy-yə çevrilir
4. candidate-lər həmin vacancy-yə bağlanır
5. pipeline mərhələləri ilə hərəkət edir:
   - applied
   - screening
   - interview
   - assessment
   - offer
   - hired
   - rejected
6. offer approval gedir
7. hire olunca onboarding trigger olur

### Ekranlar

1. Requisition list
2. Requisition detail
3. Vacancy board
4. Candidate pipeline board
5. Candidate profile
6. Interview calendar
7. Interview scorecard screen
8. Offer screen
9. Source analytics dashboard

### Must-have to-do

- [x] `Candidates` modulunu route və IA baxımından parçala — kod: app/Modules/Candidates/Routes/web.php (candidates.requisitions/openings/applications/analytics ayrıca marşrutlar)
- [x] requisition entity və approval flow əlavə et — kod: job_requisitions, app/Modules/Candidates/Application/Services/CandidateAtsCompletionService.php::submitRequisition()/approveRequisition()
- [x] candidate stage pipeline modeli qur — kod: candidate_stage_events, app/Modules/Candidates/Application/Services/CandidateApplicationStageService.php::moveToStage(), Livewire/ApplicationPipeline.php
- [x] vacancy ilə candidate əlaqəsini qur — kod: candidate_applications.job_opening_id → job_openings.job_requisition_id
- [x] interview scheduling və panel scorecard əlavə et — kod: candidate_interviews, candidate_scorecards, CandidateAtsCompletionService::scheduleInterview()/submitScorecard()
- [x] offer entity və approval flow qur — kod: candidate_offers, CandidateAtsCompletionService::createOffer()/updateOfferStatus() (status axını; təsdiq əmr axını CandidateHireOrderService vasitəsilədir)
- [x] hired candidate -> personnel / lifecycle keçidi əlavə et — kod: app/Modules/Candidates/Application/Services/CandidateHireConversionService.php::convert()/ensureLifecycleEvent()
- [x] basic conversion analytics əlavə et — kod: app/Modules/Candidates/Livewire/RecruitmentAnalytics.php (summaryCards/stageSummary)

### Should-have to-do

- [x] source effectiveness report — kod: RecruitmentAnalytics::sourceEffectivenessSummary()
- [x] rejection reason analytics — kod: RecruitmentAnalytics::rejectionReasonSummary()
- [ ] interviewer calibration report — kodda tapılmadı
- [x] talent pool / silver medalist bucket — kod: candidate_talent_pool_entries, CandidateAtsCompletionService::addToTalentPool()
- [ ] requisition aging dashboard — qismən: CandidateAtsCompletionService::requisitionAging() var, UI-da göstərilmir

### Nice-to-have to-do

- [ ] email template automation — kodda tapılmadı
- [ ] calendar integration — kodda tapılmadı
- [ ] recruitment SLA and bottleneck heatmap — kodda tapılmadı

### İcra ardıcıllığı

Faza 1:

- requisition
- approval
- pipeline stages
- hire conversion

Faza 2:

- interview center
- offer management
- analytics

Faza 3:

- automation
- integrations
- advanced reporting

---

## 3. Employee Self-Service / Manager Self-Service

### Problem nədir

Sistemdə HR-centric ekranlar güclüdür, amma işçi və rəhbər üçün mərkəzi self-service portal görünmür.

Çatışmayan tipik hissələr:

- şəxsi kabinet
- own profile update request
- leave balance görünüşü
- training history
- performance nəticəsi
- approvals inbox
- team overview
- quick actions

### Nə qurulmalıdır

Ayrı `My HR` portal yaradılmalıdır.

Bu portal 2 əsas görünüşlə işləməlidir:

1. `Employee Self-Service`
2. `Manager Self-Service`

### Əsas capability-lər

#### Employee Self-Service

- profil baxışı
- profil düzəliş tələbi
- sənəd baxışı
- təlim tarixçəsi
- performans nəticələri
- test transcript
- leave / vacation / business trip request status
- attendance monthly summary
- task və notification inbox

#### Manager Self-Service

- team snapshot
- pending approvals
- team attendance exceptions
- team leave calendar
- team training needs
- probation reviews
- performance review task-ları

### Data / tətbiq qatında nə lazımdır

- current user -> personnel mapping hər modulda standartlaşdırılmalıdır
- `my_*` query layer qurulmalıdır
- approval queue-lar ortaq inbox-a yığılmalıdır
- request tracking status timeline ilə göstərilməlidir

### Ekranlar

1. `My HR` dashboard
2. `My profile`
3. `My documents`
4. `My requests`
5. `My attendance`
6. `My learning`
7. `My performance`
8. `Manager team dashboard`
9. `Manager approvals inbox`

### Must-have to-do

- [x] `My HR` portal route və navigation yarat — kod: app/Modules/Personnel/Routes/web.php (`/my-hr`, name my-hr), Livewire/MyHr/MyHrDashboard.php
- [x] user-personnel mapping-i bütün modullərdə vahid contract ilə standartlaşdır — kod: app/Models/UserPersonnelLink.php + app/Modules/Personnel/Support/MyHr/MyHrAccess.php::resolvePersonnelId(), Livewire/MyHr/Concerns/ResolvesOwnPersonnel.php; Leaves/Performance modullarında da istifadə olunur
- [x] `my requests` timeline ekranı qur — kod: app/Modules/Personnel/Application/Services/MyHr/MyHrRequestsReadService.php, tab `requests`
- [x] leave / vacation / business trip request summary-ni employee portal-a çıxar — kod: MyHrDashboard (leaves/vacations/trips sayğacları), MyHrRequestsReadService::build()
- [ ] training history və performance result summary-ni employee portal-a çıxar — qismən: təlim tarixçəsi — MyHrLearningReadService, MyHrDevelopmentPlanReadService; performans nəticəsi My HR-da yoxdur
- [x] manager approvals inbox qur — kod: app/Modules/Personnel/Livewire/MyHr/SelfServiceRequestReviews.php (marşrut self-service-reviews), Application/Services/MyHr/MyHrRequestReviewService.php
- [ ] team attendance / leave snapshot qur — qismən: MyHrHierarchyReadService birbaşa tabeliklər; komanda davamiyyət/məzuniyyət snapshot-u My HR-da yoxdur

### Should-have to-do

- [ ] profile update request workflow — qismən: MyHrRequestCorrectionService yalnız məzuniyyət/ezamiyyət sorğularının düzəlişini qəbul edir; profil məlumatı dəyişmə sorğusu yoxdur
- [x] downloadable employee documents — kod: app/Modules/Personnel/Application/Services/MyHr/MyHrDocumentsReadService.php, marşrut personnel.files.download
- [ ] personal KPI summary — kodda tapılmadı (My HR tablarında KPI yoxdur)
- [ ] manager escalation reminders — kodda tapılmadı (menecer eskalasiya xatırlatması yoxdur)

### Nice-to-have to-do

- [ ] mobile-first quick actions — yoxlanmadı (MyHrDashboard::goto() sürətli keçidlər var, mobile-first quick action ayrıca yoxlanmayıb)
- [ ] chatbot / assistant entrypoint — kodda tapılmadı
- [ ] personalized recommendations — kodda tapılmadı

---

## 4. Goal management, OKR/KPI cascade, succession və career path

### Problem nədir

Hazırkı sistem:

- performansı ölçür
- test nəticəsi verir
- training need yaradır

Amma etmədiyi şey:

- biznes goal-ları employee-yə cascade etmək
- KPI və OKR izləmək
- ardıl planlama qurmaq
- career path göstərmək
- critical role backup readiness ölçmək

### Nə qurulmalıdır

Bu hissə ayrıca `Talent Management` kimi düşünülməlidir.

İçində 4 alt modul olmalıdır:

1. `Goals / OKR`
2. `Individual Development Plan`
3. `Career Paths`
4. `Succession Planning`

### Data modeli

- `goal_cycles`
- `goal_templates`
- `goal_assignments`
- `goal_key_results`
- `goal_checkins`
- `development_plans`
- `development_plan_actions`
- `career_paths`
- `career_path_steps`
- `succession_pools`
- `succession_candidates`
- `critical_roles`
- `readiness_reviews`

### Əsas workflow

#### A. Goals / OKR

1. company objective açılır
2. department goal-ları cascade olunur
3. manager team goal-larını paylayır
4. employee key result update edir
5. period sonunda goal nəticəsi performance ilə birləşir

#### B. IDP

1. weak area və ya manager review nəticəsi çıxır
2. development plan yaranır
3. action type seçilir:
   - training
   - mentoring
   - coaching
   - stretch assignment
4. owner və target date verilir
5. periodik check-in ilə izlənir

#### C. Succession

1. critical role müəyyən edilir
2. possible successors əlavə olunur
3. readiness level verilir:
   - ready now
   - ready in 1 year
   - ready in 2 years
4. development actions bağlanır

### Ekranlar

1. Goal dashboard
2. Goal cycle board
3. Employee goals workspace
4. IDP board
5. Career path catalog
6. Succession matrix
7. Critical roles dashboard
8. Talent review calibration page

### Must-have to-do

- [x] `Goals` data model-i və cycle məntiqini qur — kod: app/Modules/PerformanceEvaluation/Database/Migrations/2026_06_23_120000_create_performance_goal_tables.php (performance_goals + performance_cycles), Livewire/GoalsWorkspace.php
- [x] organization -> department -> employee cascade model-i yarat — kod: performance_goals.parent_goal_id, personnel_id=null təşkilat səviyyəsi (departament üçün ayrıca səviyyə yoxdur)
- [x] progress update və check-in ekranları əlavə et — kod: performance_goal_checkins, performance_scorecard_checkins (2026_09_21_140000_add_kpi_full_cycle_tables.php)
- [ ] `IDP` entity-lərini qur — qismən: ayrıca IDP cədvəli yoxdur; inkişaf planı TrainingNeedItem-dən qurulur (app/Modules/Personnel/Application/Services/MyHr/MyHrDevelopmentPlanReadService.php)
- [ ] training need ilə IDP arasında əlaqə yarat — qismən: performance_training_need_links və TrainingNeedItem əsaslı inkişaf planı var; IDP entity-si olmadığı üçün tam əlaqə yoxdur
- [x] `critical_roles` və `succession_candidates` modeli qur — kod: succession_plans (kritik rol), succession_candidates — app/Modules/PerformanceEvaluation/Database/Migrations/2026_06_24_120000_create_succession_tables.php (`critical_roles` adlı cədvəl yoxdur)
- [x] readiness review ekranı əlavə et — kod: app/Modules/PerformanceEvaluation/Livewire/SuccessionWorkspace.php, SuccessionService::setCandidateReadiness()

### Should-have to-do

- [x] KPI weighting — kod: performance_goals.weight_percent, performance_kpi_template_items weight, kpi_weight_share/competency_weight_share (2026_09_21_130000_create_performance_kpi_tables.php)
- [x] calibration session notes — kod: performance_calibration_adjustments (delta, reason) — 2026_09_21_140000_add_kpi_full_cycle_tables.php, app/Models/PerformanceCalibrationAdjustment.php (sessiya anlayışı yoxdur)
- [x] high-potential pool — kod: talent_pools.pool_type=hipo, talent_pool_members, SuccessionService::createPool()/addMember()
- [ ] career aspiration capture — kodda tapılmadı

### Nice-to-have to-do

- [x] 9-box potential/performance matrix — kod: app/Modules/PerformanceEvaluation/Application/Services/SuccessionService.php::nineBox(), talent_assessments
- [ ] succession risk heatmap — qismən: succession_plans.risk_of_loss/impact_of_loss saxlanır, heatmap vizuallaşması yoxdur
- [ ] AI-assisted development suggestion — kodda tapılmadı

### İcra prinsipi

Bu sahə birbaşa performance modulunun üstünə tikilməlidir. Ayrı, disconnected modul olmamalıdır.

Ən doğru ardıcıllıq:

1. goals
2. IDP
3. succession
4. career path vizualizasiya

---

## 5. Benefits, insurance, dependent və total rewards

### Problem nədir

İşçi təcrübəsi yalnız attendance, leave və training ilə tamamlanmır.

Korporativ HR sistemində tipik olaraq bunlar olur:

- salary package görünüşü
- benefit enrollment
- insurance plan
- dependent management
- allowance and perks
- total rewards statement

Hazırkı sistemdə bu sahə görünmür.

### Nə qurulmalıdır

Ayrı `Compensation & Benefits` modulu yaradılmalıdır.

Alt bloklar:

1. `Benefit plans`
2. `Employee enrollment`
3. `Dependents`
4. `Allowances / perks`
5. `Total rewards`

### Data modeli

- `benefit_plans`
- `benefit_plan_options`
- `employee_benefit_enrollments`
- `employee_dependents`
- `benefit_contributions`
- `employee_allowances`
- `total_rewards_snapshots`

### Əsas workflow

1. HR benefit plan yaradır
2. employee və ya HR enrollment açır
3. dependents bağlanır
4. employer / employee contribution hesablanır
5. snapshot yaradılır
6. employee total rewards statement görür

### Ekranlar

1. Benefits admin
2. Enrollment workspace
3. Employee dependents
4. Allowance management
5. Total rewards statement

### Must-have to-do

- [ ] benefit plan və enrollment data model-i yarat — kodda tapılmadı (benefit/enrollment cədvəli yoxdur)
- [ ] dependent registry qur — qismən: app/Models/PersonnelKinship.php ailə üzvləri reyestri var; dependent/benefit bayrağı yoxdur
- [ ] employee benefit visibility ekranı yarat — kodda tapılmadı
- [x] allowance / perk registry əlavə et — kod: compensation_components (earning/deduction katalogu) — app/Modules/Compensation/Database/Migrations/2026_06_28_000100_create_compensation_foundation_tables.php, 2026_06_28_000200_seed_compensation_catalog.php, Application/Services/AllowanceResolver.php
- [ ] total rewards summary ekranı yarat — qismən: Compensation modulunda işçi kompensasiya sətirləri var (employee_compensations/lines); benefit daxil total rewards xülasə ekranı yoxdur

### Should-have to-do

- [ ] payroll deduction integration — kodda tapılmadı
- [ ] renewal cycle management — kodda tapılmadı
- [ ] eligibility rules — kodda tapılmadı

### Nice-to-have to-do

- [ ] benefits comparison wizard — kodda tapılmadı
- [ ] benefit utilization analytics — kodda tapılmadı

### Risk və diqqət nöqtələri

- payroll integration olmadan bu modul yarımçıq qalar
- dependents və şəxsi məlumatlar səbəbilə privacy layer güclü olmalıdır
- audit trail mütləq olmalıdır

---

## 6. Employee relations / disciplinary case management

### Problem nədir

Admin master data-da `punishments` və `appeal statuses` var, amma əməliyyat tərəf yoxdur.

Yəni çatmayan hissələr:

- incident / complaint qeydiyyatı
- grievance case
- disciplinary process
- investigation log
- hearing and decision tracking
- closure note
- repeat offense analytics

### Nə qurulmalıdır

Ayrı `Employee Relations` modulu yaradılmalıdır.

Alt bloklar:

1. `Cases`
2. `Disciplinary actions`
3. `Grievances / complaints`
4. `Investigations`
5. `Closures and analytics`

### Data modeli

- `employee_relation_cases`
- `employee_relation_case_types`
- `employee_relation_parties`
- `employee_relation_actions`
- `employee_relation_evidence`
- `employee_relation_hearings`
- `employee_relation_decisions`
- `employee_relation_closures`

### Əsas workflow

1. case açılır
2. case type seçilir
3. involved employee-lər bağlanır
4. evidence və notes əlavə olunur
5. investigator / HR owner təyin olunur
6. hearing / review keçirilir
7. decision verilir
8. closure və follow-up yazılır

### Ekranlar

1. Case inbox
2. Case detail
3. Investigation timeline
4. Evidence panel
5. Decision panel
6. Closure analytics

### Must-have to-do

- [ ] case model-i və case type registry yarat — kodda tapılmadı (employee relations case modulu yoxdur)
- [ ] case detail timeline qur — kodda tapılmadı (employee relations case modulu yoxdur)
- [ ] evidence upload və audit log əlavə et — kodda tapılmadı (employee relations case modulu yoxdur)
- [ ] investigator / owner assignment əlavə et — kodda tapılmadı (employee relations case modulu yoxdur)
- [ ] decision və closure flow qur — kodda tapılmadı (employee relations case modulu yoxdur)
- [ ] disciplinary action linkage əlavə et — qismən: intizam tədbiri yalnız əmr təsiri kimi var (app/Modules/Orders/Infrastructure/Document/Effects/DisciplinaryEffect.php); case ilə əlaqə yoxdur

### Should-have to-do

- [ ] SLA and escalation — kodda tapılmadı (employee relations case modulu yoxdur)
- [ ] recurring issue analytics — kodda tapılmadı (employee relations case modulu yoxdur)
- [ ] policy breach taxonomy — kodda tapılmadı (employee relations case modulu yoxdur)

### Nice-to-have to-do

- [ ] anonymized whistleblowing intake — kodda tapılmadı (employee relations case modulu yoxdur)
- [ ] mediation workflow — kodda tapılmadı (employee relations case modulu yoxdur)
- [ ] legal hold support — kodda tapılmadı (employee relations case modulu yoxdur)

### Əsas prinsip

Bu modul sadəcə catalog deyil, `process engine` olmalıdır.
Master data ilə case workflow bir-birinə bağlanmalıdır.

---

## 7. Must-have / Should-have / Nice-to-have prioritet matrisi

## Must-have

Bunlar sistemi tam HR CRM / HCM istiqamətində real dəyər verən mərhələyə çıxarar:

1. Employee lifecycle
   - onboarding
   - probation
   - offboarding
2. ATS əsas xətti
   - requisition
   - pipeline
   - interview
   - hire conversion
3. Employee / manager self-service portal
4. Goals + IDP
5. Employee relations case workflow

## Should-have

1. Succession planning
2. Offer management advanced layer
3. Benefits enrollment
4. Total rewards summary
5. Advanced recruitment analytics
6. Manager team dashboards

## Nice-to-have

1. Career path visualization
2. 9-box matrix
3. Advanced benefits analytics
4. Whistleblowing / mediation
5. AI recommendation layers

---

## 8. Tövsiyə olunan icra yol xəritəsi

## Faza 1

Ən yüksək biznes dəyəri:

- Employee lifecycle
- ATS core
- Employee / manager self-service

## Faza 2

Talent qatını gücləndir:

- Goals / OKR
- IDP
- basic succession

## Faza 3

Employee experience və governance:

- Benefits
- Employee relations
- advanced analytics

## Faza 4

Strategic HR layer:

- career path
- advanced succession
- total rewards
- predictive reporting

---

## 9. Memarlıq tövsiyəsi

Bu modulları ayrı-ayrı disconnected şəkildə qurmaq olmaz.

Əsas shared contract-lar lazımdır:

- `User -> Personnel identity`
- `Employee lifecycle status`
- `Approvals inbox`
- `Task ownership`
- `Document / evidence storage`
- `Audit trail`
- `Notification center`

Cross-module əlaqələr:

- ATS -> Lifecycle
- Lifecycle -> Personnel
- Performance -> Goals / IDP
- Training -> IDP
- Attendance -> ESS/MSS
- Orders -> lifecycle və status dəyişiklikləri

---

## 10. Qısa yekun

Əgər məqsəd sadəcə güclü əməliyyat HRIS qurmaqdırsa, mövcud sistem artıq güclü bazaya sahibdir.

Əgər məqsəd `tam HR CRM / HCM` qurmaqdırsa, növbəti ən doğru inkişaf istiqaməti budur:

1. employee lifecycle
2. ATS
3. self-service portal
4. goals / IDP / succession
5. benefits
6. employee relations

Bu ardıcıllıq həm biznes dəyərinə, həm istifadəçi görünürlüğünə, həm də hazırkı sistemin memarlığına ən uyğun yoldur.

relative overflow-hidden rounded-[2rem] border border-zinc-200 bg-white shadow-[0_1px_2px_rgba(16,24,40,0.05)]
