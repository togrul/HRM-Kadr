# HR Strategic Roadmap TODO

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
- [ ] Orders -> appointment/transfer/exit order inteqrasiyası

Should-have:
- [ ] onboarding SLA tracking
- [ ] overdue lifecycle task dashboard
- [ ] asset/access handover checklist
- [ ] exit reason analytics

## 2. Candidate ATS Completion

Status: planned

Məqsəd: Candidates modulunu tam recruitment CRM / ATS səviyyəsinə çatdırmaq.

Must-have:
- [ ] job requisition entity
- [ ] requisition approval workflow
- [ ] vacancy/opening board
- [ ] candidate pipeline stage board
- [ ] interview scheduling
- [ ] interviewer panel və scorecard
- [ ] offer management
- [x] hired candidate conversion flow
- [ ] source/rejection analytics

Should-have:
- [ ] requisition aging dashboard
- [ ] interviewer calibration report
- [ ] talent pool / reserve candidates
- [ ] recruitment SLA heatmap

## 3. Employee 360 Profile / Timeline

Status: in progress

Məqsəd: personal profilində əməkdaşın bütün HR tarixçəsini vahid zaman xəttində göstərmək.

Must-have:
- [x] HR 360 timeline read service foundation
- [x] Timeline scope: orders, leave, vacation, business trip, training, performance, portfolio
- [x] Personnel profile UI-da ayrıca 360 tab və ya mövcud timeline tab-a HR 360 mode
- [ ] Audit dəyişiklikləri: kim nəyi dəyişib, nə vaxt dəyişib
- [ ] Timeline filterləri: activity type, date range, search
- [ ] Export: employee 360 timeline PDF/XLSX

Should-have:
- [ ] risk markers: expired documents, pending approvals, low score, repeated absence
- [ ] manager-facing simplified timeline
- [ ] sensitive events üçün permission-based redaction

## 4. HR Command Center Dashboard

Status: planned

Məqsəd: HR istifadəçisi login olanda günlük işi və riskləri bir ekranda görsün.

Must-have:
- [ ] pending approvals panel
- [ ] overdue lifecycle tasks
- [ ] expiring documents
- [ ] probation review queue
- [ ] open vacancies / requisition aging
- [ ] attendance exceptions
- [ ] training/performance action queue

Should-have:
- [ ] HR workload by owner
- [ ] SLA breach trend
- [ ] drill-down links to source modules

## 5. Career Path + Succession

Status: planned

Məqsəd: performance və training nəticələrini vəzifə readiness və successor planlamasına bağlamaq.

Must-have:
- [ ] role competency profile
- [ ] employee competency gap score
- [ ] target role readiness score
- [ ] successor pool per critical role
- [ ] development action plan
- [ ] performance + training + portfolio integration

Should-have:
- [ ] critical role risk dashboard
- [ ] promotion readiness history
- [ ] succession scenario comparison

## 6. Employee Relations Case Management

Status: planned

Məqsəd: şikayət, intizam, araşdırma və qərar proseslərini auditli, məxfi və idarə olunan case workflow-a çevirmək.

Must-have:
- [ ] employee relation cases
- [ ] case category: grievance, investigation, disciplinary, conflict, ethics
- [ ] case participants və witness records
- [ ] evidence/document attachments
- [ ] decision workflow
- [ ] appeal flow
- [ ] confidential permission model

Should-have:
- [ ] anonymized trend report
- [ ] repeat incident detection
- [ ] case SLA dashboard

## 7. Document Expiry / Compliance Center

Status: in progress

Məqsəd: personal sənədlərinin vaxtı keçməmiş HR xəbərdar olsun və uyğunluq riski azalsın.

Must-have:
- [ ] identity/passport/contract/medical/certificate expiry registry
- [x] document expiry dashboard foundation
- [ ] reminder rules
- [x] missing document report
- [ ] employee/self-service renewal request

Should-have:
- [x] compliance score per structure
- [ ] bulk reminder dispatch
- [ ] audit export

## İcra Ardıcıllığı

1. Employee 360 timeline foundation
2. Document expiry/compliance center
3. Employee lifecycle module
4. HR Command Center
5. Candidate ATS completion
6. Career path + succession
7. Employee relations case management
