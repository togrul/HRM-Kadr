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

## 17. KPI siyasətləri: vaxtından əvvəl bağlanan kartın bonusu və faktların təkrar idxalı

### 17.1 İşdən çıxan əməkdaşın bonusu (`early_close_policy`)

**Əvvəlki davranış.** Gündəlik `ScorecardLifecycleService::syncPersonnel` işdən çıxma tarixi
keçmiş əməkdaşın kartını `closeEarly(..., 'terminated', son iş günü)` ilə bağlayır: kart
dondurulur, `valid_to` son iş gününə, `prorata_factor` isə `işlənmiş günlər ÷ dövr günləri`
(məs. 90 günlük rübün 45 günü → 0.5) olur. Bağlı (`closed`) kart bonusa daxildir və
`BonusService` həmin əmsalı vurur — yəni bonus artıq **pro-rata** idi, amma bu, şirkət
tərəfindən seçilə bilmirdi. Vəzifə dəyişikliyində köhnə kart eyni qaydada bağlanır və dövrün
qalanı üçün yeni kart açılır.

**Qəbul edilən qayda.** Bonus dövr üzrə hesablanır; dövr bitmədən **işdən çıxma** ilə bağlanan
kart üçün dövrün bonus qaydasında siyasət seçilir:

| Dəyər | Mənası |
|---|---|
| `prorata` (standart) | işlənmiş günlərə mütənasib: kartın öz `prorata_factor`-u |
| `full` | işçi dövrün sonuna qədər işləmiş kimi: kartın başlanğıcından dövr sonuna |
| `none` | bonus verilmir |

- Saxlanma yeri: `performance_bonus_rules.early_close_policy` (dövr üzrə «bonus qaydası» —
  şirkətin mükafatlandırma siyasəti artıq burada saxlanır, məs. `pay_in_probation`). Ekranda:
  KPI → Bonus → «İşdən çıxan əməkdaşın bonusu».
- Yeni qaydanın standartı: `config/performance_evaluation.php` → `kpi.early_close_bonus`
  (env `PERFORMANCE_KPI_EARLY_CLOSE_BONUS`, standart `prorata`). Mövcud qaydalar miqrasiya ilə
  `prorata` alır — əvvəlki nəticələr dəyişmir.
- **Vəzifə dəyişikliyi** ilə bağlanan kart siyasətdən asılı olmayaraq həmişə pro-rata qalır:
  dövrün qalanını yeni kart əhatə edir, `full` eyni günləri iki dəfə ödəyərdi.
- Sınaq müddəti (`pay_in_probation`), FTE, fond və məhdudiyyət faizi əvvəlki kimi tətbiq olunur.
  Kod: `BonusService::bonusProrata()`.

**Əsaslandırma.** Azərbaycan Respublikasının Əmək Məcəlləsi KPI bonusunu ayrıca tənzimləmir;
əmək haqqı və mükafatlandırma (ƏM-in əmək haqqı fəsli) əmək müqaviləsi, kollektiv müqavilə və
ya işəgötürənin daxili əsasnaməsi ilə müəyyən edilir. Ona görə qanunla sabitlənmiş tək «düzgün»
qayda yoxdur və seçim şirkətin siyasəti olmalıdır. Standart olaraq `prorata` seçilib, çünki:
(1) bonus dövrün nəticəsinə görə ödənilir və işçi dövrün yalnız bir hissəsində iştirak edib;
(2) işə dövrün ortasında qəbul olunan əməkdaşa da eyni (mütənasib) qayda tətbiq olunur —
simmetrikdir; (3) işlənmiş müddət üçün qazanılmış mükafatı əlindən almır (`none`-dan
ədalətlidir) və işlənməmiş günlər üçün ödəmir (`full`-dan ehtiyatlıdır). ⚠️ Konkret maddə
nömrələri bu sənəddə təsdiqlənməyib; müqavilə/əsasnamədə başqa qayda varsa, dövrün bonus
qaydasında uyğun dəyər seçilməlidir.

### 17.2 Faktların təkrar idxalı (Excel/CSV)

**Əvvəlki davranış.** `KpiActualsImportService::import` hər sətir üçün yeni
`performance_kpi_actuals` qeydi (`source = import`) yaradırdı. Kartın nəticəsi göstəricinin
aqreqasiyasına görə bütün təsdiqlənmiş faktlardan hesablandığı üçün: `last` — sonuncu götürülür
(praktikada əvəz edirdi); `sum` — eyni fayl iki dəfə yüklənəndə nəticə **ikiqat** olurdu; `avg`,
`min`, `max` — köhnə idxal dəyərləri nəticəni təhrif edirdi.

**Qəbul edilən qayda.** İdxal mənbə sistemdən **snapshot**-dur:

- eyni kart bəndi (kart + göstərici; kart dövrə bağlıdır) və eyni mənbə (`import`) üçün təkrar
  idxal əvvəlki idxal faktını **ƏVƏZ edir**, toplamır — eyni faylı neçə dəfə yükləsəniz də
  nəticə eynidir (idempotent);
- əvvəlki toplayan davranışdan qalmış eyni mənbəli dublikatlar ilk təkrar idxalda bir qeydə
  endirilir;
- əl ilə daxil edilən (`manual`) və sistem/inteqrasiya (`hrm`, `integration`) faktları ayrı
  mənbədir və **toxunulmur** (sistem metrikləri də artıq eyni prinsiplə — mənbə üzrə bir
  qeyd — `updateOrCreate` edilir);
- köhnə dəyər **audit izində** qalır: `PerformanceKpiActual` activity log-a yazılır
  (`performance_scorecard` log adı) — yeniləmə `old`/`attributes` dəyərləri ilə, silinən
  dublikat isə silinmə qeydi ilə.

Kod: `ScorecardService::recordActual(..., replace: true)`, `KpiActualsImportService::import`.
Testlər: `tests/Feature/PerformanceEvaluation/KpiEarlyCloseAndReimportTest.php`.
