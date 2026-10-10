# HRM və ARBAY — inteqrasiya, təhlükəsizlik və istehsalat təklifi

Tarix: 11.10.2026. Bu sənəd KPI/API, şirkət rolları/CSP, DOCX redaktəsi və istifadəçinin yalnız izah istədiyi istehsalat/siyasət işlərini açıqlayır. Təklif olunan endpoint, cədvəl və ekranlar mövcud funksiya kimi təqdim edilmir.

## 1. KPI REST/BI API və webhook: məqsəd və mərhələlər

REST API digər sistemin HRM-dən KPI məlumatını oxuması və fakt göndərməsi üçündür. Məsələn satış sistemində işçinin aylıq satış nəticəsi HR tərəfindən Excel-ə köçürülmədən KPI kartına təklif oluna bilər. BI API rəhbərliyə Power BI/Excel-də müqayisəli hesabat qurmağa imkan verir. Webhook isə nəticə təsdiqlənəndə qarşı sistemə xəbər verir; təkrar-təkrar bütün kartları soruşmaq ehtiyacını azaldır.

HRM-də artıq `Modules/Integration/Routes/api.php`, hash-lənmiş `ApiToken`, ability middleware, `IntegrationOutbox`, `OutboxEvent` və `SafeUrl` var. KPI biznesi `Modules/PerformanceEvaluation/Application/Services/Kpi`-dədir. Bunları saxlayıb mövcud modul sərhədləri üzərindən genişləndirmək təklif olunur; ayrıca yeni backend qurmaq lazım deyil.

### Harada nə edilməlidir?

| Yer | Təklif olunan dəyişiklik |
|---|---|
| `Modules/Integration/Support/Contract.php`, API routes və middleware | Versiyalı endpoint müqaviləsi, ayrıca ability-lər, request limitləri və token konteksti |
| PerformanceEvaluation modulunun Contracts sərhədi | İnteqrasiya üçün icazəli oxu/fakt qəbul müqaviləsi; Integration KPI daxili modellərini birbaşa oxumasın |
| Mövcud KPI Application Services | Fakt, status, evidence və bonus qaydaları burada qalsın; controller yenidən hesablama məntiqini təkrarlamasın |
| Integration modulunun öz migrations qovluğu | Token scope/aktor əlaqəsi, idempotency qeydi, webhook abunəliyi və delivery vəziyyəti üçün lazım olan schema |
| Integration Application/Infrastructure | Scoped adapter, webhook sender, delivery/retry və audit |
| Mövcud integration sənədi | OpenAPI müqaviləsi, request/response nümunələri və xəta kataloqu |
| İnteqrasiya admin idarəsi | Token/abunəlik yaratma, deaktiv etmə, son istifadə və uğursuz delivery görünüşü; gizli açar yalnız bir dəfə göstərilsin |

Birinci release **yalnız oxu** olsun. Sonra fakt qəbuluna dry-run və HR review mərhələsi əlavə edək. Təkrar faktın əvəz edilməsi/toplanması qərarı verilmədən avtomatik yazmanı açmayaq. Webhook üçün işləyən worker və retry müşahidəsi tələb olunur.

### İlkin endpoint təklifi

| Endpoint | Məqsəd | İcazə |
|---|---|---|
| `GET /api/v1/kpi/cycles` | Dövrlərin siyahısı | `kpi.cards:read` |
| `GET /api/v1/kpi/scorecards` | Görünən kartlar, hədəflər və nəticə statusu | `kpi.cards:read` |
| `GET /api/v1/kpi/actuals` | Faktlar və onların təsdiq vəziyyəti | `kpi.actuals:read` |
| `POST /api/v1/kpi/actuals/imports/preview` | Göndərilən faktların yoxlanması, hələ yazılmır | `kpi.actuals:write` |
| `POST /api/v1/kpi/actuals/imports` | Razılaşdırılmış qayda ilə fakt qəbul işi | `kpi.actuals:write` |
| `GET /api/v1/kpi/imports/{id}` | İdxal nəticəsi və sətir xətaları | Yalnız həmin client-in işi |
| `GET /api/v1/kpi/bonuses` | İcazəli bonus nəticələri | ayrıca `kpi.bonuses:read` |
| `GET /api/v1/bi/kpi-scorecards`, `/kpi-actuals` | Düz, səhifələnən BI cədvəlləri | `bi.kpi:read`; həssas sütunlar ayrıca grant |

