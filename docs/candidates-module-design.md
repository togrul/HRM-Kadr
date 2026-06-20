# Candidates Module Design

This document defines the target professional design for the `Candidates` module
for three deployment profiles:

- private / corporate
- public / state
- military / security

It also explains the current state of the module, the architectural gaps, the
required entities and screens, and the recommended refactor order.

## 1. Executive summary

The current `Candidates` module is not yet a full recruitment platform. It is
closer to a military-oriented candidate screening register.

Current code and schema show that the module is centered around a single
`candidates` record with military-heavy screening fields:

- [app/Models/Candidate.php](/Users/togruljalalli/Desktop/projects/HRM/app/Models/Candidate.php)
- [app/Modules/Candidates/Database/Migrations/2024_01_15_104819_create_candidates_table.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Candidates/Database/Migrations/2024_01_15_104819_create_candidates_table.php)
- [app/Modules/Candidates/Support/CandidateModeResolver.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Candidates/Support/CandidateModeResolver.php)
- [config/candidates.php](/Users/togruljalalli/Desktop/projects/HRM/config/candidates.php)

This is acceptable for a military/security institution as a first phase, but it
is not enough for:

- corporate recruitment
- public-sector competitive hiring
- shared long-term candidate management
- vacancy-based hiring pipeline
- multi-application candidate journeys

The correct long-term target is:

1. candidate master profile
2. requisition / vacancy
3. application pipeline
4. stage history
5. assessments and interviews
6. offer / appointment decision
7. hire conversion

## 2. Current state

### 2.1 What exists now

The module currently provides:

- candidate list
- candidate add/edit/delete
- candidate documents
- military/civilian runtime mode toggle
- mode-based field visibility and list filters
- candidate export

Main files:

- [app/Modules/Candidates/Livewire/CandidateList.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Candidates/Livewire/CandidateList.php)
- [app/Modules/Candidates/Resources/views/livewire/candidates/candidate-list.blade.php](/Users/togruljalalli/Desktop/projects/HRM/app/Modules/Candidates/Resources/views/livewire/candidates/candidate-list.blade.php)
- [docs/candidates-dual-mode.md](/Users/togruljalalli/Desktop/projects/HRM/docs/candidates-dual-mode.md)

### 2.2 Why the current structure is military-heavy

The current table includes fields like:

- `height`
- `military_service`
- `knowledge_test`
- `physical_fitness_exam`
- `research_result`
- `discrediting_information`
- `attitude_to_military`
- `hhk_date`
- `hhk_result`
- `useless_info`

These are not general recruitment entities. They are profile-specific screening
attributes mostly suitable for military/security hiring.

### 2.3 Main architectural gap

The current design makes `candidate` itself the center of the module.

Professional recruitment systems make `application to a vacancy` the center.

That distinction matters because:

- one candidate can apply to multiple vacancies
- each application can have different stages
- each application can have different interviewers, scores, outcomes, and audit
- one candidate may be rejected for one vacancy and hired for another

## 3. Design principles

The future module should follow these principles:

1. One candidate profile can have multiple applications.
2. One vacancy can have multiple candidates.
3. Stages must be history-based, not only current-status based.
4. Sector-specific checks must be isolated into separate entities.
5. Every hiring decision must be auditable.
6. The UI must follow the real business workflow, not only CRUD.
7. Profile-specific behavior must come from policy packs or workflow packs, not
   from random conditionals scattered across components.

## 4. How the module should work by profile

## 4.1 Private / corporate

### Goal

Fast, practical, recruiter-friendly hiring focused on time-to-hire and quality
of hire.

### Core workflow

1. vacancy opens
2. candidates enter from one or more sources
3. recruiter screening
4. hiring manager review
5. optional assessment / task
6. interview rounds
7. reference check if needed
8. offer
9. accepted / rejected / pool
10. hire conversion

### Main data points

- CV / resume
- source
- salary expectation
- notice period
- location / work model
- skills
- experience summary
- recruiter notes
- interview scorecards
- offer status

### What should not be mandatory

- military test fields
- physical exam
- security vetting fields unless role-specific

## 4.2 Public / state

### Goal

Transparent, fair, documented, audit-ready recruitment based on merit and
formal rules.

### Core workflow

1. requisition or competition is opened
2. legal basis and vacancy criteria are defined
3. candidates submit applications
4. eligibility and completeness screening
5. structured assessment or exam
6. commission interview
7. scoring and ranking
8. reserve list or selection decision
9. objection / appeal handling if required
10. appointment decision

