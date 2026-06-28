# Maaş & Kompensasiya — Tikinti Sənədi (Payroll × HR)

> Status: TƏSDIQLƏNMİŞ PLAN — bu sənəd əsasında tikintiyə başlanır.
> Qərarlar (istifadəçi təsdiqi): **çox-rejimli engine** · **2 modul (Compensation + Payroll)** · **3 inteqrasiya (bank + GL + dövlət hesabatları)** · **Faza 1 = Compensation**.

---

## 1. HR ↔ Maliyyə kəsişməsi

| Ortaq sahə | HR tərəfi | Maliyyə tərəfi | Mövcud körpü (kodda) |
|---|---|---|---|
| Təşkilati struktur | İerarxiya, ştat | Xərc mərkəzi, büdcə | `structures` (+`coefficient`, `parent_id`, `level`) |
| Vəzifə / Rütbə | Karyera, ştat | Maaş qrupu / şkala | `positions`, `ranks`, `rank_categories` |
| Davamiyyət | Qrafik, gecikmə | Ödəniş əsası, overtime | `attendance_daily_ledgers`, `attendance_monthly_summaries` |
| Məzuniyyət / İcazə | Hüquq, balans | Ödənişli/ödənişsiz | `vacations`, `personnel_vacations`, `leaves`, `leave_types.attendance_code` |
| Ezamiyyət | Səfər qeydi | Per-diem | `personnel_business_trips` |
| Əmrlər (lifecycle) | İşə qəbul/köçürmə/xitam | Maaş başlanğıc/dayanma | Orders effects: hire/transfer/termination/vacation |
| Mükafat / İntizam | Tanınma / tənbeh | Bonus / tutulma | `personnel_awards`, `personnel_punishments` |
| Müqavilə | Şərtlər, müddət | Maaş şərtləri | `personnel_contracts` |
| Ailə / Asılılar | Kinship | Vergi güzəşti | `personnel_kinships` (+birthdate) |
| Əlillik / Hərbi | Status | Güzəşt / əlavə | `disability_id`, `personnel_military_services` |
| İdentifikasiya | Kadr | Vergi ID / bank | `pin` (FİN) — **bank/IBAN yoxdur** |
| Təlim / Recruitment | İnkişaf / offer | Büdcə / təklif maaşı | `training_*budget`, `candidate_offers.salary_amount`+`currency` |

**Tezis:** payroll ayrı ada deyil — yuxarıdakı modulların çıxış nöqtəsidir.
`Maaş = (struktur+rütbə+vəzifə → baza) + əlavələr − (davamiyyət/məzuniyyət proporsiyası) − (vergi/sığorta/tutulmalar)`.

---

## 2. Mövcud aktivlər (payroll bunları OXUYACAQ, yenidən qurmuruq)

- `structures.coefficient` → struktur maaş əmsalı
- `personnel_labor_activities.coefficient` + `is_special_service` → staj / xüsusi xidmət əmsalı
- `rank_categories` → məzuniyyət/müqavilə siyasəti
- `attendance_daily_ledgers` / `*_monthly_summaries` → worked/overtime/absence dəqiqələr
- `leaves`+`leave_types`, `vacations` → ödənişli/ödənişsiz gün
- `personnel_business_trips` → per-diem gün sayı
- Orders effects → payroll hadisələri
- `personnel_kinships` (+birthdate) → asılı şəxslər
- `personnels`: `pin`, `join_work_date`, `leave_work_date`, `disability_id`, `position_id`, `structure_id`, `work_norm_id`
- `candidate_offers.salary_amount`+`currency='AZN'` → onboarding ilkin maaş

## 3. Çatışmayan "pul" qatı

baza maaş/şkala · əlavə kataloqu · tutulma kataloqu · vergi/sığorta dərəcələri · bank/IBAN · maaş tarixçəsi · per-diem/overtime dərəcələri · payslip+registr+hesabatlar · bank/GL/dövlət ixracı.

---

## 4. Arxitektura — 2 modul

Hər ikisi mövcud konvensiyaya uyğun: `ModuleState::enabled`, `RegistersLivewireAliases`, tabbed workspace, styled confirm-modal, `notify` toast, premium düymələr, query-budget + render-benchmark + translation-lint, cross-module read-repo (Orders nümunəsi).

**A — `Compensation` (HR sahibliyində):** "kim, nə vaxtdan, nə qədər almalıdır" master datası.
**B — `Payroll` (Maliyyə sahibliyində):** "hər dövr hesabla → təsdiqlə → kilidlə → ödə".

Qoşulma: `config/modules.php` catalog · `config/profiles.php` features (`compensation`, `payroll`) · `config/menus.php` · `config/hr_policies.php`.

