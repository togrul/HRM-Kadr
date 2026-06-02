# HRM Next Work Priority Plan

Bu fayl hazırkı mərhələdə görüləcək işləri prioritet və icra sırası ilə saxlayır. Məqsəd işləri hissə-hissə, yoxlanıla bilən mərhələlərlə aparmaqdır.

## Prioritet Şkalası

- `P0`: Kritik stabilik, təhlükəsizlik və production riski.
- `P1`: Əsas HR axınlarına və çox istifadə olunan ekranlara ciddi təsir edən iş.
- `P2`: Arxitektura, UX/UI vahidliyi və uzunmüddətli maintainability işi.
- `P3`: Optimallaşdırma, polish və əlavə rahatlıq.

## P0 - Production Və Deploy Sabitliyi

### 1. CSS build/deploy axınını bağlamaq

Status: `completed-local`

Problem:
- Serverdə köhnə asset qalması əvvəllər checkbox styling kimi problemlər yaratdı.
- Coolify deploy zamanı düzgün commit getsə də container/cache/build sinxronluğu real yoxlanmalıdır.

İcra:
- `public/build/manifest.json` və generated CSS asset-in deploy-da doğru gəldiyini yoxlamaq.
- Post-deploy command-larda `optimize:clear`, `view:cache`, asset manifest check qaydasını sabitləşdirmək.
- Build asset yoxlamasını CI/CD-də fail-fast etmək.

Acceptance:
- `npm run build` keçdi və yeni asset hash yarandı: `public/build/assets/app-2d772e83.css`.
- `php artisan assets:manifest-check --json` keçdi, manifestdəki bütün asset-lər mövcuddur.
- CI/CD üçün fail-fast guard command mövcuddur.
- Production deploy-dan sonra eyni command serverdə bir dəfə təsdiqlənməlidir.

### 2. Production debug təhlükəsizliyi

Status: `pending-production-confirmation`

İcra:
- Production/staging env-lərdə `APP_DEBUG=false`.
- Debugbar production-da bağlı olmalıdır.
- Deploy sonrası config cache real env-i oxumalıdır.

Acceptance:
- Production URL-də debugbar görünmür.
- `config('app.debug') === false` production üçün təsdiqlənir.
- Lokal kod/config səviyyəsində guard-lar var, amma real production env dəyəri yalnız serverdə təsdiqlənə bilər.

## P1 - Əsas HR Axınları

### 3. `all-personnels` sidebar refactor

Status: `completed-local`

Problem:
- Əsas əməkdaş ekranında sidebar davranışı mərkəzi modal/sidebar sistemi ilə eyni deyil.
- Yeni `x-ui.side-panel` artıq Audit və Employee Lifecycle üçün uğurlu işləyir.

İcra:
- Mövcud `all-personnels` sidebar shell hissəsini ayırmaq.
- Content və Livewire state-i mümkün qədər toxunmadan saxlamaq.
- Sidebar shell-i `x-ui.side-panel` ilə əvəz etmək.
- Open/close animasiya, backdrop, `Esc`, focus trap, body scroll lock eyni standartda işləməlidir.

Risk:
- Nested Livewire state, validation, filter/pagination və edit flow regressiya yarada bilər.

Acceptance:
- Profil açılışı işləyir.
- Edit/save/delete flow pozulmur.
- Validation error-lar panel içində qalır və düzgün görünür.
- Filter/pagination sidebar açılıb-bağlanandan sonra dəyişmir.
- Desktop browser QA-da `Yeni əməkdaş` side modal açılıb-bağlanması yoxlandı.
- Daha geniş add/edit/delete browser QA ayrıca mərhələdə saxlanılır.

### 4. Candidate ATS -> Personnel conversion E2E QA

Status: `completed-local`

Problem:
- Struktur qurulub, amma real data ilə son təsdiq lazımdır.

İcra:
- Candidate approval axınını yoxlamaq.
- Hire/conversion action yoxlamaq.
- Duplicate prevention yoxlamaq.
- Personnel data mapping yoxlamaq.
- Candidate application status və personnel link-in doğru yazıldığını təsdiqləmək.

