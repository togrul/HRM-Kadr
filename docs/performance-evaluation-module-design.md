# Performance Evaluation Module Design

This document defines the target design for the `Performance Evaluation` module.

## 1. Module goal

The module exists to measure employee performance, behavior, and development potential objectively and transparently for managers and HR.

## 2. Submodules

1. Evaluation Form
2. Electronic Tests and Skill Measurement

## 3. Business users

- HR Admin
- HR Specialist
- Manager
- Employee
- Academic evaluator
- Auditor

## 4. Evaluation cycle

HR defines an annual or academic-year evaluation period.

### Required process

1. HR opens evaluation period
2. System creates evaluation forms for all employees automatically
3. Inputs are collected from:
   - manager
   - employee self-assessment
   - student survey for academic staff
   - research/teaching result sources
   - HR
4. final score and category are calculated automatically

## 5. Evaluation forms

### Core properties

- standard template-based form
- criteria grouped into sections
- score/percentage for each criterion
- comment per criterion
- proof document per score

### Output

- total score
- weighted result
- category
- evaluator notes
- evidence pack

## 6. Result categories

The system must produce three categories:

- high performers
  - eligible for bonus/reward
- medium performers
  - stable performance
- weak performers
  - development plan required

## 7. Electronic tests and skill measurement

HR uploads question banks.

### Supported question types

- multiple choice
- open answer
- practical case study
- behavioral/scenario-based questions

### Test delivery rules

- employee joins from personal cabinet
- duration, question count, and pass score are predefined
- employee sees this as a notification on entry
- retry limits exist
- time control exists

### Scoring rules

- objective questions are auto-graded
- open questions can be graded manually by HR/manager
- score, percentage, and status are shown
- individual and collective analytics are generated
- skill profile is produced
- skill levels are classified as:
  - beginner
  - intermediate
  - advanced

### Derived outputs

- strong areas
- weak areas
- skill profile
- automatic training need creation for weak areas

## 8. Integration with Training and Development

Weak results from either submodule must automatically create development needs.

### Required destinations

- Training and Development -> Training Needs
- Employee profile -> Individual Development Plan

### Need types

- training
- mentoring
- coaching

## 9. Evaluation criteria model

## A. Pedagogical performance (60%)

### 1. Course organization (20%)

- syllabus upload and evaluation
- teaching materials aligned to plan
- literature list completeness and recency
- source: KOICA, entered by evaluator

### 2. Teaching process (30%)

- methodology
- structure
- time allocation
- interactivity
- source: open class, open door, camera observation
- system should calculate average score across three sources

### 3. Student surveys (10%)

- source: KOICA integration
- score should flow into system automatically

## B. Creativity, development, and labor discipline (40%)

- social project participation (10%)
- training and seminar delivery (10%)
- self-development (10%)
  - local trainings/certificates
  - international certificates
- mentoring activity (10%)
- ethical violations
  - automatic penalty deduction: `-10%`

## 10. Professional development information

The module must also surface long-term employee growth data:

- all trainings completed during employment
- training name, duration, result, proof document
- individual development plan
- quantitative and qualitative indicators for academic staff
- publication count
- publications in international ranked journals
- conference and seminar participation
- project participation metrics

## 11. Domain model proposal

### Core entities

- `performance_cycles`
- `performance_form_templates`
- `performance_forms`
- `performance_form_sections`
- `performance_form_items`
- `performance_form_scores`
- `performance_form_evidence`
- `performance_result_snapshots`
- `test_banks`
- `test_questions`
- `test_options`
- `test_sessions`
- `test_attempts`
- `test_attempt_answers`
- `skill_profiles`

### Integration entities

- `performance_training_need_links`
- `performance_source_integrations`

## 12. Roles and permissions

- HR Admin
  - cycle setup, template design, reporting, overrides
- HR Specialist
  - manage forms, tests, review open answers
- Manager
  - team evaluations, open-answer review, result review
- Employee
  - self-assessment, tests, own results
- Auditor
  - read-only visibility

Suggested permissions:

- `manage-performance-cycles`
- `manage-performance-templates`
- `evaluate-performance`
- `manage-skill-tests`
- `export-performance-reports`

## 13. Main screens

1. Performance cycle dashboard
2. Evaluation form management
3. Employee evaluation workspace
4. Self-assessment workspace
5. Test bank management
6. Test runner
7. Open-answer review board
8. Result analytics dashboard
9. Employee skill profile

## 14. Calculations

System must support:

- weighted criteria calculation
- average calculation across multiple sources
- automatic penalty application
- final category mapping
- pass/fail calculation for tests
- skill-level classification from test results

## 15. Integrations

- Training and Development
  - weak results create training needs
- Employee personal profile
  - evaluation results and development plan summary
- KOICA
  - student surveys and course organization source data
- Notifications
  - cycle start, deadlines, test invitations, results

## 16. Acceptance criteria

- HR can open a cycle and auto-create forms
- all evaluation actors can submit their parts
- final result is calculated automatically
- tests support objective and manual grading
- skill profile is generated
- weak areas create training needs automatically
- reports show individual and collective performance trends