### Hesablama mühərriki (Payroll, Faza 2 — qayda-əsaslı)
```
1) Earnings   = base + allowances (% / sabit; struktur+staj əmsalı)
2) Proration  = attendance/leaves (ödənişsiz günlər çıxılır)
3) GROSS      = Σ earnings (taxable/affects_social bayraqları ilə baza ayrılır)
4) Statutory  − gəlir vergisi(bracket) − DSMF(ee) − işsizlik(ee) − icbari tibbi(ee)
5) Voluntary  − həmkarlar ittifaqı − kredit/avans − intizam tutulması
6) NET        = GROSS − (4) − (5);   Employer = DSMF(er)+işsizlik(er)+tibbi(er)
```
Bütün dərəcələr DB-də (`statutory_rates`, effective-dated, **regime_id** ilə) — kodda hardcode YOX. Payslip kilidlənəndə giriş datası JSON snapshot-da dondurulur (Orders `template_snapshot` pattern-i).

### AZ statutory komponentləri (konfiqurasiyalı, dərəcələr mühasibat/qanunla təsdiqlənir)
gəlir vergisi (progressiv) · DSMF (ee/er) · işsizlikdən sığorta · icbari tibbi sığorta · həmkarlar ittifaqı.
⚠️ Hərbi/uniformlu xidmət xüsusi rejimdir (xüsusi pensiya, fərqli güzəştlər) → buna görə engine qayda-dəstidir, tək formula deyil.

### Mərhələli plan
| Faza | Məzmun |
|---|---|
| **1. Compensation əsası** | şkalalar, komponent kataloqu, işçi maaşı (effective-dated)+bank, maskalama, audit |
| **2. Payroll engine MVP** | periods/runs, calculator (gross→net), payslip+lines, run wizard, kilid |
| **3. Statutory + proration** | statutory_rates, vergi/sığorta, attendance/leave proporsiyası, retro |
| **4. İxrac & inteqrasiya** | bank ödəniş faylı, GL export, dövlət hesabatları, Orders effekt körpüsü, self-service payslip |
| **5. Qabaqcıl** | kredit/avans, off-cycle, çox-valyuta, büdcə/forecast |

---

# FAZA 1 — `Compensation` Modulu (TİKİNTİ SPESİFİKASİYASI)

## F1.0 Əhatə
**Daxil:** şkalalar/pillələr, əlavə/tutulma kataloqu, işçi maaşı (effective-dated + tarixçə), bank rekvizitləri, çox-rejim etiketi, maaş maskalama, audit, premium CRUD UI, modullararası read-contract.
**Xaric (Faza 2+):** faktiki hesablama, vergi/sığorta dərəcələri, payslip, ixrac. Amma data modeli 3 inteqrasiyanı qabaqcadan tutur (§F1.7).

## F1.1 Migrations
`app/Modules/Compensation/Database/Migrations/`. Məbləğlər `decimal(12,2)`, valyuta `char(3) default 'AZN'`, qısa FK adları, `if (! Schema::hasTable(...))` guard.

| # | Cədvəl | Sütunlar |
|---|---|---|
| 1 | `compensation_regimes` | id, code(`military`/`state`/`private`), name, is_active, sort |
| 2 | `pay_scales` | id, name, regime_id→regimes, currency, effective_from(date), effective_to(date,null), is_active, description(null) |
| 3 | `pay_grades` | id, pay_scale_id→cascade, code, name, base_amount, rank_category_id(null), position_id(null), sort |
| 4 | `compensation_components` | id, code(unik), name, type(`earning`/`deduction`), calc_type(`fixed`/`percent`/`formula`/`per_diem`/`rate`), taxable(bool), affects_social(bool), is_statutory(bool), gl_code(null), sort, is_active |
| 5 | `employee_compensations` | id, tabel_no→personnels, regime_id, pay_grade_id(null), base_amount, currency, effective_from, effective_to(null), status(`draft`/`active`/`ended`), order_no(null), note(null) |
| 6 | `employee_compensation_lines` | id, employee_compensation_id→cascade, component_id→components, amount(null), percent(null), note(null) |
| 7 | `employee_bank_accounts` | id, tabel_no→personnels, iban, bank_name(null), account_no(null), is_primary(bool), is_active(bool) |

İndeks/unik: `compensation_components.code` unik; index `employee_compensations(tabel_no, effective_from)`; index `employee_bank_accounts(tabel_no, is_primary)`. "Bir işçidə eyni anda 1 aktiv kompensasiya" servis səviyyəsində.

Seed migration: regimes (military/state/private) + başlanğıc komponent kataloqu (`base`, `rank_supplement`, `seniority`, `hazard`, `secrecy`, `language`, `income_tax`, `dsmf_ee`, `union` — flag-larla) + permissions (§F1.6).