### Main data points

- competition number
- legal basis
- vacancy publication period
- eligibility result
- document completeness result
- exam score
- interview commission score
- final ranking
- reserve list flag
- appeal result
- appointment outcome

### Critical requirements

- full audit trail
- scoring transparency
- consistent criteria
- retention and recordkeeping
- permission-separated decision flow

## 4.3 Military / security

### Goal

Suitability, reliability, discipline, fitness, and security-oriented intake.

### Core workflow

1. application received
2. basic document screening
3. suitability screening
4. knowledge / aptitude test
5. physical test
6. medical board
7. background / integrity research
8. commission decision
9. appointment readiness
10. hire / reject

### Main data points

- military service status
- physical results
- medical board result
- background research result
- discrediting information
- discipline / reliability findings
- final commission decision

### Important rule

Military-specific checks must stay in dedicated assessment tables, not remain
mixed into the universal candidate master.

## 5. Target domain model

The recommended target entities are below.

### Core entities

- `candidates`
- `job_requisitions`
- `job_openings`
- `candidate_applications`
- `candidate_stage_templates`
- `candidate_stage_events`
- `candidate_documents`
- `candidate_notes`
- `candidate_tags`

### Assessment entities

- `application_assessments`
- `application_assessment_scores`
- `application_interviews`
- `application_interview_panelists`
- `application_interview_scorecards`

### Outcome entities

- `candidate_offers`
- `candidate_offer_approvals`
- `candidate_appointments`
- `candidate_hires`
- `candidate_rejection_reasons`

### Pooling and sourcing

- `candidate_sources`
- `candidate_source_touchpoints`
- `candidate_pools`
- `candidate_pool_members`

### Military / public specializations

- `military_screenings`
- `medical_board_results`
- `security_clearance_checks`
- `public_competition_results`
- `public_appeal_records`

## 6. Required new tables

These are the tables that should be added first for the long-term design.

### Phase 1 tables

- `job_requisitions`
- `job_openings`
- `candidate_applications`
- `candidate_stage_events`
- `candidate_rejection_reasons`
- `candidate_sources`

### Phase 2 tables

- `application_assessments`
- `application_interviews`
- `application_interview_panelists`
- `application_interview_scorecards`
- `candidate_offers`
- `candidate_offer_approvals`

### Phase 3 tables

- `candidate_hires`
- `candidate_pools`
- `candidate_pool_members`
- `public_competition_results`
- `public_appeal_records`
- `military_screenings`
- `medical_board_results`
- `security_clearance_checks`

## 7. Which fields must move out of `candidates`

The following current columns should not remain long-term as generic candidate
master data:

- `knowledge_test`
- `physical_fitness_exam`
- `research_date`
- `research_result`
- `discrediting_information`
- `examination_date`
- `attitude_to_military`
- `hhk_date`
- `hhk_result`
- `useless_info`

These belong in:

- assessment records
- medical records
- security/vetting records
- decision/audit records

The `candidates` table should become a reusable identity/profile layer.

## 8. Required screens

The target UI should not be one flat candidates CRUD.

### Shared screens

1. Candidate directory
2. Vacancy / opening list
3. Application pipeline board
4. Candidate profile
5. Application detail
6. Document vault
7. Hiring analytics

### Corporate-specific screens

1. Recruiter board
2. Interview plan
3. Offer management
4. Talent pool board

### Public-specific screens

1. Competition announcement list
2. Eligibility screen
3. Exam and score board
4. Commission decision board
5. Reserve list and appeals

### Military-specific screens

1. Suitability board
2. Test and physical board
3. Medical board result workspace
4. Research / clearance workspace
5. Commission readiness screen

## 9. Recommended workflow packs

Do not keep only:

- `military`
- `civilian`

That is too broad.

Recommended packs:

- `private`
- `public`
- `military`

Each pack should define:

- visible field groups
- mandatory document checklist
- allowed stages
- scoring rules
- approval participants
- final outcome types
- default analytics widgets

## 10. To-do list

## Must-have