Acceptance:
- Namizəd təsdiqlənib əməkdaşa çevrilir.
- Eyni namizəd ikinci dəfə duplicate personnel yaratmır.
- Əsas personal məlumatlar, struktur/vəzifə mapping və statuslar doğru görünür.
- `CandidateAtsCompletionServiceTest` və `CandidateApplicationStageServiceTest` keçdi.
- `php artisan candidates:ats-query-budget` keçdi.

### 5. Employee Lifecycle real process QA

Status: `completed-local`

İcra:
- Onboarding process yaratmaq.
- Probation review yaratmaq və tamamlamaq.
- Daxili yerdəyişmə process-i yaratmaq.
- Offboarding case yaratmaq və checklist tamamlamaq.
- Validation mesajlarının tam lokalizasiya olunduğunu yoxlamaq.

Acceptance:
- Hər flow browser-də real form submit ilə işləyir.
- Toast/alert standart dispatch sistemi ilə görünür.
- Query və UI davranışı stabil qalır.
- Read service, query budget və console testləri keçdi.
- Real browser-də bütün create/update case-lər üçün əlavə staging QA hələ faydalıdır.

### 6. Compliance reminder scheduler yoxlaması

Status: `completed-local-pending-production-cron`

İcra:
- Scheduler command production/staging cron-da işləyir.
- Server log və ya command output ilə təsdiqlənir.
- Reminder duplicate göndərmir.

Acceptance:
- Cron faktiki command çalışdırır.
- Eyni sənəd üçün təkrar/yanlış reminder yaranmır.
- `php artisan compliance:document-reminders --json` lokal keçdi.
- Production cron faktiki işləməsi server scheduler log-u ilə təsdiqlənməlidir.

## P1 - Lokalizasiya Və Audit Dəqiqliyi

### 7. Audit log English event/description lokalizasiyası

Status: `completed-local`

Problem nümunələri:
- `Overtime Request.approved`
- `Attendance overtime request approved.`

İcra:
- Audit event label map genişləndirmək.
- Description translation map əlavə etmək.
- Model/subject labels oxunaqlı HR dilində qalmalıdır.

Acceptance:
- Audit grid və detail paneldə qarışıq English/AZ mətn qalmır.
- Yeni event gələndə fallback texniki olsa belə aydın və idarə olunan olur.
- Overtime və attendance event/description map-ləri əlavə edildi.
- Audit dashboard localization testi keçdi.

## P2 - Modulların Tamamlanması

### 8. Employee Lifecycle plan template CRUD UI

Status: `completed-local`

Hazırda:
- Plan template list və edit side panel mövcuddur.
- Task editor istiqaməti başlanıb.

Qalan:
- Create flow tam cilalanmalıdır.
- Detail görünüşü task-larla rahat oxunmalıdır.
- Task add/edit/delete UX tamamlanmalıdır.
- Template istifadə olunubsa delete əvəzinə archive/deactivate qaydası tam tətbiq olunmalıdır.

Acceptance:
- Template yaratmaq, redaktə etmək, task əlavə etmək, task silmək işləyir.
- Used template delete edilmir, deactivate/archive olur.
- Bütün validation mesajları lokalizə və standart error component ilə görünür.
- Template list/detail/edit task panel axını mərkəzi side panel davranışı ilə işləyir.
- Validation error component kompakt vahid stilə salınıb.

### 9. Yeni modulların role/permission/menu staging testi

Status: `completed-local-pending-staging-role-check`

Modullar:
- Audit jurnalı
- Sənəd uyğunluğu
- Əməkdaş həyat dövrü
- Candidate ATS hissələri

Acceptance:
- Permission veriləndə menyuda görünür.
- Permission alınanda route və menyu bağlanır.
- Admin və non-admin rolları ayrı test edilir.
- Navigation permission feature testi keçdi.
- Real staging role-ları ilə manual təsdiq hələ lazımdır.

## P2 - UI/Architecture Vahidliyi

### 10. Detail/edit sidebar-ları mərhələli mərkəzləşdirmək

Status: `in_progress`

İcra sırası:
1. `all-personnels`
2. Candidate application detail
3. Compliance document detail
4. My HR review detail
5. Digər modal/drawer istifadə edən ekranlar

