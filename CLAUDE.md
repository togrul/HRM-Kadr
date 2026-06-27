# HRM — Claude Code Project Guide

İnsan resursları (HCM/HR) sistemi. Modulyar Laravel monoliti. Bu fayl layihəyə xas qaydaları toplayır; **hər dəyişiklikdən əvvəl oxşar mövcud faylı yoxla və ona uyğunlaş** (naming, struktur, yanaşma).

## Stack (həqiqi versiyalar — `composer.json`-a güvən)
- PHP `^8.2`, Laravel `^12`
- Livewire `^4` + `livewire/blaze` `^1`
- Tailwind 3 + Alpine + Vite
- `laravel/sanctum`, `spatie/laravel-permission`, `spatie/laravel-activitylog`
- Test: **Pest 3** (`vendor/bin/pest`), Format: **Pint** (`vendor/bin/pint`)
- Laravel **Boost** MCP quraşdırılıb (aşağıya bax)

> ⚠️ `.junie/guidelines.md` faylı Boost tərəfindən yaradılıb, amma versiyalar **köhnədir** (Laravel 10 / Livewire 3 / Pest 2 yazır). Versiya məsələsində həmişə `composer.json`-a güvən, o fayla yox. Qalan ümumi Laravel qaydaları (`.junie/guidelines.md`) keçərlidir.

## Memarlıq — modulyar monolit
Bütün domen məntiqi `app/Modules/<Modul>/` altındadır (Admin, Attendance, Audit, BusinessTrips, Candidates, Compliance, EmployeeLifecycle, Leaves, Orders, Personnel, PerformanceEvaluation, Reports, Staff, Vacation, ...).

Modul daxili tipik qatlar:
```
app/Modules/<Modul>/
  Application/Services/   # domen xidmətləri (biznes məntiqi burada)
  Http/Controllers/
  Livewire/               # Livewire 4 komponentləri (UI)
  Database/Migrations/    # modulun öz migration-ları
  Routes/                 # modulun route faylları
  Policies/  Console/  Support/  Resources/{views,lang}/  Providers/
```

Qaydalar:
- **Modullar arası birbaşa oxuma qadağandır.** Architecture testləri (`tests/Unit/Architecture/*`) bunu məcbur edir — məs. `OrdersCrossModuleReadIsolationTest`, `OrdersDomainApplicationBoundaryTest`, `AttendanceLivewireReadBoundaryTest`. Modullar arası əlaqə üçün servis/contract istifadə et.
- **Biznes məntiqi `Application/Services`-də**, Livewire/Controller nazik qalsın.
- **Yeni base qovluq açma**, dependency əlavə etmə — əvvəl soruş.
- Migration həmin modulun `Database/Migrations`-ına gedir.
- İcazə üçün `spatie/permission`, audit üçün `spatie/activitylog` + modul Policy-ləri.

## Dil / tərcümələr (vacib)
- Dillər: **`az` (əsas)**, `en`, `ru`. Mətnləri hardcode etmə — tərcümə açarı işlət.
- Yeni açar **bütün dillərə** əlavə olunmalıdır. `php artisan translations:lint` və `ModuleTranslationNamespaceTest` bunu yoxlayır; modul tərcümələri modulun namespace-ində olmalıdır.
- `lang/*`, `.blade.php` və ya `app/Modules/**/*.php` faylına toxunanda **hook avtomatik `translations:lint` salır**; çatışmayan açar olsa Claude-a qaytarılır (bax `.claude/hooks/translations-lint.sh`).

## Blade / Blaze
- View-lar **blaze-safe** olmalıdır: `composer ci:blaze-safe-lint` (`--strict`) keçməlidir.
- `.blade.php` faylına toxunanda **hook avtomatik `views:blaze-safe-lint` salır**; xəta olsa Claude-a qaytarılır və düzəldilməlidir (bax `.claude/hooks/blaze-safe-lint.sh`).
- Kritik əməliyyat düymələrində ikon konvensiyası var (`CriticalActionIconsSmokeTest`).

## Performans — "query budget"
Modulların `*:query-budget` artisan əmrləri var (orders, attendance, audit, employee-lifecycle, compliance, candidates...). Read/render yollarında ağır sorğu salma — Livewire read-boundary testləri bunu bağlayır. Dəyişiklikdən sonra müvafiq budget əmrini yoxla.

## Boost MCP alətləri (varsa istifadə et)
- `search-docs` — Laravel/Livewire/Pest sənədləri üçün **ilk növbədə** bunu işlət (versiyaya uyğun nəticə verir). Sorğuya paket adı yazma.
- `tinker` — PHP/Eloquent debug; `database-query` — yalnız oxuma sorğuları.
- `list-artisan-commands` — əmr parametrlərini yoxlamaq üçün.
- Sayt Laravel Herd ilə xidmət olunur; URL üçün `get-absolute-url` işlət, serveri özün qaldırma.

## İş axını — bitirməzdən əvvəl
1. **Format:** `vendor/bin/pint <dəyişən-fayl>` (Edit/Write zamanı hook avtomatik salır — aşağıya bax).
2. **Test:** `vendor/bin/pest` və ya konkret fayl: `php artisan test tests/Feature/...`.
3. **Toxunduğun modulun CI gate-i** (lazımdırsa):
   - `composer ci:orders-template-gate`
   - `composer ci:attendance-gate`
   - `composer ci:audit-gate`
   - `composer ci:strategic-hr-gate` (employee-lifecycle / compliance / candidates)
   - `composer ci:translations` — tərcüməyə toxunanda
4. Frontend görünmürsə: `npm run dev` / `npm run build` lazım ola bilər.

## Kod konvensiyaları (xülasə)
- Control structure-larda həmişə `{}` işlət (tək sətir olsa belə).
- `__construct()`-də PHP 8 constructor property promotion; boş konstruktor yaratma.
- Bütün metod/funksiyalarda **açıq return type** və parametr type-hint.
- Şərh əvəzinə PHPDoc; kod içində şərhi yalnız çox mürəkkəb məntiqdə yaz.
- Pint preset: `laravel` + `no_unused_imports`, global namespace import (funksiya/sabit) — bax `pint.json`.
- Test tələb olunan funksionallıq üçün tinker/verification script yaratma — Pest test yaz.

## Sənədləşmə
- `docs/` zəngindir (modul plan/todo faylları). **Yeni sənəd faylı yalnız istifadəçi açıq istəsə** yarat.
