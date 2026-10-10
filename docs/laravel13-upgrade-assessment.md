# HRM — Laravel 13 keçidinin qazanc, risk və xərc hesabatı

Tarix: 10.10.2026. Yoxlanan mənbə: `demo-mode`, `fb163b35` və mövcud lokal dəyişikliklər.
Status: qərar üçün texniki qiymətləndirmə; upgrade tətbiq edilməyib.

## Tövsiyə və qərarın səbəbi

**HRM-i Laravel 13-ə keçirmək məqsədəuyğundur, amma hazırkı təhlükəsizlik/deploy paketindən ayrı budaqda və staging yoxlamasından sonra.** Əsas qazanc uzun dəstək müddəti və yeni paketlərlə daha rahat işləməkdir. İstifadəçi üçün dərhal görünən yeni HR funksiyası və ölçülmüş sürət artımı yoxdur.

Paket səviyyəsində texniki blok tapılmadı: layihənin manifest/lock nüsxəsində aparılan Composer dry-run PHP 8.3 ilə Laravel 13.35.0 üçün uyğun dəsti həll etdi. Tətbiq bu dəstlə quraşdırılmadı, testlər və brauzer yoxlamaları Laravel 13 altında icra edilmədi. Buna görə nəticə “keçid mümkündür”, “istehsalata hazırdır” deyil.

İndi layihə budağını hazırlamağa dəyər. Canlı keçidi isə backup, hazır təhlükəsizlik paketinin buraxılışı və təsdiqlənmiş rollback planından sonra etmək lazımdır. Bu hesabat keçid qərarının özü deyil.

## Əvvəlki hesabat tapıldımı?

HRM `docs/` daxilində ayrıca Laravel 13 risk/qazanc hesabatı tapılmadı. `docs/archive/laravel12-livewire4-upgrade.md` əvvəlki Laravel 12 + Livewire 4 keçididir. `ISLER_PLANI.md` yalnız qısa Laravel 13 addımları verir. Başqa layihənin `anbar/docs/upgrade-report.md` sənədi HRM üçün uyğunluq sübutu deyil.

## Dəstək və PHP tələbi

| Mövzu | Laravel 12 | Laravel 13 |
|---|---|---|
| Minimum PHP | 8.2 | 8.3 |
| Bug-fix dəstəyi | 13.08.2026-da bitib | 2027-ci ilin III rübünə qədər |
| Təhlükəsizlik dəstəyi | 24.02.2027-yə qədər | 17.03.2028-ə qədər |