Bu adlar təklifdir; implementasiyadan əvvəl OpenAPI müqaviləsində dondurulmalıdır. BI endpoint-i hesablamanı hər sorğuda işə salmasın: saxlanmış nəticələri oxusun, məlumatın tarixini/version-unu göstərsin. Səhifə ölçüsü başlanğıcda 100, maksimum 500 kimi limitlənsin; hədlər yük testindən sonra tənzimlənsin. Cursor dəyişməz ID-yə və export watermark-a bağlansın. `updated_since` ilə yenilənən qeydlər və silinmə/tombstone müqaviləsi müəyyən edilsin ki, BI köhnə qeydi əbədi saxlamasın.

### İcazə, məlumat və təkrar sorğu sərhədi

Hazır HRM tokenində `abilities=null` bütün ability-lərə icazə deməkdir. Yeni KPI/bonus səlahiyyətini köhnə geniş tokenlərə avtomatik vermək olmaz. Yeni endpoint-lərdə açıq scope/grant tələb edilməlidir. Token həm texniki aktora, həm icazəli struktur dəstinə bağlanmalıdır; client-in göndərdiyi `user_id` və ya `structure_id` ona hüquq qazandırmamalıdır. Struktur yoxlaması pagination, count, export, detal və fakt yazmasında eyni olmalıdır.

HRM-in demo tenant-ları ayrıca DB/storage konteksti ilə işləyir. ARBAY-dakı `company_id` modelini HRM-də varmış kimi qəbul etməyək. Token konkret HRM installation/tenant kontekstində həll edilsin; başqa tenant-a header/query vasitəsilə sərbəst keçid olmasın. ARBAY API-də isə company token kontekstindən gəlməlidir.

Mövcud `ScorecardService::recordActual` User aktoru istəyir. Machine actor üçün təsdiqlənmiş servis hesabı və audit əlaqəsi lazımdır; API request-dən gələn istifadəçi ID-si ilə impersonation edilməsin. Fakt göndərmək kartı təsdiqləmək, bonusu ödəmək və ya payroll-u kilidləmək hüququ verməsin. Evidence tələb olunan fakt sadəcə rəqəmlə qəbul edilməsin.

`Idempotency-Key` client/tenant/endpoint ilə unikal saxlanılsın. Eyni açar və eyni payload əvvəlki nəticəni qaytarsın, fərqli payload 409 olsun. Bundan əlavə xarici faktın `source_record_id`-si olmalıdır: retry açarı yeni olsa belə eyni satış/ölçmə ikinci dəfə toplanmasın. Dövr bağlanıbsa konflikt qaytarılsın. Faktların səhv hissəsi səssizcə ötürülməsin; bütün batch-in qəbul siyasəti müqavilədə göstərilsin.

Pul və tarix representation-u sabit olsun: məbləğ/currency ayrıca, pul decimal string, tarix ISO, saat qurşağı açıq. JSON error code-ları sabit, izah az/en/ru olsun. 401, 403, 404, 409, 422, 429 ayrıca müqavilələşdirilsin. Xarici istifadəçiyə görünməyən kartın mövcudluğu detal error-u ilə sızdırılmasın.

PIN, bank hesabı, sənəd nömrələri və şəxsi şərhlər standart BI export-a daxil edilməsin. Bonus məbləği ayrıca grant tələb etsin. BI faylı yükləndikdən sonra HRM icazəsi həmin xarici nüsxəni geri ala bilmir; yalnız lazım olan sütunlar verilməlidir.

### Webhook təklifi

İlk hadisələr `kpi.scorecard.approved`, `kpi.cycle.closed`, `kpi.bonus.finalized` ola bilər. Payload minimum metadata saxlasın: `event_id`, hadisə/version, entity ID, vaxt, nəticə version-u. Tam şəxsi məlumatı push etmək əvəzinə alıcı öz icazəli API tokeni ilə detalı çəksin.

Outbox biznes transaction-da yazılsın. Worker commitdən sonra delivery-ni tapsın; dispatcher anlıq dayansa periodik recovery göndərilməmiş qeydi yenidən tapsın. Hər subscription-un ayrı delivery statusu və ayrı secret-i olsun. Mövcud ARBAY orders feed/polling formatı dəyişdirilməsin.