- [ ] Split candidate master from application pipeline.
- [ ] Add `job_requisitions` and `job_openings`.
- [ ] Add `candidate_applications`.
- [ ] Keep only the deployment-relevant workflow pack visible in UI using config/profile pack resolution.
- [ ] Add stage history model instead of only current status.
- [ ] Move military-only checks into dedicated assessment tables.
- [ ] Add candidate source tracking.
- [ ] Add application-level decision and rejection reason model.
- [ ] Add hire conversion flow from candidate to personnel.
- [ ] Replace binary `military/civilian` design with `private/public/military` pack model.

## Should-have

- [ ] Add interview scheduling and scorecards.
- [ ] Add reserve list / talent pool logic.
- [ ] Add offer and appointment approval flow.
- [ ] Add profile-based document checklist.
- [ ] Add recruiter / commission ownership on stages.
- [ ] Add audit-focused decision notes and evidence support.
- [ ] Add source analytics and funnel analytics.

## Nice-to-have

- [ ] Add SLA / aging metrics per vacancy and stage.
- [ ] Add interviewer calibration analytics.
- [ ] Add candidate communication templates.
- [ ] Add referral tracking.
- [ ] Add candidate duplicate merge tools.

## 11. Professional workflow proposal

### Shared core workflow

1. requisition is approved
2. vacancy is published
3. candidate profile enters system
4. application is created against vacancy
5. application moves through stage pipeline
6. assessments and interviews are recorded
7. decision is logged
8. final outcome is one of:
   - hire
   - reject
   - reserve / pool
   - withdrawn
9. hired record triggers personnel / onboarding flow

### Private workflow

1. recruiter screening
2. manager review
3. assessment if needed
4. interview rounds
5. offer
6. hire

### Public workflow

1. eligibility check
2. document completeness
3. exam
4. commission interview
5. ranking
6. reserve list or appointment

### Military workflow

1. suitability screening
2. test and fitness
3. medical board
4. background research
5. commission decision
6. appointment readiness

## 12. Step-by-step refactor plan

### Step 1. Stabilize the current module

- keep current module working
- keep current dual-mode non-breaking behavior
- avoid immediate destructive migration

### Step 2. Introduce the new center of gravity

- create `job_requisitions`
- create `job_openings`
- create `candidate_applications`
- connect one candidate to many applications

### Step 3. Stage-driven workflow

- replace status-only logic with stage events
- add current stage + stage history
- add responsible user per stage

### Step 4. Extract profile-specific assessments

- remove military-specific logic from generic form flow
- create assessment entities
- map workflow pack to assessment requirements

### Step 5. Build sector packs

- `private`
- `public`
- `military`

Each pack should provide:

- enabled screens
- visible navigation entries
- enabled stage template
- required document categories
- required assessment blocks
- allowed final outcomes

### Step 6. Add decision and conversion layer

- offer / appointment records
- rejection reasons
- hire conversion
- reserve / pool logic

### Step 7. Add analytics

- pipeline conversion
- source effectiveness
- time to hire
- rejection reasons
- stage aging

## 13. Recommended implementation order for this project

If this module is rebuilt in controlled phases, the best order is:

### Phase A

- requisition
- vacancy
- applications
- stage history

### Phase B

- interview and assessment center
- rejection reasons
- basic source tracking

### Phase C

- offer / appointment
- hire conversion
- reserve / pool

### Phase D

- profile packs
- public and military specializations
- advanced analytics

## 14. Final recommendation

The current module should not be abandoned. It should be treated as:

- phase-1 military candidate intake

But the future architecture should be:

- one candidate kernel
- one vacancy/application pipeline
- sector-specific workflow packs

This gives the project:

- correctness for military institutions
- real ATS capability for private companies
- audit and fairness compliance for public institutions
- cleaner architecture and easier future growth

## 15. External references

These external sources support the professional design direction:

- OPM on federal merit-based hiring and assessments:
  - [Competitive Hiring](https://www.opm.gov/policy-data-oversight/hiring-information/competitive-hiring/)
  - [Individual Assessment](https://www.opm.gov/services-for-agencies/assessment-evaluation/individual-assessment/)
- EEOC on lawful testing and selection:
  - [Employment Tests and Selection Procedures](https://www.eeoc.gov/laws/guidance/employment-tests-and-selection-procedures)
  - [Recordkeeping Requirements](https://www.eeoc.gov/employers/recordkeeping-requirements)
- ICO on recruitment data handling and retention:
  - [Keeping recruitment records](https://ico.org.uk/for-organisations/uk-gdpr-guidance-and-resources/employment/recruitment-and-selection/keeping-recruitment-records/)
