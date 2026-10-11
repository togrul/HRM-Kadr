# HR Strategic Roadmap TODO

> **Status (10.10.2026):** bu sənədin işarələri fevral–may aylarından sonra yenilənməyib; açıq qalan bəndlərin çoxu real vəziyyəti əks etdirməyə bilər. Aktiv iş siyahısı `~/Desktop/projects/ISLER_PLANI.md`-dir. Bəndi icra etməzdən əvvəl kodda yoxlayın.
>
> **Tutuşdurma (11.10.2026):** 57/57 açıq bənd kodla yoxlanıb; 23 bənd `[x]`-ə keçdi (dəlil bəndin yanında), 34 bənd açıq qalır. Əvvəlki 17 `[x]` toxunulmayıb. Employee relations bölməsi kodda tamamilə yoxdur.

Bu sənəd HRM layihəsini əməliyyat HR sistemindən daha tam HCM platformasına çevirmək üçün icra siyahısıdır. Prioritet ardıcıllığı mövcud modul bazasına, riskə və HR üçün real dəyərə görə verilib.

## 1. Employee Lifecycle Module

Status: in progress

Məqsəd: işçinin namizəddən aktiv əməkdaşa, daxili yerdəyişməyə, probation-a və çıxışa qədər bütün həyat dövrünü idarə etmək.

Must-have:
- [x] `employee_lifecycle_events` data modeli
- [x] `employee_lifecycle_tasks` data modeli
- [x] lifecycle dashboard foundation: active events, overdue tasks, probation/offboarding queue
- [x] onboarding plan və task template-ləri
- [x] onboarding task owner məntiqi: HR, rəhbər, IT/admin
- [x] probation period və probation review axını
- [x] internal movement lifecycle: transfer, promotion, role change
- [x] offboarding case, exit checklist, exit interview
- [x] lifecycle deadline reminder-ləri
- [x] Candidates -> hired -> Personnel -> Lifecycle inteqrasiyası
- [x] Orders -> appointment/transfer/exit order inteqrasiyası — işə qəbul: CandidateHireConversionService::ensureOrderLifecycleForCandidate(); köçürmə/xitam: Orders effektləri (TransferEffect, TerminationEffect) → kontrakt app/Modules/EmployeeLifecycle/Contracts/OrderLifecycleSync.php → Application/Services/OrderLifecycleEventService.php (rol dəyişikliyi / offboarding checklist, gələcək tarixli köçürmə tətbiq anında, ləğvdə ləğv, idempotent); tests/Feature/EmployeeLifecycle/OrderLifecycleEventsTest.php

Should-have:
- [ ] onboarding SLA tracking — kodda tapılmadı (app/Modules/EmployeeLifecycle-də SLA izləmə yoxdur)
- [x] overdue lifecycle task dashboard — kod: app/Modules/EmployeeLifecycle/Application/Services/LifecycleDashboardReadService.php::overdueTasks(), Livewire/Dashboard.php
- [x] asset/access handover checklist — kod: LifecyclePlanTemplateService::defaultOffboardingChecklist() (close_system_access, verify_handover_assets); onboarding üçün şablon tapşırıqları
- [ ] exit reason analytics — qismən: offboarding_cases.reason saxlanır və siyahıda göstərilir (LifecycleDashboardReadService::offboardingCases()), amma səbəb üzrə analitika yoxdur

## 2. Candidate ATS Completion

Status: əsasən tamamlanıb (11.10.2026)

Məqsəd: Candidates modulunu tam recruitment CRM / ATS səviyyəsinə çatdırmaq.

Must-have:
- [x] job requisition entity — kod: job_requisitions cədvəli (app/Models/JobRequisition.php), Livewire/RequisitionList.php
- [x] requisition approval workflow — kod: app/Modules/Candidates/Application/Services/CandidateAtsCompletionService.php::submitRequisition()/approveRequisition()/rejectRequisition(), Livewire/RequisitionDetail.php
- [x] vacancy/opening board — kod: app/Modules/Candidates/Livewire/OpeningList.php, OpeningDetail.php, marşrut candidates.openings
- [x] candidate pipeline stage board — kod: app/Modules/Candidates/Livewire/ApplicationPipeline.php, marşrut candidates.applications
- [x] interview scheduling — kod: CandidateAtsCompletionService::scheduleInterview(), candidate_interviews cədvəli, Livewire/ApplicationAtsPanel.php
- [x] interviewer panel və scorecard — kod: candidate_scorecards (reviewer_id, criterion, score), CandidateAtsCompletionService::submitScorecard(); interview-da tək interviewer_id, panel qiymətləndirmə reviewer_id ilə
- [x] offer management — kod: candidate_offers, CandidateAtsCompletionService::createOffer()/updateOfferStatus()
- [x] hired candidate conversion flow
- [x] source/rejection analytics — kod: app/Modules/Candidates/Livewire/RecruitmentAnalytics.php::sourceSummary()/rejectionReasonSummary()