Beləliklə, 10.10.2026 vəziyyətinə Laravel 12 üçün təxminən 4,5 aylıq təhlükəsizlik dəstəyi qalır. Keçid təhlükəsizlik dəstəyi sonunu təxminən 13 ay irəli aparır. Yeni versiyada AI, JSON:API, vektor axtarışı və queue imkanları da var; HRM bunlardan yalnız ayrıca implementasiya ilə faydalanar. [Rəsmi release və dəstək cədvəli](https://laravel.com/docs/13.x/releases).

HRM lokal PHP-si 8.3.33-dür. `Dockerfile` PHP 8.3 imicindən, `.gitlab-ci.yml` PHP 8.3 runner-dən istifadə edir. Mövcud konfiq səviyyəsində PHP major upgrade tələb olunmur. Canlı konteynerin versiyası bu hesabatda yoxlanmayıb. Müştəriyə ayrıca PHP 8.2-də quraşdırma vəd edilirsə, Laravel 13 həmin uyğunluğu aradan qaldırır.

## Konkret nə qazanırıq?

| Qazanc | HRM üçün praktik nəticə | Dəyər |
|---|---|---|
| Daha uzun təhlükəsizlik dəstəyi | Növbəti məcburi framework keçidinə qədər daha çox vaxt | Yüksək |
| Aktual paket dəsti | Yeni düzəlişlər üçün köhnə major məhdudiyyətləri azalır | Orta–yüksək |
| Request-origin yoxlaması | Login və yazma axınlarının əlavə qorunma imkanları | Orta; konfiq və test tələb edir |
| Daha sərt serializasiya seçimi | Keş/sessiyanın obyekt açma sərhədlərini daraltmaq mümkündür | Orta; mərhələli tətbiq edilməlidir |
| ARBAY ilə eyni framework major-u | İki sistem üçün oxşar debug, deploy və sənəd yanaşması | Orta; biznes inteqrasiyası avtomatik dəyişmir |
| Müasir test alətləri | Pest 4 / PHPUnit 12 ilə test infrastrukturunu yeniləmək | Orta |
| Gələcək AI və axtarış imkanları | Namizəd/sənəd axtarışı üçün ayrıca məhsul işi mümkün olur | Hazırkı HRM üçün aşağı |

Request forgery qoruması origin yoxlaması ilə token yoxlamasını birlikdə istifadə edə bilir. HRM üçün token fallback saxlanmalıdır; `originOnly` rejimi ayrıca ehtiyac olmadan seçilməməlidir. [Rəsmi request forgery sənədi](https://laravel.com/docs/13.x/csrf).

Bu cədvəldəki biznes dəyəri və prioritetlər layihə üçün qiymətləndirməmdir. Framework funksiyasının mövcudluğu həmin funksiyanın HRM-də avtomatik aktivləşməsi demək deyil.

## Nə qazanmayacağıq?

- UI yenidən dizayn olunmayacaq; Livewire 4 və Blaze artıq layihədə var.
- Payroll float hesablamaları decimal olmayacaq, hüquqi normalar dəyişməyəcək.
- KPI API, webhook, növbə worker-i və çoxpilləli payroll təsdiqi öz-özünə yaranmayacaq.
- Struktur icazələri, tarixi aktorlar və çoxşəxsli köçürmə işi upgrade ilə həll olunmayacaq.
- Konkret faizlə sürət artımı və RAM azalması vəd etmək üçün ölçü yoxdur.
- AI servisi və onun xərci framework yenilənməsi ilə məcburi olmur; ayrıca dependency və məhsul qərarıdır.

Sürət əsas məqsəddirsə, sorğu sayı, indekslər, cache payload-ları, Livewire cavab ölçüsü və növbəli ağır hesablamalar ayrıca optimallaşdırılmalıdır.

## Paket uyğunluğu — real yoxlama

Yoxlama üçün manifest və lock müvəqqəti qovluğa köçürüldü. Əsas layihənin `composer.json`, `composer.lock`, `vendor`, bazası və tətbiq faylları dəyişdirilmədi. Composer `--dry-run --no-scripts --with-all-dependencies` ilə işlədi; çıxış kodu **0** oldu.

| Paket | Mövcud lock | Dry-run dəsti | Yanaşma |
|---|---|---|---|
| PHP | Manifest `^8.2`, lokal 8.3.33 | Manifest `^8.3` | Minimum tələb açıq göstərilsin |
| Laravel Framework | 12.69.3 | 13.35.0 | Major keçid |
| Tinker | 2.11.1 | 3.0.2 | Major keçid |
| Boost | 1.8.13 | 2.10.3 | Dev aləti yenilənir |
| Debugbar | `barryvdh` 3.16.5 | `fruitcake` 4.4.4 | Ad/namespace/config yoxlanmalıdır |
| Pest | 3.8.7 | 4.7.8 | Test major-u yenilənir |
| Pest Laravel plugin | 3.2.0 | 4.1.0 | Laravel 13 uyğunluğu |
| PHPUnit | 11.5.56 | 12.5.33 | Pest vasitəsilə həll olunur |
| Livewire | 4.4.1 | 4.4.7 | Major dəyişmir |
| Blaze | 1.0.17 | 1.0.19 | Major dəyişmir |
| Sanctum | 4.3.3 | 4.3.3 | Mövcud metadata Laravel 13-ə icazə verir |
| Excel | 3.1.70 | 3.1.70 | Mövcud metadata Laravel 13-ə icazə verir |
| Activitylog | 4.12.3 | 4.12.3 | Mövcud metadata Laravel 13-ə icazə verir |
| Permission | 6.25.0 | 6.25.0 | Mövcud metadata Laravel 13-ə icazə verir |
| PHPWord | 1.4.0 | 1.4.0 | Həll olunan dəstdə saxlanır |

Metadata `composer.lock` və paketlərin Packagist məlumatından yoxlanıb. [Debugbar metadata](https://packagist.org/packages/fruitcake/laravel-debugbar), [Tinker](https://packagist.org/packages/laravel/tinker), [Boost](https://packagist.org/packages/laravel/boost), [Pest Laravel plugin](https://packagist.org/packages/pestphp/pest-plugin-laravel).

Bu geniş update ssenarisi **5 əlavə, 52 yenilənmə, 3 çıxarılma** təklif etdi. Bu sayların hamısı Laravel 13 üçün məcburi deyil; geniş update transitiv paketləri də yeniləyir. Məsələn `brick/math` 0.14.8 → 1.0.0 olur. Yekun lock fərqi ayrıca nəzərdən keçirilməli, əlaqəsiz major dəyişikliklər mümkün qədər ayrılmalıdır. Dry-run təhlükəsizlik auditi və runtime uyğunluğu sübutu deyil.

Pest 4 PHP 8.3 və PHPUnit 12 ilə işləyir; artıq mövcud Pest 5 isə PHP 8.4 tələb edir. HRM-in bu keçidinə PHP 8.4/Pest 5-i əlavə etməyə ehtiyac yoxdur. Snapshot testləri istifadə edilirsə, adlandırma fərqi ayrıca qiymətləndirilməlidir. [Pest upgrade guide](https://pestphp.com/docs/upgrade-guide).

## Ziyan və risklər — HRM-ə xas qiymətləndirmə

| Risk | Müşahidə edilmiş HRM səbəbi | Mümkün nəticə | Qarşı tədbir |
|---|---|---|---|
| Obyekt keşləri | `CallPersonnelInfo` kolleksiyalar, Personnel wizard `City`/`CountryTranslation` modelləri, Admin Structures ağac saxlayır | Lookup-lar boş və ya uyğun olmayan obyekt kimi açıla bilər | Mövcud cache davranışını saxla; sonra array payload və ya tam allow-list keçidini ayrıca test et |
| Sessiya uyğunluğu | `config/session.php` köhnə cookie adını müəyyən edir, serialization açarı yoxdur | Konfiq kor-koranə dəyişsə giriş sessiyaları itə bilər | Cookie adı/prefixləri saxla; birinci keçiddə PHP serialization davranışını açıq saxla |
| CSRF/Livewire | App middleware, Http Kernel və Sanctum köhnə CSRF sinfinə istinad edir | Yazma axını, upload və girişdə 419/403 | Middleware və route istisnalarını birlikdə nəzərdən keçir; brauzerdə test et |
| Bootstrap qarışığı | `Application::configure` ilə yanaşı custom Http/Console Kernel və Handler binding-ləri var | Middleware/scheduler qeydiyyatı dəyişərkən gözlənilməz davranış | Minimal dəyişiklik et; skeleton-u bütöv köçürmə |
| Test major-u | Pest və PHPUnit birlikdə yenilənir | Metadata, mock və assertion uyğunsuzluğu | Tam test paketi, static analiz və modul gate-ləri |
| Geniş lock fərqi | Dry-run 52 yenilənmə verir | Framework-dən kənar regressiya | Dependency diff və package release qeydlərini ayrıca yoxla |
| Tenant sərhədi | DemoTenantSwitcher cache/store dəyişir | İstifadəçi və kirayəçi vəziyyətinin qarışması | İki fərqli tenant ilə auth/cache/storage testləri |
| Pul və məzuniyyət nəticəsi | Payroll/Vacation domeni həssasdır | Yuvarlaqlaşdırma və tarix sərhədi regressiyası | Eyni fixtures ilə əvvəl/sonra nəticə müqayisəsi |
| Fayl və PDF | DOCX→LibreOffice, private fayl marşrutları mövcuddur | Upload/download/PDF axını pozula bilər | Real Docker imicində DOCX/PDF və icazə yoxlaması |
| Deploy ilə pending miqrasiyaların qarışması | Plan üzrə arxivlə/sil miqrasiyaları pending ola bilər | Upgrade ilə səhvən məlumat dəyişiklikləri də buraxılar | Framework keçidini domen miqrasiyalarından ayır; migration ledger-i əvvəl yoxla |

Rəsmi guide-da CSRF köhnə adları deprecated alias kimi qalır. Session JSON-a dəyişəndə aktiv sessiyalar etibarsızlaşır; yeni cache skeleton-undakı `serializable_classes=false` obyekt keşləri ilə uyğunlaşdırılmalıdır. `upsert` üçün boş `uniqueBy`, bəzi queue event və route davranışları da dəyişir. `@js` Unicode çıxışı dəyişə bilər. [Laravel 13 upgrade guide](https://laravel.com/docs/13.x/upgrade).

Burada əsas risk **konfiq fayllarını yeni skeleton ilə bütöv əvəz etməkdir**. Laravel 13-ün yeni tətbiq default-u ilə mövcud HRM-in davranışını ayrı qiymətləndirmək lazımdır. Upgrade özü bütün yeni default-ları avtomatik bizim config-ə yazmır.

Keş sərtləşdirməsi tam imtina ediləcək iş deyil: ilk buraxılışda uyğunluq saxlanmalı, sonra obyekt payload-ları inventarlaşdırılıb ayrıca təhlükəsizlik işi kimi array və ya məhdud sinif siyahısına keçirilməlidir. Canlı keş store-u və bütün payload sinifləri bu hesabatda tam inventarlaşdırılmayıb.

Sessiyanın JSON-a keçirilməsi ayrıca təhlükəsizlik qərarı olmalıdır. Görülən session yazmaları əsasən scalar-dır, amma bu tam inventar və keçid sınağı deyil. Birinci upgrade-də istifadəçiləri səbəbsiz çıxarmağa ehtiyac yoxdur.

## CI və testlər haqqında vacib tapıntı

Mövcud `.gitlab-ci.yml` Composer install/validate, frontend build və manual production deploy verir. **Bu faylda Pest, PHPStan və modul CI gate-lərini işə salan test mərhələsi yoxdur.** GitLab pipeline-in yaşıl olması təkbaşına funksional uyğunluğu göstərmir. Composer-də həmin gate əmrlərinin olması onların pipeline-də icra edilməsi demək deyil.

Upgrade buraxılışına qədər pipeline-ə bu yoxlamalar daxil edilməli və ya eyni nəticələr release artefaktında məcburi saxlanmalıdır:

- Tam `vendor/bin/pest`.
- PHPStan və Pint check; baseline böyüdülməsin.
- `ci:orders-gate`, `ci:attendance-gate`, `ci:audit-gate`, `ci:strategic-hr-gate`, `ci:translations`.
- `npm ci` və `npm run build`.
- Docker imicinin build-i, config/route/view cache əmrlərinin smoke yoxlaması.

Sonnet-in `@dataProvider` → `#[DataProvider]` dəyişiklikləri lokal fayllarda mövcuddur; hazırkı axtarışda qalan `@dataProvider` yoxdur. Bu, test paketinin bütünlüklə PHPUnit 12 uyğunluğunu sübut etmir.

Blade fayllarında `@js` üçün 124 uyğunluq var (komment daxil ola bilər). Bunların hamısı problem deyil; seçilmiş həssas axınlarda Azərbaycan/Rus hərfləri, apostrof, dırnaq, modal və təsdiq mesajı brauzerdə yoxlanmalıdır.

## Vaxt, əməliyyat xərci və alternativlər

Aşağıdakı rəqəmlər HRM-in ölçüsünə görə ilkin mühəndis qiymətləndirməsidir; zəmanət deyil.

| İş | Təxmini vaxt |
|---|---|
| Ayrı budaq, dependency/config uyğunlaşdırması | 0,5–1 gün |
| Test regressiyaları, static analiz, lock yoxlaması | 1–2 gün |
| Staging, real brauzer və Docker/PDF yoxlaması | 1 gün |
| Release/rollback məşqi və son düzəliş ehtiyatı | 0,5–1 gün |
| Ümumi planlama intervalı | 3–5 iş günü |

Staging və backup hazırlığı gözlənilərsə təqvim müddəti uzanar. Framework üçün ayrıca lisenziya büdcəsi nəzərdə tutulmur; əsas xərc mühəndis vaxtı, staging resursu və regresiya yoxlamasıdır. Yeni AI provider-i, worker və ya server resursu yalnız ayrıca seçilən funksiyanın xərcidir.

| Variant | Qazanc | Xərc/risk | Qiymətləndirmə |
|---|---|---|---|
| İndi birbaşa production upgrade | Dəstək müddəti tez uzanır | Hazır deploy və pending miqrasiyalarla regressiya səbəbini ayırmaq çətinləşir | Tövsiyə etmirəm |
| İndi ayrıca budaq/staging, hazır təhlükəsizlik paketindən sonra release | Qazancı alırıq, regressiyanı ayırırıq | İki ayrı release yoxlaması | Tövsiyə etdiyim variant |
| Laravel 12-də qısa müddət qalmaq | Cari upgrade yükü təxirə düşür | Dəstək müddəti azalır, sonradan keçid yenə tələb olunur | Müvəqqəti seçim |
| Laravel 12-də müddətsiz qalmaq | İndi vaxt sərf edilmir | Təhlükəsizlik dəstəyi bitəndən sonra qəbul edilməyən texniki risk | Tövsiyə etmirəm |

## Keçid və qəbul planı

1. Hazır Sonnet/Opus dəyişikliklərini ayrı commitlərlə sabitləşdir. Mövcud dirty fayllar qorunsun. Laravel 12 baseline test/ölçüləri saxlanılsın.
2. Ayrı `codex/upgrade-laravel-13` budağı yarat. Bu hesabat həmin budağı yaratmır.
3. Minimum PHP və təsdiqlənmiş package constraint-lərini yenilə. Livewire 4, Blaze, Permission 6 və Activitylog 4 major-larını saxla.
4. Custom bootstrap/Kernel quruluşunu qoruyaraq CSRF istinadlarını uyğunlaşdır. Keş və sessiya default-larını ayrıca qərarla dəyiş.
5. Tam avtomatik yoxlamaları çalışdır; Laravel 13 altında keçməyən testləri düzəlt. Payroll/məzuniyyət nəticələrini dəyişikliksiz fixtures ilə müqayisə et.
6. Production bazasının məxfiləşdirilmiş surəti ilə staging qur. Pending migrations və activity-log ayrıca bazası üçün ledger/backup planı hazırla.
7. Login, logout, sessiya davamlılığı, tenant keçidi, bütün əsas rollar, Personnel wizard, məzuniyyət, əmrlər və PDF, payroll/payslip, ARBAY mübadiləsi, scheduler və mövcud queue axınlarını yoxla.
8. Eyni məlumat, imic resursu və yük ilə real render/query benchmark ölç. Testdə sabit 10 ms nəticəsini performans ölçüsü kimi istifadə etmə.
9. Uyğun lock və Docker imicini dondur. Rollback məşqi uğurlu olduqdan sonra iş saatından kənar release et.

Qəbul üçün şərtlər: izahsız test failure yoxdur; pul/məzuniyyət nəticələri uyğun gəlir; tenant və struktur sərhədləri keçilmir; real render/PDF/upload axınları işləyir; yük ölçüsündə əhəmiyyətli pisləşmə izah və həll olunub; rollback yoxlanıb.

## Rollback necə qorunmalıdır?

Əvvəlki Docker imici, Git commit, lock və config nüsxəsi saxlanmalıdır. `APP_KEY` dəyişdirilməməlidir. Cache/session prefix və serialization dəyişiklikləri rollback ilə uyğun olmalıdır. İlk release-də domen schema/data dəyişikliklərini framework keçidinə qatmasaq, geri dönüş əsasən əvvəlki tətbiq imicinə qayıtmaq olur.

Əgər release data miqrasiyası da icra edirsə, yalnız Git revert bazanı bərpa etmir. Backup-dan bərpa üçün qəbul edilən məlumat itkisi müddəti, maintenance pəncərəsi və bərpa addımları əvvəl razılaşdırılmalıdır. Bu hesabatda canlı backup/restore yoxlanmayıb.

## Bu hesabatda təsdiqlənən və açıq qalan sübut

**Yoxlandı:** HRM manifest/lock, lokal PHP, Docker/CI konfiqi, CSRF istinadları, obyekt keş nümunələri, session/prefix konfiqi, provider atributlarının vəziyyəti, `@js` inventarı, rəsmi Laravel/Pest sənədləri və real Composer dependency dry-run.

**Yoxlanmadı:** Laravel 13 altında tətbiq boot-u/tam Pest, real brauzer, canlı PHP və cache/session/queue mühiti, staging bazası, PDF imici, production deploy, backup/restore və performans müqayisəsi.

**Qərar təklifi:** Laravel 13 keçidini ayrıca iş kimi qəbul edək; indiki təhlükəsizlik paketinin deploy-u ilə birləşdirməyək. Keçidin mühəndis hazırlığına başlamaq olar, canlı release isə yuxarıdakı qəbul şərtlərindən sonra olmalıdır.

## Composer yoxlamasının saxlanmış xülasəsi

Müvəqqəti manifestdə əsas dəyişikliklər: PHP `^8.3`, framework `^13.0`, Tinker `^3.0`, Boost `^2.0`, Pest `^4.0`, Laravel plugin `^4.1`, Debugbar `fruitcake/laravel-debugbar ^4.0`.

```text
composer update --dry-run --no-scripts --no-interaction --with-all-dependencies --no-progress
Lock file operations: 5 installs, 52 updates, 3 removals
laravel/framework: v12.69.3 => v13.35.0
laravel/tinker: v2.11.1 => v3.0.2
laravel/boost: v1.8.13 => v2.10.3
pestphp/pest: v3.8.7 => v4.7.8
pestphp/pest-plugin-laravel: v3.2.0 => v4.1.0
phpunit/phpunit: 11.5.56 => 12.5.33
Exit code: 0
```

Dry-run çıxışındakı “Installing” sətirləri planlaşdırılan əməliyyatlardır; paketlər faktiki quraşdırılmayıb. Bu nəticə 10.10.2026 paket metadata-sına aiddir; icra zamanı yenidən həll edilməlidir.