İmza təklifi: `HMAC-SHA256(secret, event_id + timestamp + raw_body)`. Dəqiq separator/UTF-8 qaydası müqavilədə göstərilsin. Alıcı sabit vaxtlı müqayisə, məsələn 5 dəqiqəlik timestamp pəncərəsi və `event_id` dedup ilə yoxlasın. Retry-də eyni event ID, yeni timestamp/imza istifadə edilsin. HMAC məzmunu şifrələmir: HTTPS də tələb olunur.

URL hər cəhddə mövcud `SafeUrl` ilə yoxlanıb yoxlanmış IP-yə bağlansın; redirect bağlansın. Daxili host istisnası yalnız administratorun dar allow-list qərarı ilə verilsin. Response body/time limitləri olsun. Secret şifrəli saxlanılsın, log-da token, imza və həssas payload görünməsin.

Retry başlanğıcı: 1 dəq, 5 dəq, 15 dəq, 1 saat, 6 saat və son hədd; jitter olsun. 2xx uğur, 429 üçün məhdud `Retry-After`, timeout/5xx üçün retry; daimi 4xx-lər araşdırma növbəsinə düşsün. Təkrar çatdırılma mümkündür: exactly-once vəd edilməsin. Event version-u gecikmiş hadisənin yeni nəticəni əvəz etməsinə mane olsun. Delivery history, failed backlog, manual retry və secret rotation ayrıca görünməlidir.

### Xeyri, xərci və başlanğıc prioriteti

Əl idxalı və səhvlər azalır; məlumatın mənbəyi və təkrarları izlənir; rəhbərlik BI hesabatını avtomatlaşdıra bilir. Qarşılığında yeni məlumat çıxış səthi, worker, monitoring və müqavilə saxlanması xərci yaranır. Kiçik istifadəçi sayı və nadir idxal üçün mövcud Excel/REST connector kifayət edə bilər. Mənim prioritetim: scoped read API → BI pilot → fakt preview/review → write API → webhook. Mürəkkəb inteqrasiya ehtiyacı olmadan hamısını birdən açmayaq.

## 2. ARBAY: şirkətə görə rollar

Hazırda `config/permission.php`-də teams söndürülüb. `User::isPlatformAdmin`, `AccessCatalog`, `Gate::before` və Shield global rol modelinə əsaslanır. İstifadəçi A şirkətində mühasibdirsə bağlı B şirkətində də həmin rol işləyir. Bu data tenancy-ni avtomatik ləğv etmir, amma şirkətə görə fərqli vəzifə hüququnu ifadə etmir.

Məqsəd: eyni insan A-da mühasib, B-də yalnız oxucu, C-də anbar operatoru ola bilsin. Platforma operatoru ayrıca qalsın. Rol şirkət scope-u ilə yanaşı mövcud division/warehouse/money-account scope-ları da tətbiq edilməlidir.

| Yer | Nə dəyişəcək |
|---|---|
| `config/permission.php`, database migrations | Teams aktivləşməsi, team key şirkətə bağlanması, pivot/unique indeksləri |
| `AdminPanelProvider::tenantMiddleware` | Tenant məlum olduqdan, policy yoxlanmadan əvvəl permission team konteksti; Livewire request-lərində persistent tətbiq |
| `User`, `AccessCatalog`, `AppServiceProvider` gate-ləri | Platforma hüququnun tenant-local roldan ayrı müəyyənləşdirilməsi; mövcud qadağanedici gate sırası saxlanması |
| `Services/Security/UserAdministration` | Aktiv şirkət üçün rol təyini; başqa şirkətin rolunu dəyişmək/silmək bloklanması |
| Shield/role ekranları və `RolesSeeder` | Şirkət rolları, platforma rolları və permission catalog fərqi; yenidən seeding zamanı mövcud rollar qorunması |
| API, queue, console | Filament-dən kənarda açıq company/team konteksti, iş bitəndə `finally` ilə reset |
| Testlər | Tenant switch, eyni user-in fərqli rolları, direct URL/action/API və ardıcıl queue işləri |