Acceptance:
- Yeni detail/edit panel-lər `x-ui.side-panel` və ya onun variantları ilə açılır.
- Hər modul öz custom overlay/animation kodunu yazmır.
- `x-side-modal` artıq mərkəzi teleport/focus-trap/animation shell ilə işləyir.
- Audit detail və Employee Lifecycle template detail mərkəzi side panel davranışındadır.

### 11. Table/grid component vahidliyi

Status: `in_progress`

İcra:
- Yeni modullardakı table-lar mövcud əsas grid dizaynına uyğunlaşdırılır.
- Header, row spacing, action button, empty state və pagination eyni vizual dildə olur.

Acceptance:
- Audit, Compliance, Employee Lifecycle, Candidate ATS table-ları eyni sistem hissi verir.
- Compliance və yeni modullarda əsas table görünüşləri uyğunlaşdırılıb.
- Köhnə ekranlarda qalan custom table-lar mərhələli audit edilməlidir.

### 12. Form input/filter/validation standartları

Status: `in_progress`

Hazırda:
- Filter component-ləri və validation error component-ləri yenilənib.

Qalan:
- Yeni modullarda custom/plain input qalıqları yoxlanmalıdır.
- Validation error-lar vahid compact qırmızı component ilə göstərilməlidir.

Acceptance:
- Form input-lar background-dan aydın seçilir.
- Error-lar eyni ölçü, padding və rəng sistemi ilə görünür.
- Filter panelləri 3-column premium layout standartını qoruyur.
- `x-validation` / `x-input-error` kompakt vahid error stilinə gətirilib.
- Filter/input komponentləri yeni modullarda tətbiq edilib.

## P3 - Query Budget Və Performans

### 13. Yeni modullar üçün query budget-ləri staging data ilə ölçmək

Status: `completed-local-pending-staging-data`

Modullar:
- Employee Lifecycle
- Compliance
- Candidate ATS
- Audit logs

Acceptance:
- Local query budget testləri keçir.
- Staging real data ilə yenidən ölçülür.
- Limitdə olan probe-lar 20-30% buffer ilə optimizasiya edilir.
- `audit:query-budget`, `employee-lifecycle:query-budget`, `compliance:document-query-budget`, `candidates:ats-query-budget`, `hr:strategic-query-budget` lokal keçdi.
- Staging real data böyüdükcə limitlər yenidən ölçülməlidir.

### 14. Runtime browser QA paketi

Status: `in_progress`

Ekranlar:
- Audit jurnalı
- Employee Lifecycle
- Document Compliance
- Candidate ATS
- Attendance forms
- All Personnel

Acceptance:
- Əsas add/edit/delete və detail açılışları browser-də yoxlanır.
- Desktop və mobile breakpoint-lərdə görünüş pozulmur.
- All Personnel add side modal desktop browser-də açılıb-bağlanma üzrə yoxlandı.
- Employee Lifecycle, Document Compliance, Audit Logs və Candidates səhifələri browser smoke QA-da error-suz yükləndi.
- Attendance və geniş add/edit/delete desktop/mobile QA ayrıca davam etməlidir.

## Tövsiyə Olunan İcra Sırası

1. Qalan desktop/mobile browser QA: Employee Lifecycle, Compliance, Candidate ATS, Attendance.
2. Production serverdə `assets:manifest-check`, `APP_DEBUG=false`, scheduler log təsdiqi.
3. Staging role/permission testi: admin və non-admin rolları.
4. Staging real data ilə query budget-ləri yenidən ölçmək.
5. Qalan sidebar/table/form component vahidliyini köhnə ekranlarda mərhələli tamamlamaq.

## Hər Mərhələ Üçün Standart Done Definition

- Feature test və ya mövcud uyğun test keçməlidir.
- Browser QA real ekran üzərində edilməlidir.
- Lokalizasiya yoxlanmalıdır.
- Query riski varsa budget ölçülməlidir.
- UI mövcud HRM dizayn dilindən kənara çıxmamalıdır.
- Commit/push yalnız ayrıca icazə ilə edilməlidir.
