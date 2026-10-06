# Technical Debt Audit — HRM

**Tarix:** 2026-06-26 · **Stack:** PHP 8.2, Laravel 12, Livewire 4 (+ Blaze), Tailwind 3, Pest 3 · **Həcm:** ~152,892 LOC PHP, 1,445 fayl, 21 modul, 196 test faylı

---

## 1. Executive Summary

HRM bu ölçüdə bir sistem üçün **strukturca möhkəm və yaxşı qorunan** kod bazasıdır: biznes məntiqi əsasən `Application/Services`-də cəmlənib, siyahı sorğuları əksər yerlərdə eager-loading edən query servislərinə həvalə olunub, və nadir rast gəlinən bir təhlükəsizlik şəbəkəsi var — **arxitektura testləri + per-modul `query-budget` əmrləri + Livewire read-boundary testləri**. Ən pis ölçü **təhlükəsizlik (authorization)**: `Services/Users` modulunda hesab silmə/bərpa yolları açıqdır — yaddaşda qeyd olunan "User CRUD account takeover" P0-ın qalıq hissəsi hələ də canlıdır (Orders tərəfi isə artıq tam bağlanıb). İkinci böyük problem **müşahidə (observability)**: 21 modulun yalnız 8-i `Log::`/`report()` istifadə edir, kritik yazma yollarında audit izi yoxdur. Qalan borc məqsədli və idarəolunandır: bir neçə god-class/trait, write-path döngülərində N+1, 2 modulun testsizliyi, və köhnəlmiş onboarding sənədləri. **~10 sürətli qələbə** 30 dəqiqədən azdır.

---

## Update — Fixes applied 2026-06-26 (full suite 733 green)