Spatie team konteksti dəyişəndə yüklənmiş `roles`/`permissions` əlaqələri təmizlənməlidir; middleware sırası önəmlidir. [Spatie teams sənədi](https://spatie.be/docs/laravel-permission/v6/basic-usage/teams-permissions).

Köhnə global rolları bütün şirkətlərə düşünmədən yaymaq olmaz. Əvvəl user × company × role matrisi və mövcud memberships çıxarılmalıdır. Təklif: şirkət istifadəçilərinin cari effektiv hüququ üzrə preview hazırlamaq, dəyişiklikləri admin təsdiqindən sonra tətbiq etmək. Global super-admin assignment-ları tenant-a təxminlə köçürülməsin; platforma hüququnun saxlanma mexanizmi əvvəl dizayn edilsin. Mövcud membership yoxlaması yenə məcburi qalsın.

Fayda: shared accountant/outsourcing modeli təhlükəsiz ifadə olunur. Xərc: permission migration-u, rol ekranları və bütün giriş yollarının yenidən testidir. Səhv middleware sırası həm məlumat sızmasına, həm legitim istifadəçinin bloklanmasına səbəb ola bilər. Bu iş CSP keçidindən ayrı buraxılış olsun.

## 3. ARBAY: tam script CSP

Hazır `SecurityHeaders` yalnız `frame-ancestors 'self'` tətbiq edir; private download route-ları daha sərtdir. Bu clickjacking qorumasıdır, bütün script execution siyasəti deyil. ARBAY Filament 4 / Livewire 3 istifadə edir. HRM-in Livewire 4 `csp_safe` ayarını ARBAY-a köçürmək düzgün deyil.

Təklif: əvvəl `Content-Security-Policy-Report-Only` ilə real pozuntuları toplayaq. Hesabat collector-u rate-limit/retention ilə işləsin; hesabatdakı URL/query şəxsi məlumat ola bildiyindən redaksiya edilsin. Hələ enforcement başlamasın.

Sonra Filament/Livewire bundle, Alpine ifadələri, inline script-lər, chart, dropdown, datepicker, upload, modal, print və Vite asset-ləri inventarlaşdırılsın. `SecurityHeaders`, `AdminPanelProvider` render hooks, `resources/js`, `resources/views` və paket asset-ləri üzrə siyahı qurulsun. Vendor faylını əl ilə patch etmək əvəzinə uyğun rəsmi extension/build yolu seçilsin.

Alpine CSP build `unsafe-eval` ehtiyacını aradan qaldırmaq üçün mövcuddur, amma tam sintaksis uyğunluğu vəd etmir. [Alpine CSP sənədi](https://alpinejs.dev/advanced/csp). Livewire 4-də ayrıca CSP-safe imkan var; bu ARBAY-ın Livewire 3 steki üçün avtomatik həll deyil. [Livewire 4 CSP sənədi](https://livewire.laravel.com/docs/4.x/csp).

Hədəf: request-ə məxsus nonce və ya sabit asset hash-ları ilə `script-src`, `unsafe-eval` olmaması, mümkün olduqca `unsafe-inline` olmaması. `object-src 'none'`, dar `base-uri`, `connect-src`, `frame-src`, `img-src`, `form-action` da ölçülmüş ehtiyacla müəyyənləşsin. `strict-dynamic` seçilirsə etibar zənciri ayrıca yoxlanmalıdır; geniş CDN allow-list təhlükəsizlik məqsədini zəiflədə bilər. Style siyasəti ayrıca mərhələdir; “tam script CSP” bütün inline stillərin dərhal çıxarılması demək deyil.

Nonceli HTML page cache ilə uyğunlaşdırılmalıdır; səhv cache siyasəti script-ləri bloklaya bilər. Download cavablarının daha sərt başlığı saxlanmalıdır. Enforce yalnız real browser matrisindən sonra, feature flag və dərhal geri dönüş imkanı ilə açılsın.

Fayda: bəzi XSS yollarının script icra imkanını daraldır. CSP icazə yoxlamasını və HTML escaping-i əvəz etmir. Performans artımı gözləmirəm; xərc refaktor və browser QA-dır. “Header-i bir sətirlə sərtləşdirək” paneli işləməz hala sala bilər.

## 4. Brauzerdə DOCX redaktəsi — indi implementasiya edilmir

Bu, Word sənədini HRM səhifəsində redaktə etməyə imkan verən ayrıca document server inteqrasiyasıdır. Sadə textarea, mövcud DOCX placeholder designer-i və PDF preview-dən fərqlənir. ONLYOFFICE editor/JWT/callback, Collabora isə WOPI host və document locking axını tələb edir. [ONLYOFFICE inteqrasiya sənədi](https://api.onlyoffice.com/docs/docs-api/get-started/how-it-works/), [Collabora WOPI inteqrasiyası](https://sdk.collaboraonline.com/docs/How_to_integrate.html). Collabora SDK səhifəsi bu yoxlamada bot qoruması ilə açılmadı; dəqiq WOPI/save contract pilotdan əvvəl rəsmi SDK ilə ayrıca təsdiqlənməlidir.

### Harada faydalıdır?

HR operatoru mətni, cədvəli, sətir aralığını və formatı Word yükləmədən düzəldə bilər. İki nəfər eyni qaralamanı işləyə, dəyişiklik tarixçəsi və şərh görə bilər. Ayrı-ayrı Word faylları və “son-son-v2” nüsxələri azalır. Bunun dəyəri ən çox sərbəst mətnli əmrlər və həqiqətən tez-tez Word-də düzəldilən sənədlər üçündür.

Bizim əmrlər isə sənədlə yanaşı **strukturlaşdırılmış effektlər** daşıyır. Operator DOCX-də köçürmə tarixini dəyişsə, snapshot-dakı `effective_date` öz-özünə dəyişmir. Mətn və biznes nəticəsi zidd ola bilər. Buna görə sərbəst DOCX redaktəsini biznes faktı kimi istifadə etmək olmaz.

Təklif olunan sərhəd: əvvəl yalnız template/qaralama redaktəsi. Əmr təsdiqlənəndə strukturlaşdırılmış sahələr yenidən render və yoxlamadan keçsin; yekun PDF və hash dondurulsun. Təsdiqlənmiş sənədi editor-da dəyişmək bağlı olsun. Düzəliş yeni revision və ya ləğv/yeni əmr proseduru ilə getsin. Tarix/vəzifə/maaş kimi kritik sahələrin sərbəst redaktəsinə ayrıca qayda lazımdır.

### Maneələr və əməliyyat xərci

- Editor server-inin HRM faylını oxuya bilməsi lazımdır, amma public storage açılmamalıdır. Qısa müddətli, sənədə/tenant-a bağlı access və write callback yoxlaması lazımdır.
- JWT/WOPI, file version, lock və callback imzası yoxlanmalıdır. Callback-dakı download URL SSRF yolu olmamalıdır.
- Eyni sənədin iki save-i arasında version/hash konflikti həll olunmalıdır; gecikmiş callback yeni faylı əvəz etməsin.
- Şablon placeholder-larının silinməsi və Word XML-inin yenidən yazılması parser/renderer-i poza bilər. Saxlamadan sonra DOCX təhlükəsizlik və token bütövlüyü yoxlaması lazımdır.
- Azərbaycan şriftləri, mürəkkəb cədvəllər, səhifə nömrələri, imza hissəsi və header/footer üçün bizim real sənəd matrisi ilə uyğunluq testi lazımdır.
- HRM-in yekun PDF-i LibreOffice ilə yaradılır. Editor görüntüsü ilə yekun PDF-də layout fərqi ola bilər; eyni sənədin faktiki çıxışı yoxlanmalıdır.
- Proxy/WebSocket, iframe CSP, əlavə servis update-i, monitoring, backup və zəifliklərin izlənməsi əməliyyat yükünü artırır.

### Performansa necə təsir edəcək?

Adi HRM səhifələrində editor yüklənməzsə əsas panelə təsir məhdud olar. Editor səhifəsi əlavə JS, font və sənəd download-u səbəbilə daha ağır olacaq. İlk açılışda DOCX hazırlanması/konvertasiyası, save-də callback və disk yazması gecikmə yarada bilər. RAM/CPU yükü eyni anda açıq sənədlərin sayı, ölçüsü və formatından asılıdır. Eyni VPS-də yerləşdirsək sənəd servisi payroll/query/PDF prosesi ilə resurs uğrunda rəqabət aparar.

Mənim təklifim ayrıca servis/konteyner və resurs limitləridir; yalnız editor açıldıqda lazy load. Autosave hər klavişdə PHP request yaratmasın, document server-in save/version mexanizmi ilə uyğunlaşdırılsın. Mövcud yüngül PDF preview tez-tez yalnız baxış üçün saxlanılsın. Editor outage olsa HRM-in qalan hissəsi işləsin, yüklə–redaktə et–yenidən yüklə fallback-ı olsun.

ONLYOFFICE-in rəsmi Docker sənədi minimum 4 GB RAM və 40 GB boş disk göstərir; real tutum sənəd yükündən asılıdır. Bu rəqəm bizim server üçün ölçülmüş sizing deyil. [Sistem tələbləri](https://helpcenter.onlyoffice.com/docs/installation/docs-community-sys-reqs-docker.aspx). İlk pilotda real HRM DOCX-ləri ilə open/save p95, eyni vaxtlı istifadə, server RAM/CPU və yekun PDF ölçülməlidir. Hazırda “X istifadəçi üçün Y ms” vəd etmək üçün ölçü yoxdur.

### Lisenziya və seçim

ONLYOFFICE Community AGPL şərtləri və proprietary/SaaS inteqrasiya/branding məsələləri ayrıca qiymətləndirilməlidir. Təchizatçının FAQ-ı proprietary inteqrasiya və white-label üçün commercial lisenziya istiqamətini göstərir; bizim tətbiqin hüquqi statusuna avtomatik nəticə çıxarmayaq. Dəqiq edition/version, inteqrasiya forması və müqavilə üçün təchizatçıdan yazılı uyğunluq cavabı alınmalıdır. Köhnə “20 bağlantı” rəqəmini aktual universal limit kimi qəbul etmirəm. [Rəsmi lisenziya FAQ](https://helpcenter.onlyoffice.com/docs/faq/docs-community.aspx).

Collabora özü CODE-u business/production üçün tövsiyə etmir; supported Collabora Online subscription ayrı məhsuldur. [Collabora FAQ](https://www.collaboraonline.com/faqs/). Qiymət və limitlər üçün real istifadəçi/sənəd yükü əsasında kommersiya təklifi lazımdır.

**Mənim qərar təklifim:** hazırda mövcud template designer + PDF preview + qaralama autosave ilə davam edək. Operatorların nə qədər və hansı düzəlişlər üçün Word-ə çıxdığını ölçək. Ehtiyac yüksəkdirsə bir template üzrə ayrıca pilot edək; bütün əmrlərə sərbəst redaktəni birdən açmayaq. Bu sənəddə editor quraşdırılmayıb və servis/lisenziya alınmayıb.

## 5. İcra edilməyən istehsalat bəndləri — harda, nə üçün, fayda

### 5.1 Backup, migrations, env, rollar və tokenlər

**Backup:** HRM DB-si ilə yanaşı ayrıca activity-log DB-si, private/public fayllar və zəruri config sirlərinin qorunan nüsxəsi lazımdır. Bazanın consistent surəti və uyğun fayl snapshot-u olmalıdır; yalnız backup faylının yaranması bərpanın işlədiyini göstərmir. Restore ayrıca staging-də yoxlanmalıdır. Fayda: migration/deploy səhvində razılaşdırılmış məlumat itkisi və bərpa müddəti ilə geri dönüş.

**Migrations:** HRM və ARBAY-da `migrate:status` nəticəsi faktiki bazadan oxunmalıdır. HRM-də arxivləmə və köhnə cədvəllərin silinməsi, actor ID qorunması, user–personnel backfill və private fayl köçürməsi; ARBAY-da branding və əvvəlki pending migration-lar ayrıca nəzərdən keçirilməlidir. Arxivli miqrasiyanın əvvəlki versiyası artıq işləyibsə sonradan koduna əlavə edilmiş arxiv addımı retroaktiv işləməz. Rollback kodu məlumat backup-ını əvəz etmir. Fayda: tətbiq ilə DB schema uyğunsuzluğunun və təsadüfi məlumat itkisinin qarşısı.

**Mühit:** Coolify/real konteynerdə HRM `APP_URL`, `TRUSTED_HOSTS`, `TRUSTED_PROXIES`, secure cookie və outbound allow-list; ARBAY debug/session/proxy/SafeUrl parametrləri təsdiqlənməlidir. Daxili hostlara geniş wildcard verilməsin. `APP_KEY` dəyişdirilməsin. Env cache yenilənməsi və worker restart release addımıdır. Fayda: düzgün HTTPS/domain davranışı, sessiya təhlükəsizliyi və SSRF sərhədi.

**Rollar:** HRM Admin → Rollar ekranında hər HR roluna icazəli struktur və ya əsaslandırılmış bütün-struktur hüququ verilir. Struktursuz rolun hər şeyi görməsi əvəzinə heç nə görməməsi təhlükəsizlik default-udur; deploydan sonra legitim HR user-in boş siyahı görməməsi üçün preview lazımdır. ARBAY müştəri admini platforma `super_admin` yox, `şirkət_admini` olmalıdır. Fayda: hüquqları işi tələb edən həddə saxlamaq.

**Tokenlər:** HRM işçi/compensation lenti üçün lazımi ability-lər; ARBAY mübadilə tokeni üçün `exchange` icazəsi yoxlanır. Plain token yalnız yaradılarkən görünür; encrypted secret store-da saxlanmalı, log/ekran görüntüsünə düşməməlidir. Minimum ability, expiry, rotation və deaktiv etmə qaydası seçilsin. Maaş `null` olanda ARBAY-da köhnə maaşı saxlamaq qoruması artıq var; düzgün token yenə aktual maaş sinxronu üçün lazımdır. Bu bəndlər burada icra edilməyib.

### 5.2 Köhnə private fayllar və user–personnel bağları

Yeni faylların private saxlanması köhnə public nüsxəni avtomatik təhlükəsiz etmir. HRM learning faylları `files:privatize`, ARBAY əlavələri `attachments:secure`, branding `branding:secure` ilə işlənir. Əvvəl dry-run/report: source, owner, ölçü/hash, ortaq istifadə və conflict-lər yoxlanır; sonra razılaşdırılmış maintenance pəncərəsində köçürmə. Sonra public URL-in açılmaması, icazəli private download-un işləməsi və SHA-256 uyğunluğu yoxlanır. Demo tenant storage-i ayrıdır; bir tenant üçün icra hamısını əhatə etmir.

Yetim fayl siyahısı silmə icazəsi deyil. Başqa qeyd, soft-delete, snapshot və arxivə istinad ayrıca yoxlanmalıdır. Fayda: istifadəçi sənədi və şirkət möhürünün internetdən icazəsiz açılmasını bağlamaq, mövcud download-u qorumaq.

User–personnel backfill texniki hesabı konkret işçi ilə bağlayır. MyHr, öz məzuniyyəti və payslip göstərmək üçün düzgün şəxsiyyət əlaqəsi lazımdır. Ad/soyad oxşarlığı təkbaşına sübut deyil. Miqrasiya hesabatında ambiguous/not-found çıxanlar Admin istifadəçi bağları ekranında təsdiqlənir; inactive/soft-deleted və shared hesab hallarına baxılır. Bağ dəyişikliyi audit olunmalıdır. Fayda: istifadəçiyə başqa işçinin maaş və şəxsi məlumatını göstərməmək. Bu da tarixi `approved_by` actor bərpasından fərqli işdir.

### 5.3 Scheduler, rollarla real giriş və PDF

Serverdə bir dəqiqəlik Laravel schedule trigger-i və ya idarə olunan `schedule:work` olmalıdır. Kodda hər beş dəqiqəlik transfer işi qeydiyyatı real serverdə cron-un işlədiyini sübut etmir. Server timezone-u, işlərin log-u, failure/lock və deployment restart davranışı yoxlanır. Birdən çox scheduler replika varsa shared lock və tək icra strategiyası lazımdır. Fayda: gələcək köçürmələrin vaxtında icrası və digər reminder/periodik işlərin davamlılığı.

Rollarla smoke yoxlaması həm müsbət, həm mənfi olsun: öz strukturunu görür, başqasının detal URL-i/export-u görünmür; MyHr öz işçisidir; maaş yalnız icazəli şəxslərə açılır. Admin-in “hamısı işləyir” yoxlaması məhdud rolun işlədiyini göstərmir. Fayda: həm data sızmasını, həm legitim işin dayanmasını tapmaq.

PDF məhz production Docker imicində LibreOffice və fontlarla yoxlanmalıdır. Azərbaycan hərfləri, cədvəl/səhifə keçidi, imza, footer və çoxşəxsli səhifə yoxlanır. Təsdiqlənmiş yekun PDF snapshot/hash ilə saxlanmalı, icazəsiz download bloklanmalıdır. Fayda: test mühitində düzgün, real serverdə fərqli çıxan əmri buraxmamaq. Bu canlı yoxlamalar aparılmayıb.

### 5.4 Siyasət və hüquqi/mühasibat qərarları

| Qərar | Harada tətbiq ediləcək | Niyə vacibdir və seçimlərin təsiri |
|---|---|---|
| Təyin olunmuş rəhbər məzuniyyəti təsdiqləyə bilərmi? | Leave approval route, `AddComment`/policy və testlər | Route-da təyin olunan rəhbərin ayrıca geniş HR permission-u olmadan öz mərhələsini tamamlaması praktikdir. Sərt permission seçilərsə həmin rəhbərlərə hüquq verilmədən axın dayanacaq. Təyinat, struktur və özünü təsdiq qaydaları ayrıca saxlanır. |
| Erkən bağlanan KPI kartının bonusu | `ScorecardService::closeEarly`, `BonusService`, cycle siyasəti | Tam/pro-rata/heç bonus seçimləri fərqli məbləğ verir. İşdən çıxma, transfer və texniki bağlama ayrı səbəblər kimi müəyyən edilməlidir. Təsdiqlənmiş dövrün qaydası sonradan səssiz dəyişməsin. |
| Təkrar KPI faktı toplanır, yoxsa əvəz edilir? | Import/REST connector/actual service və aggregation | Toplama satış kimi ayrı əməliyyatlara, əvəz son toplam göstəriciyə uyğun ola bilər. Eyni fakt retry ilə ikinci dəfə sayılmamalıdır. Mənbə ID-si, version və aggregation qaydası lazımdır. |
| HRM-55: şirkət profili | Plan üzrə TeConctrol LLC üçün activity/payroll regime mapping; HRM və ARBAY source-of-truth uyğunluğu | “Publik” label-inin hansı hesaplama/feature profilini göstərdiyi təsdiqlənməlidir. Özəl adın olması təkbaşına vergi kateqoriyasını sübut etmir. Səhv profil pul nəticəsinə təsir edə bilər; mühasib/HR təsdiqindən əvvəl dəyişdirilməməlidir. |
| Off-cycle kredit taksiti | PayrollCalculator və LoanService repayment/rollback | Hazır qayda taksiti regular run-da tutur. Off-cycle-da da tutsaq eyni ay ikinci tutulma riski yaranır; istisna seçilərsə qalıq/aylıq limit və dedup qaydası lazımdır. Reopen repayment-i simmetrik geri almalıdır. |
| Çoxpilləli payroll təsdiqi | PayrollRunService, ApprovalRouteResolver, audit və notification | Kim hesablayır, kim yoxlayır, kim approve/lock edir müəyyən edilir. Öz hesablamasını təsdiq etmə, əvəzləyici, gecikmə və rejection qaydaları lazımdır. Fayda: səhvin ödənişə çevrilmədən tutulması. |
| Staj/şərait/VTİSK və 2026 payroll dərəcələri | Vacation norm evaluator, position history və compensation/tax regime | “Düz 15 il”, şərait zəncirinin qırılması və vəzifə kateqoriyası üçün hüquqi cavab saxlanmalıdır. Dərəcələr effective-from ilə version-lansın; tarixi locked snapshot yenidən hesablanmasın. |

Bu hissə hüquqi dərəcə və ya bonus siyasəti seçmir. Hüquqşünas/mühasib/HR cavabı yazılı əsas, qüvvəyə minmə tarixi, nümunə input/output və təsdiqçi ilə qeydə alınmalıdır. Fayda: müxtəlif operatorun eyni hadisəyə fərqli cavab verməməsi və sonradan hesablamanın izah olunması.

## 6. Təklif olunan iş sırası və yoxlama sərhədi

Əvvəl hazır təhlükəsizlik paketinin release hazırlığı, tenant/membership/rol preview-u və read-only inventarlar. API read pilot və ARBAY şirkət rolları ayrı mərhələlər olsun. KPI write və payroll approval siyasət cavablarından sonra; webhook worker hazır olandan sonra. CSP report-only ayrıca browser matrisi ilə irəliləsin. DOCX editor ehtiyac/lisenziya/sizing qərarınadək bağlı qalsın.

Bu sənəd üçün mövcud kod oxunub və təchizatçıların rəsmi sənədləri yoxlanıb. Təklif olunan API, teams və CSP implementasiya edilməyib; document server quraşdırılmayıb. Canlı backup/migration/env/rol/token/scheduler/PDF və siyasət dəyişiklikləri edilməyib. Müştəri axınları və production performansı bu araşdırmada test edilməyib.
