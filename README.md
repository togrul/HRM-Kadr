# HRM — İnsan Resursları İdarəetmə Sistemi

Hərbi/uniformalı xidmət üçün insan resursları (HCM/HR) sistemi. **Modulyar Laravel monoliti** — bütün domen məntiqi `app/Modules/<Modul>/` altında, CI ilə məcbur edilən modul sərhədləri və performans büdcələri ilə.

> Layihəyə xas qaydalar üçün bax: [`CLAUDE.md`](CLAUDE.md). Versiya məsələsində həmişə `composer.json`-a güvən.

## Stack

| Sahə | Texnologiya |
|------|-------------|
| Backend | PHP `^8.2`, Laravel `^12` |
| UI | Livewire `^4` + `livewire/blaze`, Alpine.js, Tailwind 3, Vite |
| Auth / icazə | `laravel/sanctum`, `spatie/laravel-permission` |
| Audit | `spatie/laravel-activitylog` |
| Sənəd ixracı | `phpoffice/phpword`, `maatwebsite/excel` |
| Test | Pest 3 (`vendor/bin/pest`) |
| Format | Pint (`vendor/bin/pint`) |
| Dev tooling | Laravel Boost MCP, Pail, Debugbar |

## Memarlıq

Modulyar monolit. Hər modul öz qatları ilə özünü-tam saxlayır:

```
app/Modules/<Modul>/
  Application/Services/   # biznes məntiqi
  Http/Controllers/
  Livewire/               # Livewire 4 komponentləri (nazik UI qatı)
  Database/Migrations/    # modulun öz migration-ları
  Routes/  Policies/  Console/  Support/  Resources/{views,lang}/  Providers/
```

Mövcud modullar: Admin, Attendance, Audit, BusinessTrips, Candidates, Compliance, EmployeeLifecycle, LearningLibrary, Leaves, Notifications, OnboardingLibrary, Orders, PerformanceEvaluation, Personnel, Reports, Services, SidebarStructure, Staff, TrainingNeeds, UI, Vacation.

**Qaydalar (CI ilə yoxlanılır):**
- Modullar arası birbaşa oxuma qadağandır — əlaqə servis/contract üzərindən (`tests/Unit/Architecture/*`).
- Biznes məntiqi `Application/Services`-də; Livewire/Controller nazik qalır.
- Read/render yollarında "query budget" pozulmamalıdır (`*:query-budget` artisan əmrləri).
- View-lar blaze-safe olmalıdır (`composer ci:blaze-safe-lint`).
- Ən mürəkkəb alt-sistem: dinamik **Orders (əmr) engine** — şablon/handler/snapshot/DOCX + statuslu iş axını.

## Dillər

`az` (əsas), `en`, `ru`. Mətnlər hardcode edilmir — tərcümə açarı işlədilir; yeni açar bütün dillərə əlavə olunmalıdır (`php artisan translations:lint`, `ModuleTranslationNamespaceTest`).

## Qurulum

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev          # və ya: npm run build
```

Sayt lokal olaraq Laravel Herd ilə xidmət olunur.

## İş axını — dəyişiklikdən əvvəl/sonra

```bash
vendor/bin/pint <dəyişən-fayl>        # format (Edit/Write hook avtomatik salır)
vendor/bin/pest                        # bütün testlər
php artisan test tests/Feature/...     # konkret fayl
```

Toxunduğun modulun CI gate-ini yoxla (lazımdırsa):

```bash
composer ci:orders-template-gate
composer ci:attendance-gate
composer ci:audit-gate
composer ci:strategic-hr-gate          # employee-lifecycle / compliance / candidates
composer ci:translations               # tərcüməyə toxunanda
composer ci:blaze-safe-lint            # blade-ə toxunanda
```

## Sənədləşmə

`docs/` qovluğunda modul plan/todo faylları var. Texniki borc auditi: [`TECH_DEBT_AUDIT.md`](TECH_DEBT_AUDIT.md).