Should-have:
- [x] requisition aging dashboard — Home «HR idarəetmə mərkəzi»: HrCommandCenterReadService::requisitionAging(), tests/Feature/Personnel/HrCommandCenterTest.php
- [ ] interviewer calibration report — kodda tapılmadı
- [x] talent pool / reserve candidates — kod: candidate_talent_pool_entries, CandidateAtsCompletionService::addToTalentPool()
- [ ] recruitment SLA heatmap — kodda tapılmadı (RecruitmentAnalytics::timeToStageSummary() orta gün göstərir, heatmap yoxdur)

## 3. Employee 360 Profile / Timeline

Status: in progress

Məqsəd: personal profilində əməkdaşın bütün HR tarixçəsini vahid zaman xəttində göstərmək.

Must-have:
- [x] HR 360 timeline read service foundation
- [x] Timeline scope: orders, leave, vacation, business trip, training, performance, portfolio
- [x] Personnel profile UI-da ayrıca 360 tab və ya mövcud timeline tab-a HR 360 mode
- [x] Audit dəyişiklikləri: kim nəyi dəyişib, nə vaxt dəyişib — kod: app/Modules/Personnel/Application/Services/Personnel360TimelineService.php::auditChanges(), tests/Feature/Personnel/Personnel360TimelineServiceTest.php
- [x] Timeline filterləri: activity type, date range, search — kod: Personnel360TimelineService::build() ($filters type/date_from/date_to + search), app/Modules/Personnel/Livewire/Employee360Timeline.php
- [x] Export: employee 360 timeline PDF/XLSX — kod: app/Modules/Personnel/Application/Services/Personnel360ExportService.php, Http/Controllers/Personnel360ExportController.php (marşrut personnel.360.export, icazə export-personnel-360; PDF = PhpWord DOCX + LibreOffice, App\Contracts\DocumentPdfConverter), tests/Feature/Personnel/Personnel360ExportTest.php

Should-have:
- [ ] risk markers: expired documents, pending approvals, low score, repeated absence — kodda tapılmadı (timeline-da risk marker yoxdur; sənəd müddəti riski yalnız Home attention kartında: HomeOverviewService::expiringDocuments())
- [ ] manager-facing simplified timeline — kodda tapılmadı
- [x] sensitive events üçün permission-based redaction — kod: Personnel360TimelineService::redact() (intizam əmrləri → view-personnel-360-sensitive; xəstəlik vərəqəli icazələr → həmçinin view-medical-diagnosis), UI və ixracda

## 4. HR Command Center Dashboard

Status: in progress (qismən: Personnel Home)

Məqsəd: HR istifadəçisi login olanda günlük işi və riskləri bir ekranda görsün.

Must-have:
- [x] pending approvals panel — kod: app/Modules/Personnel/Application/Services/HomeOverviewService.php::attention()/queueItems() (vacation_requests, attendance_pending, unsigned_orders), app/Modules/Personnel/Livewire/Home.php::decide()
- [x] overdue lifecycle tasks — kod: app/Modules/Personnel/Application/Services/HrCommandCenterReadService.php (Home «HR idarəetmə mərkəzi» lazy island-ı), tests/Feature/Personnel/HrCommandCenterTest.php
- [x] expiring documents — kod: app/Modules/Personnel/Application/Services/HomeOverviewService.php::attention() 'expiring_documents' kartı, expiringDocuments()
- [x] probation review queue — kod: HrCommandCenterReadService::probationReviews() (gecikmiş + yaxın 14 gün)
- [x] open vacancies / requisition aging — kod: HrCommandCenterReadService::openVacancies(), requisitionAging() (köhnəlmə həddi config/strategic_hr.php)
- [x] attendance exceptions — kod: HrCommandCenterReadService::attendanceExceptions() (açıq attendance_exceptions, struktur görünürlüyü ilə)
- [ ] training/performance action queue — kodda tapılmadı

Should-have:
- [ ] HR workload by owner — kodda tapılmadı
- [ ] SLA breach trend — kodda tapılmadı
- [x] drill-down links to source modules — kod: app/Modules/Personnel/Application/Services/HomeOverviewService.php::attention() hər kart üçün 'route' (mənbə moduluna keçid)

## 5. Career Path + Succession

Status: in progress (succession var, career path yox)

Məqsəd: performance və training nəticələrini vəzifə readiness və successor planlamasına bağlamaq.