## F1.2 Modellər (`app/Models/`)
`HasFactory` + `LogsActivity` (Spatie), `decimal:2` cast.
- `CompensationRegime` (hasMany payScales, employeeCompensations)
- `PayScale` (belongsTo regime; hasMany grades)
- `PayGrade` (belongsTo payScale, rankCategory?, position?)
- `CompensationComponent` (LogsActivity; bool cast-lar)
- `EmployeeCompensation` (belongsTo personnel(tabel_no), regime, payGrade?; hasMany lines; **LogsActivity** = maaş tarixçəsi; `maskedBaseAmount()` accessor → icazəsizə `•••`)
- `EmployeeCompensationLine` (belongsTo compensation, component)
- `EmployeeBankAccount` (belongsTo personnel; LogsActivity)

## F1.3 Servislər (`Application/Services/`)
- `CompensationService` — assignCompensation (yeni effective yazı, köhnəni `ended`+`effective_to`), updateLines, endCompensation, currentFor(tabel_no, date), historyFor(tabel_no)
- `SalaryScaleService` — scale/grade CRUD, tarixə görə effektiv şkala, grade→base lookup
- `AllowanceResolver` — effektiv komponent siyahısı (sabit+faiz) + struktur/staj əmsalı köməkçisi (Payroll Faza 2 istifadə edir)
- `EffectiveDating` (util) — overlapping dövr idarəsi

## F1.4 Read-contract (Payroll Faza 2 üçün)
`Domain/Contracts/CompensationReadRepository` + `Infrastructure/Persistence/Eloquent/EloquentCompensationReadRepository` (bind `register()`-də):
`currentCompensation(tabel_no, date)` · `componentsFor(tabel_no, date)` · `primaryBankAccount(tabel_no)` · `componentCatalog()`.

## F1.5 Livewire & UI
`Compensation/Livewire/Dashboard` (tabbed) + tablar:
1. **Şkalalar** (`scales`) — pay_scales/grades CRUD
2. **Kataloq** (`components`) — komponent CRUD (taxable/affects_social toggle)
3. **İşçi maaşları** (`assignments`) — PersonnelPicker → maaş+sətirlər, maskalama
4. **Bank** (`bank`) — IBAN/rekvizit
5. **Tarixçə** (`history`) — işçi maaş audit-i

Davranış: bu sessiyada qurulan **premium kataloq pattern-i** (search + pagination + edit/delete), styled **confirm-modal** (native alert YOX), **notify** toast, premium Apple-stil ikon düymələr, `select-dropdown direction="auto"`. Maaş icazəsizə `•••`.

## F1.6 Konfiqurasiya & icazələr
- `config/modules.php` → `catalog['compensation']` (provider/enabled/migrations)
- `config/profiles.php` → feature `compensation` (default yalnız icazəli profil)
- `config/menus.php` → "Kompensasiya" menyusu (icazə-qorunan)
- `config/hr_policies.php` → `compensation.tabs` + permission flag
- İcazələr (seed): `show-compensation`, `manage-compensation`, `review-compensation`, `export-compensation`, + həssas **`view-compensation-amounts`** (maskalama)
- ServiceProvider: `ModuleState::enabled('compensation')` guard, route/view/lang/Livewire alias, read-repo bind, QueryBudget+RenderBenchmark command

## F1.7 Çox-rejim + 3 inteqrasiyaya hazırlıq
- Çox-rejim: `compensation_regimes` + `pay_scales.regime_id` + `employee_compensations.regime_id`; komponent flag-ları (`taxable`/`affects_social`/`is_statutory`) → Payroll engine rejimə görə tətbiq edir.
- Bank faylı: `employee_bank_accounts` (IBAN) indi qurulur.
- GL: `compensation_components.gl_code` indi qurulur (komponent→baş kitab hesabı).
- Dövlət hesabatları: flag-lar + `regime_id` + mövcud `pin` → DSMF/vergi klassifikasiyası indidən hazır.

## F1.8 Təhlükəsizlik & audit
Maaş = həssas PII → `view-compensation-amounts` ayrı icazə + UI maskalama. Bütün dəyişikliklər Spatie activitylog. Effective-dated → keçmiş maaş üzərinə yazılmır.

## F1.9 Testlər (CI-uyğun)
Feature: scale/grade/component CRUD; maaş təyini (effective-dating — yeni təyinat köhnəni `ended` edir); maskalama (icazəsiz `•••`); bank primary unikallığı; read-repo cari maaşı qaytarır.
Arxitektura: Livewire başqa modulun modelini birbaşa sorğulamır; translation-catalog lint; query-budget + render-benchmark.

## F1.10 Deliverables
7 migration (+seed) · 7 model · 4 servis · 1 read-contract+impl · 1 Dashboard + 5 tab Livewire + blade · az/en lang · 4 config faylı · permissions seed · QueryBudget+RenderBenchmark · ~10 test.

## F1.11 Default mikro-qərarlar (etiraz olmasa)
- Maaş "aylıq brüt" (saatlıq yox); proporsiya Faza 2-də davamiyyətdən.
- Bir işçidə eyni anda 1 aktiv kompensasiya; dəyişiklik = yeni effective yazı.
- `gl_code` indi sərbəst mətn (Faza 4-də maliyyə planına bağlanır).