| ID | Status | Note |
|----|--------|------|
| C1–C4 | ✅ Fixed | `DeleteUser` + `AllUsers` (forceDelete/restore/mount) now gate on `access-settings`; `findOrFail` null-guard. Tests: `UserManagementAuthorizationTest` (+4). |
| C6 | ✅ Fixed | `LeaveForm::document_path` enforces mimes + 10 MB cap (svg excluded). Tests: `LeaveFormMaxDaysNoticeTest` (+3). |
| C5 | ⚠️ Mostly false-positive + hardened | `/admin` route group already has `can:access-admin`; added defense-in-depth `Gate::authorize('access-admin')` to `AdminCrudTrait::delete()`. Tests: `AdminAccessControlTest`. |
| P1 | ✅ Fixed | `EditStaff::store()` — `destroy()` + id-diff hoisted out of loop, `findOrFail` guard. Tests: `EditStaffStoreTest`. |
| A6/A7 | 🟡 Gated + batch fixed | **Larastan** added (`composer ci:phpstan`, level 6 scoped to return types, baseline for existing). 40 return types fixed in `Services/Users` + the two reporting services; rest baselined as tracked debt. |
| FormRequest | ❎ Won't fix | Livewire component / Form-object validation is the framework idiom; "0 FormRequest" is correct, not debt. |
| T1/T2 | ✅ Fixed | `BusinessTripsAccessTest`, `VacationsAccessTest` added (both modules were untested). |
| D1/DOC1/D3 | ✅ Fixed | Real project `README.md`; `.junie` version table → 12/4/3; `composer.json` name → `hrm/hrm`. |
| D2 | ✅ Fixed | `laravel/breeze` moved to `require-dev` (no runtime usage; app boots clean on L12.51). |
| B1/B2/B3 | ✅ Fixed | Domain-level `activity()` logging on Order status transitions (semantic verb + effect direction) and on user delete/force-delete/restore. Tests: `OrderStatusTransitionLoggingTest` + user-auth activity assertions. |
| E2 | ✅ Fixed | `ModuleBoundaryIsolationTest` scans **all** modules for foreign internal imports; `Contracts\` is the only sanctioned surface. Allow-list pins remaining debt (now 3, was 8). |
| E1 | ✅ Fixed | `Contracts\` introduced for the 4 Personnel MyHr services (ApprovalRouteResolver, LearningAssignmentManager, OnboardingAssignmentManager, MyHrRequestReview); bound in provider; 5 consumer modules migrated off internal imports. |
| God-classes | ✅ Fixed | `HasPersonnelRelations` (71-method trait) → 6 cohesive traits; `NotificationCampaignDispatcher` 833→697 (NotificationPayloadFactory extracted); `PersonnelServiceBookWordExportService` 988→41 delegator (ServiceBookPageRenderer extracted) + characterization test pinning document structure. |
| DOC3 | ✅ Fixed | 8 completed-migration docs moved to `docs/archive/` (Blaze + L12/LW4 upgrade checklists). |

> Full suite **737 green**, PHPStan gate clean, boundary arch test green. Remaining open items: the other ~3 non-MyHr cross-module couplings (pinned in ALLOWED_DEBT), the long tail of return types (baselined, gated), and the `argument.type` PHPStan items on loosely-typed Eloquent relations.

---

## 2. Mental Model

HRM hərbi/uniformalı xidmət üçün insan resursları (HCM) sistemidir — modulyar Laravel monoliti. Bütün domen `app/Modules/<Modul>/` altında (Personnel, Orders, Attendance, Leaves, Vacation, Candidates, EmployeeLifecycle, Compliance, Notifications, və s.), hər modul daxili qatları ilə: `Application/Services` (biznes məntiqi), `Livewire` (UI), `Support/Traits`, `Database/Migrations`, `Routes`, `Policies`. Modullar arası birbaşa oxuma rəsmən qadağandır və `tests/Unit/Architecture/*` ilə yoxlanılır; icazələr `spatie/permission`, audit `spatie/activitylog` üzərindədir. Ən mürəkkəb alt-sistem dinamik **Orders (əmr) engine**-dir (şablon/handler/snapshot/DOCX + statuslu iş axını). Sənədlər və UI əsasən Azərbaycan dilindədir.

---

## 3. Findings Table

| ID | Category | File:Line | Severity | Effort (h) | Description | Recommendation |
|----|----------|-----------|----------|-----------|-------------|----------------|
| **C1** | Security / AuthZ | `app/Modules/Services/Livewire/Users/DeleteUser.php:30,53` | **Critical** | 1 | `deleteUser()` istifadəçini `$user->delete()` edir, hər iki `authorize('delete',$user)` çağırışı **şərhə alınıb** (dead comment). Route yalnız `web,auth` ilə qorunur → istənilən autentifikasiyalı user istənilən hesabı (admin daxil) soft-delete edə bilər. | Şərhləri açıb policy gate-i bərpa et + audit log əlavə et |
| **C2** | Security / AuthZ | `app/Modules/Services/Livewire/Users/AllUsers.php:53-58` | **Critical** | 1 | `forceDeleteData($id)` ixtiyari ID ilə istənilən useri **forceDelete** edir — authz yox, null yoxlaması yox, `#[Locked]` yox. Client-dən birbaşa çağırıla bilər, soft-delete-i bypass edir. | `authorize` + `findOrFail` + `#[Locked]` / server-side ID |
| **C3** | Security / AuthZ | `app/Modules/Services/Livewire/Users/AllUsers.php:61-70` | **High** | 1 | `restoreData($id)` ixtiyari trashed useri bərpa edir və `is_active=true` təyin edir — authz yox. Söndürülmüş/işdən çıxarılmış hesabı dirildə bilər. | authz gate əlavə et |
| **C4** | Security / AuthZ | `app/Modules/Services/Livewire/Users/AllUsers.php:72-75` | **High** | 1 | `mount()`-da `authorize()` yoxdur; bütün user idarəetmə ekranı (email, rol siyahısı) route gate olmadığından hər autentifikasiyalı userə açıqdır. | `mount()`-a `authorize('access-settings')` |
| **C5** | Security / AuthZ | `app/Modules/Admin/Livewire/*.php` (~27 komponent: `Punishments`, `LeaveTypes`, `Positions`, `Structures`, `Weapons`, `SelfServiceApprovalRoutes`, …) | **High** | 8 | ~27 Admin reference-data CRUD komponentində heç bir authz yoxdur — sistem reference data (create/update/delete) hər logged-in userə açıq. | Ortaq trait/route-group permission middleware |
| **C6** | Security / Upload | `app/Livewire/Forms/LeaveForm.php:51-64`; store: `AddLeave.php:45`, `EditLeave.php:61` | **High** | 2 | `document_path` üçün `mimes`/`max` yoxdur — ixtiyari tip və ölçüdə fayl public diskə yüklənir (stored-XSS / malware hosting / disk tükənməsi). | Personnel/Candidates qaydasını köçür (`mimes`,`max`) |
| **B1** | Observability | repo-wide (`Log::` yalnız 8 faylda) | **High** | — | 21 modulun yalnız 8-i `Log::`/`report()` istifadə edir; əksəri provider/migration. HR sistemi üçün ciddi müşahidə boşluğu. | Kritik write-path-larda structured logging |
| **B2** | Observability | `app/Services/Orders/Document/OrderStatusTransitionService.php` | **High** | 3 | Orders status keçidləri (approve/cancel/reopen/revert — geri-dönən personnel effektləri) sıfır `Log::`/`activity()`. Ləğv edilmiş əmr audit izi qoymur. | `activity()` log hər keçidə |
| **B3** | Observability | `AllUsers.php:53-70`, `DeleteUser.php:37-60` | **High** | 2 | Hesab silmə/force-delete/restore loglanmır — C1-C3 ilə birlikdə nə authz, nə audit. | `activity()` log |
| **E1** | Architecture | `app/Modules/Personnel/Livewire/MyHr/MyHrNotifications.php:5-6` | **High** | 4 | Başqa modulun **daxili** `Notifications\Support\*` siniflərinə birbaşa müraciət (contract deyil). Boundary qaydasının ən pis pozuntusu. | Contract/interface ilə vasitəçilik |
| **E2** | Architecture | `tests/Unit/Architecture/OrdersCrossModuleReadIsolationTest.php:12-14` | **High** | 4 | Boundary testi yalnız **1 faylı** string-scan edir. Modullar arası `Application/Services`/`Support` importlarına qarşı real qoruyucu yoxdur; 7+ real cross-module coupling sızır (E1, Candidates→ELC, Notifications→Personnel MyHr, Leaves/Vacation/Onboarding/Learning→Personnel MyHr). | Bütün `app/Modules/**`-ı tarayan real Pest arch testi + `Contracts` namespace |
| **A0** | Architecture | `app/Services/PersonnelServiceBookWordExportService.php:1` | **High** | 16 | 988 LOC god class, `renderPage1..16` — modul sistemindən kənarda (`app/Services`), domen məntiqi boundary-dən qaçır. | Modula köçür + render strategiyasına böl |
| **A1** | Architecture | `app/Modules/Notifications/Support/NotificationCampaignDispatcher.php:1` | **High** | 14 | 833 LOC, 28 metod: event dispatch + campaign CRUD + payload building + rendering bir sinifdə. | ≥3 sinfə böl |
| **A2** | Architecture | `app/Modules/Orders/Livewire/OrderComposer.php:1,421` | **High** | 12 | 717 LOC fat Livewire, 33 metod; `attemptIssue()` 115 sətir — biznes məntiqi UI qatında (CLAUDE.md "Livewire nazik" pozuntusu). | Servisə çıxar |
| **A3** | Architecture | `app/Models/Concerns/HasPersonnelRelations.php:1` | **High** | 8 | 71 public metod (bütün Personnel relation-ları bir trait-də, 462 LOC) — ekstremal god trait. | Məntiqi qruplara böl |
| **A4** | Architecture | `app/Modules/Attendance/Application/Services/AttendancePunchProcessingPipelineService.php:30` | **High** | 10 | `process()` **366 sətir** tək metod. | Pipeline mərhələlərinə böl |
| **H1** | Consistency | `app/Modules/Personnel/Support/Traits/*` (17 / 23 trait) | **High** | 12 | God class-lar trait arxasında gizlədilir (`ManagesPersonnelRelationRows` 516 LOC/40 metod, `PersonnelDropdownCareerOptions` 539 LOC/28 metod). Trait kompozisiyası mürəkkəbliyi azaltmır, gizlədir. | Servislərə decompose |
| **P1** | Performance | `app/Modules/Staff/Livewire/EditStaff.php:50-67` | **Med-High** | 1.5 | `foreach`-in **içində** `StaffSchedule::destroy($removedIds)` (təkrar-təkrar), `find()->update()` N+1 yazma və `find()` null olsa **fatal**. | destroy/id hesablamasını döngüdən çıxar; `findOrFail` |
| **A5** | Architecture | `app/Modules/Personnel/Application/Services/Personnel360TimelineService.php:1` | **Medium** | 10 | 698 LOC, 31 metod — timeline aggregator + tam i18n label resolver bir yerdə. | Label resolver-i ayır |
| **G1** | Consistency | repo-wide | **Medium** | 6 | 168 inline `$this->validate()`, **0 FormRequest** — validation qaydaları səpələnib. | Ortaq rules()/FormRequest |
| **A6** | Type Debt | repo-wide (~560 metod) | **Medium** | 40 | ~560 modul metodu açıq return type-sız (CLAUDE.md tələbini pozur). Ən pis: `PerformanceEvaluation/Livewire/Reports.php` (18), service qatında `PerformanceEvaluationReportingService` (11). | Tədricən + PHPStan/Pint qaydası |
| **A7** | Type Debt | repo-wide (76 hal) | **Medium** | 8 | 76 type-sız `public $x` Livewire property (client→server serialize olunur). | Tipləri əlavə et |
| **P2** | Performance | `app/Modules/PerformanceEvaluation/Application/Services/PerformanceSkillMeasurementService.php:180-188` | **Medium** | 1 | Transaction döngüsündə per-competency `first()`/`value()` sorğuları. | `whereIn` ilə preload (mövcud `keyBy` patterni tətbiq et) |
| **T1** | Test Debt | `tests/**` (BusinessTrips: 0 fayl) | **Medium** | 4 | BusinessTrips modulunun öz testi yoxdur. | Feature testləri yaz |
| **T2** | Test Debt | `tests/**` (Vacation) | **Medium** | 3 | Vacation effektiv testsiz (yalnız legacy-repair command testi var). | Request/approval flow testləri |
| **T3** | Test Debt | Compliance(4)/ELC(3)/Audit(3)/Staff(3) | **Medium** | 6 | Ən böyük komponentlər (ELC Dashboard 594, Staff 479, EditStaff P1 bug) birbaşa test edilməyib. | Davranış testləri |
| **B5** | Error Handling | `app/Modules/Personnel/Application/Services/ProfessionalPortfolioLinkHealthService.php:70` | **Medium** | 1 | `catch(Throwable)` xam `$e->getMessage()`-i UI-yə qaytarır (internal detail leak) və loglamır. | Generik mesaj + log |
| **D1** | Doc Drift | `.junie/guidelines.md:11-18` | **Medium** | 0.5 | Versiya cədvəli köhnə: Laravel 10 / Livewire 3 / Pest 2 (real: 12/4/3). | Regenerate/düzəlt |
| **DOC1** | Doc Drift | `README.md:1-40` | **Medium** | 1 | Stock Laravel README — layihəyə aid setup/modul xəritəsi yoxdur. | Layihə README-si yaz |
| **DOC2** | Doc Drift | `app/Modules/*/Application/Services/*` (178/271) | **Medium** | ongoing | Public service metodlarının 66%-i PHPDoc-suz. | Tədricən doldur |
| **F1** | Duplication | `Personnel/Support/Traits/PersonnelCrud.php` (656) + `Staff/Support/Traits/StaffCrud.php` (527) | **Medium** | 8 | Eyni row-grid CRUD mexanikası (`addRow`/`deleteRow`/`setData`) ortaq base olmadan təkrarlanır. | Ortaq base trait/servis |
| **C7** | Security | `app/Modules/UI/Livewire/Filter/Detail.php:218-268`; `PersonnelDropdownCareerOptions.php:112+` | **Low** | 3 | `DB::raw("$localeCol as label")` — sütun adı interpolasiyası (hazırda locale az/en/ru ilə məhdud, inyeksiya yox, amma kövrək). | Whitelist mapping |
| **C9** | Security | `candidate-form-header.blade.php:3`; `vacation-list.blade.php:6` | **Low** | 1 | `{!! $title !!}` raw render (hazırda static string, gələcəkdə kövrək). | `{{ }}`-ə keç və ya invariant sənədləşdir |
| **D2** | Dependency | `composer.json` | **Low** | 0.25 | `laravel/breeze` `require`-də (prod), `require-dev`-də olmalıdır. | Köçür |
| **D3** | Dependency | `composer.json` | **Low** | 0.25 | `name: "laravel/laravel"` — skeleton metadata heç vaxt fərdiləşdirilməyib. | name/description düzəlt |
| **L1** | Cleanup | `BusinessTrips.php:131`, `DocumentController.php:13`, `AllOrders.php:189,197`, `OrderComposer.php:444,485` | **Low** | 1 | Şərhə alınmış `dd()` və dead kod blokları. | Sil |
| **L2** | Cleanup | `ImportPersonnelsFromAccessToMysql.php:48,154,157` | **Low** | 0.25 | Console command-də canlı `dd()` debug çağırışları. | Sil/loglama ilə əvəzlə |
| **DOC3** | Doc Drift | `docs/` (46 fayl) | **Low** | 2 | 46 plan/todo/checklist faylı — bir çoxu tamamlanmış migration-ları təsvir edir (stale). | Tamamlananları arxivləşdir |

---

## 4. Top 5 Priorities (impact / effort)

1. **C1–C4 — User hesab silmə/bərpa authz boşluqları** (Critical/High, ~4h). Hər autentifikasiyalı user istənilən hesabı (admin daxil) silə/force-delete/restore edə bilər. Yaddaşdakı "User CRUD account takeover" P0-ın açıq qalan hissəsi. **Ən yüksək risk/əmək nisbəti.**
2. **C6 — Leaves faylı yükləmə validasiyası** (High, ~2h). Yeganə məhdudiyyətsiz public-disk upload; `mimes`/`max` köçürmək kifayətdir.
3. **E2 — Real boundary arch testi** (High, ~4h). Boundary qaydası "teatrdır" (1 fayl scan). Bütün modulları tarayan Pest testi + `Contracts` namespace 7+ real coupling-i bağlayar və regressiyanı dayandırar.
4. **B1/B2/B3 — Kritik write-path-larda structured logging** (High, ~5h). Orders status keçidləri və user mutasiyaları üçün `activity()`/log — HR sistemində audit izi vacibdir.
5. **C5 — ~27 Admin komponentinə authz** (High, ~8h). Ortaq trait və ya route-group permission middleware ilə reference-data CRUD-u qapat.

---

## 5. Quick Wins Checklist (<30 dəq hər biri)

- [ ] **C1**: `DeleteUser.php:30,53` — `$this->authorize('delete',$user)` şərhlərini aç.
- [ ] **C2**: `AllUsers.php:53` — `forceDeleteData`-ya authz + `findOrFail`.
- [ ] **C3**: `AllUsers.php:61` — `restoreData`-ya authz.
- [ ] **C4**: `AllUsers.php:72` — `mount()`-a `authorize('access-settings')`.
- [ ] **C6**: `LeaveForm.php:51-64` — `document_path`-a `mimes`/`max` qaydası.
- [ ] **L1/L2**: şərhə alınmış `dd()` və dead kod bloklarını sil (`BusinessTrips.php:131`, `DocumentController.php:13`, `ImportPersonnels...:48,154,157`, `AllOrders.php:189,197`).
- [ ] **D1**: `.junie/guidelines.md` versiya cədvəlini 12/4/3-ə düzəlt.
- [ ] **D2**: `laravel/breeze`-i `require-dev`-ə köçür.
- [ ] **D3**: `composer.json` `name`/`description` fərdiləşdir.
- [ ] **B5**: `ProfessionalPortfolioLinkHealthService.php:70` — xam exception mesajını UI-dən çıxar.

---

## 6. "Looks Bad But Is Fine" (qəsdən, borc deyil)

- **462 raw-SQL istifadəsi** — yoxlanıldı: `whereRaw(..., [$bind])` parametr-bağlı, `selectRaw`-lar isə sabit/driver-əsaslı ifadələrdir (Reports/PerformanceEvaluation). İstifadəçi inputu interpolasiya olunmur → SQL injection yoxdur (yeganə kövrək hal C7, locale sütun adı).
- **60 `{!! !!}` unescaped Blade** — əksəriyyəti static/translation string, markdown render (repo-daxili `.md`), `e()`-lənmiş highlight, və ya framework komponentləridir. İstifadəçi datası render edən yalnız 2 kövrək hal var (C9).
- **`PuantajGrid::render()`-də ağır CPU işi** — DB tərəfi düzgün batch-lənib (`loadLedgerMap`, cached `structureMap`), N+1 yoxdur; `AttendanceLivewireReadBoundaryTest` ilə örtülüb. Qəsdən dizayndır.
- **Bir skipped test** (`OrderTemplateDesignerTest.php:106`) — LibreOffice olmayan mühit üçün legitim environmental skip, borc deyil.
- **`env()` app-də sıfır** — config-cache təhlükəsiz; ən geniş yayılmış Laravel anti-pattern-i burada **təmizdir**. Güclü siqnal.
- **11 arxitektura testi + per-modul query-budget + CI gate-lər** — nadir struktur intizamı; debt deyil, qoruyucudur.

---

## 7. Open Questions (maintainer aydınlaşdırmalı)

1. **Services/Users route**-u qəsdən permission middleware-siz buraxılıb, yoxsa komponent-səviyyə authz-a (indi pozulmuş) etibar edilib? Route-group gate əlavə edək?
2. **Cross-module `Personnel\...\MyHr\*` servisləri** qeyri-rəsmi "shared kernel" olub. Bunlar rəsmi `Contracts` interfeysinə çıxarılmalıdır, yoxsa ayrıca Shared modula?
3. **`app/Services/` altındakı domen məntiqi** (PersonnelServiceBook, CvWordExport, OrderStatusTransition) — modul sisteminə köçürülməlidir, yoxsa qəsdən "ortaq infrastruktur" sayılır?
4. **PHPDoc/return-type** konvensiyası CLAUDE.md-də məcburidir amma geniş pozulur — PHPStan ilə CI-də məcbur etmək istəyirsinizmi (yoxsa konvensiya yumşaldılsın)?
5. **`docs/` 46 plan faylı** — hansılar aktiv spesifikasiyadır, hansılar arxivlik tamamlanmış işdir?