Must-have:
- [x] role competency profile — kod: app/Models/RoleCompetencyRequirement.php (position_id, training_competency_id, required_level_id)
- [x] employee competency gap score — kod: app/Modules/TrainingNeeds/Application/Services/TrainingNeedSuggestionService.php::resolveGap(), app/Models/EmployeeCompetencyProfile.php
- [x] target role readiness score — kod: app/Modules/PerformanceEvaluation/Application/Services/CareerReadinessScoreService.php (0–100, çəkilər config/strategic_hr.php), SuccessionWorkspace-də varis üzrə komponent töhfələri; tests/Feature/PerformanceEvaluation/CareerReadinessScoreTest.php
- [x] successor pool per critical role — kod: succession_plans/succession_candidates/talent_pools (app/Modules/PerformanceEvaluation/Database/Migrations/2026_06_24_120000_create_succession_tables.php), SuccessionService
- [x] development action plan — kod: app/Modules/Personnel/Application/Services/MyHr/MyHrDevelopmentPlanReadService.php (TrainingNeedItem əsaslı), Livewire/MyHr/MyHrDevelopmentPlan.php
- [x] performance + training + portfolio integration — kod: CareerReadinessScoreService (performans forması, tamamlanmış təlimlər, KPI kartı, təsdiqlənmiş portfolio qeydləri, staj)

Should-have:
- [x] critical role risk dashboard — kod: succession_plans.risk_of_loss/impact_of_loss, SuccessionService::summary() (plans_no_ready), Livewire/SuccessionWorkspace.php
- [ ] promotion readiness history — kodda tapılmadı
- [ ] succession scenario comparison — kodda tapılmadı

## 6. Employee Relations Case Management

Status: done (12.10.2026 — app/Modules/EmployeeRelations)

Məqsəd: şikayət, intizam, araşdırma və qərar proseslərini auditli, məxfi və idarə olunan case workflow-a çevirmək.

Must-have:
- [x] employee relation cases — kod: app/Modules/EmployeeRelations (employee_relation_cases, /employee-relations, işin kartı /employee-relations/cases/{id})
- [x] case category: grievance, investigation, disciplinary, conflict, ethics — redaktə olunan soraqça employee_relation_case_types (şikayət, intizam pozuntusu, münaqişə, təhqir/ayrı-seçkilik, əmək təhlükəsizliyi), «İş növləri» bölməsi
- [x] case participants və witness records — barəsində şikayət olunan, şikayətçi (anonim ola bilər), şahidlər (employee_relation_case_witnesses)
- [x] evidence/document attachments — employee_relation_case_evidence (fayl/izahat/yazışma), özəl disk + EvidenceDownloadController, UploadRules::document
- [x] decision workflow — CaseWorkflow (server tərəfli qraf + tarixçə), DecisionService: töhmət/şiddətli töhmət → OrderDrafter ilə intizam əmri qaralaması; ƏM Md.187 müddət bloku (DisciplinaryDeadline)
- [x] appeal flow — AppealService, Şəxsi kabinet «Şikayət və müraciətlər» bölməsi (MyCases), yenidən baxış → yekun
- [x] confidential permission model — CaseAccess (struktur görünürlüyü + sahib + araşdırıcı), anonim şikayətçinin kimliyi yalnız reveal-employee-relations-reporter ilə və audit-lə

Should-have:
- [x] anonymized trend report — StatisticsService (kiçik qruplar gizlədilir, min_group_size)
- [x] repeat incident detection — statistikada «təkrarlanan hallar» (repeat_threshold)
- [x] case SLA dashboard — mərhələ SLA-sı (config/employee_relations.php), gecikənlər filtri, employee-relations:sla-reminders

## 7. Document Expiry / Compliance Center

Status: in progress

Məqsəd: personal sənədlərinin vaxtı keçməmiş HR xəbərdar olsun və uyğunluq riski azalsın.

Must-have:
- [ ] identity/passport/contract/medical/certificate expiry registry — qismən: DocumentExpiryReadService::DOCUMENT_SOURCES — service_card, passport, id_card, contract; medical/certificate yoxdur
- [x] document expiry dashboard foundation
- [x] reminder rules — kod: compliance_document_requirements.warning_days/critical_days (2026_04_30_130000_create_compliance_document_requirements_table.php), app/Console/Commands/ComplianceDocumentReminderCommand.php (Kernel-də cədvəl)
- [x] missing document report
- [ ] employee/self-service renewal request — kodda tapılmadı

Should-have:
- [x] compliance score per structure
- [x] bulk reminder dispatch — kod: app/Modules/Compliance/Application/Services/ComplianceReminderNotifier.php::notify(), `compliance:document-reminders --notify`
- [ ] audit export — qismən: Livewire/DocumentExpiryDashboard.php::exportCsv() sənəd siyahısını ixrac edir; audit-məqsədli export yoxdur

## İcra Ardıcıllığı

1. Employee 360 timeline foundation
2. Document expiry/compliance center
3. Employee lifecycle module
4. HR Command Center
5. Candidate ATS completion
6. Career path + succession
7. Employee relations case management
