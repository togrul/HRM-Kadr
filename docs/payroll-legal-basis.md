# Əmək haqqı hesablamasının hüquqi əsası — əmrdən irəli gələn ödənişlər

> Mənbə: **Azərbaycan Respublikasının Əmək Məcəlləsi** (ƏM), e-qanun.az-dakı cari
> redaksiya — <https://e-qanun.az/framework/46943> (mətn:
> <https://e-qanun.az/frameworks/46/f_46943.html>), 2026-10-09 tarixində oxunub.
> Sitatlar həmin mətndən hərfi götürülüb. Kodda bu sənədə istinad edən yerlər:
> `Payroll\Application\Services\OrderEarningsService`, `PayrollRunService`, `RetroService`,
> `LoanService`, `PayrollCalculator`, `config/payroll.php`; §4.1–§6 (11.10.2026): `DeductionCap`,
> `StatutoryEngine`, `statutory_exemptions` və rejim dərəcələri miqrasiyaları. §5–§6 üçün əlavə mənbələr:
> Vergi Məcəlləsi, «Sosial sığorta haqqında», «Tibbi sığorta haqqında», «İşsizlikdən sığorta haqqında»
> qanunlar, DVX izahları — hər bölmədə link və oxunma tarixi ilə.
>
> İşarələr: ✅ — qanunun mətni ilə təsdiqlənib; ⚠️ — qanunda açıq yazılmayıb, təcrübəyə
> və ya bizim şərhimizə əsaslanır (hüquqşünas/mühasib təsdiqi tövsiyə olunur).

---

## 1. Qeyri-iş günü (istirahət, bayram) işə cəlb — ƏM m.164

### Mətn ✅

> **Maddə 164.** İstirahət, səsvermə, iş günü hesab edilməyən bayram günləri və ümumxalq
> hüzn günü görülən işə görə əməkhaqqının ödənilməsi
>
> 1. … əməkhaqqı aşağıdakı kimi ödənilir:
> – əməyin vaxtamuzd ödənilmə sistemində gündəlik (saatlıq) tarif maaşının iki mislindən
>   aşağı olmamaqla;
> – əməyin işəmuzd ödənilmə sistemində ikiqat işəmuzd qiymətlərindən aşağı olmamaqla;
> – **aylıq maaş alan işçilərə iş aylıq iş vaxtı norması çərçivəsində görülmüşsə, maaşdan
>   əlavə gündəlik (saatlıq) vəzifə maaşı məbləğindən aşağı olmamaqla, əgər iş aylıq iş
>   vaxtı normasından artıq vaxtda görülmüşsə, maaşdan əlavə gündəlik (saatlıq) vəzifə
>   maaşının ikiqat məbləğindən aşağı olmamaqla.**
> 2. … işləmiş işçinin arzusu ilə ona əməkhaqqı əvəzinə başqa istirahət günü verilə bilər.

### Nəticələr və tətbiq

| Məsələ | Qərar | Status |
|---|---|---|
| Hansı işçi qrupu? | HRM-də maaş aylıqdır (`employee_compensations.base_amount`), ona görə m.164.1-in **üçüncü bəndi** tətbiq edilir | ✅ |
| Baza nədir? | **Vəzifə maaşı** (tarif) — əlavələr və mükafatlar bazaya daxil deyil. Kodda baza = `base_amount`; komponentlər (əlavələr) daxil edilmir | ✅ (mətn «vəzifə maaşı» deyir) |
| Norma daxilində | maaşdan əlavə **1 ×** saatlıq vəzifə maaşı | ✅ |
| Normadan artıq | maaşdan əlavə **2 ×** saatlıq vəzifə maaşı | ✅ |
| Saatlıq vəzifə maaşı necə? | aylıq vəzifə maaşı ÷ həmin ayın **fərdi iş vaxtı norması** (saat; təqvim, növbə, qısaldılmış iş vaxtı nəzərə alınmaqla — `AttendanceWorkNormService::employeeMonthNorm`) | ⚠️ Məcəllədə formula yoxdur; geniş yayılmış təcrübədir |
| «Norma çərçivəsində» nə deməkdir? | Əməkdaşın ayın norma iş günlərindən işləmədiyi (məzuniyyət, icazə/xəstəlik) qədər vaxt «normadaxili» sayılır; qalanı normadan artıq. Ezamiyyət iş vaxtıdır, normanı azaltmır. Tam işləmiş əməkdaş üçün qeyri-iş günü işi həmişə normadan artıqdır → 2 × | ⚠️ Şərhimizdir; cəmlənmiş uçot rejimində (m.96) fərqli hesablana bilər |
| Başqa istirahət günü | m.164.2 — işçinin arzusu ilə pul **əvəzinə**. Əmrdə `day_off` seçiləndə ödəniş sətri yaranmır; seçim `attendance_overtime_requests.compensation` sütununda və maliyyə feed-ində qalır | ✅ |
| Eyni gün iki əmrdə | bir dəfə ödənilir | — (məntiqi tələb) |

Düstur: `məbləğ = vəzifə maaşı × (normadaxili dəq. × 1 + normadan artıq dəq. × 2) ÷ ayın norma dəqiqələri`.
Nümunə: 1 680 AZN, 2026-11 norması 168 saat → 10 AZN/saat; tam işləmiş əməkdaşın 8 saatı → 160 AZN;
ayda bir iş günü məzuniyyətdə olmuşsa → 8 saat normadaxili → 80 AZN.

Müqayisə üçün m.165 (iş vaxtından artıq iş) saatlıq maaşın **ikiqat** məbləğini tələb edir və
m.165.3 əlavə istirahət günü ilə əvəzi **qadağan edir** — yəni əmrlə qeyri-iş günü işi m.165
yox, m.164 qaydası ilə ödənilməlidir; adi əlavə iş (mənbəyi `order` olmayan) bu sətirlə ödənilmir.

## 2. Müvəqqəti əvəzetmə — ƏM m.162

### Mətn ✅

> **Maddə 162.** Müvəqqəti əvəzetməyə görə haqqın ödənilməsi
>
> 1. Özünün əmək funksiyasını yerinə yetirməklə yanaşı müəyyən səbəbdən müvəqqəti işə
>    çıxmayan işçini əvəz edən işçiyə, **əvəz edilən işçinin tarif (vəzifə) maaşı ilə onun
>    maaşı arasındakı fərq ödənilir.**
> 2. Əvəz edilən işçinin tarif (vəzifə) maaşı əvəz edən işçinin maaşı ilə eyni və ya ondan
>    az olduqda isə **əmək haqqına əlavə müəyyən edilib verilir. Bu əlavə işçinin və
>    işgötürənin qarşılıqlı razılığı ilə müəyyən edilir.**

Əmək və Əhalinin Sosial Müdafiəsi Nazirliyi də eyni qaydanı izah edir:
<https://sosial.gov.az/en/media/news/additional-payment-is-determined-for-employees-replacing-other-employees-9262> ✅

Yaxın maddələr: m.161 (peşələrin/vəzifələrin əvəz edilməsi — əlavə kollektiv və ya əmək
müqaviləsi ilə), m.160 (müxtəlif işlər üzrə).

### Nəticələr və tətbiq

| Məsələ | Qərar | Status |
|---|---|---|
| Əvəz edilənin maaşı yüksəkdirsə | **fərq** (əvəz edilənin vəzifə maaşı − əvəz edənin maaşı) — əmrdə əvəz edilən əməkdaş seçilibsə maaşı Compensation-dan oxunur | ✅ m.162.1 |
| Bərabər və ya azdırsa | əmrdə razılaşdırılmış əlavə: əvəz edənin **öz vəzifə maaşının faizi** (əmr mətni: «vəzifə maaşının … faizi») və ya köhnə qurulumlarda aylıq sabit məbləğ | ✅ m.162.2 |
| Razılaşdırılmış əlavə fərqdən çoxdursa | çox olan ödənilir (işçi üçün daha əlverişli şərt) | ⚠️ şərhimizdir |
| Mütənasiblik | əlavə yalnız vəzifənin **faktiki icra edildiyi** günlərə: ayın norma iş günlərindən əvəzetmə müddətinə düşən və əvəz edənin məzuniyyətdə, icazədə/xəstəlikdə, ezamiyyətdə **olmadığı** iş günləri ÷ ayın norma iş günləri | ⚠️ Məcəllədə açıq formula yoxdur; «özünün əmək funksiyasını yerinə yetirməklə yanaşı … əvəz edən» ifadəsindən və təcrübədən çıxarılıb. Təqvim günü əvəzinə iş günü götürülür |
| Əvəz edilən adı yazılıb, əməkdaş seçilməyibsə | fərq hesablana bilmir → razılaşdırılmış əlavə | — |

Düstur: `aylıq = max(fərq, razılaşdırılmış)`, `məbləğ = aylıq × faktiki iş günləri ÷ ayın norma iş günləri`.
Nümunə: 2 100 × 10 % = 210; noyabrın 21 iş günündən 5-i məzuniyyətdə → 16 gün → 160 AZN.

## 3. Ləğv olunmuş əmr üzrə artıq ödənişin geri tutulması — ƏM m.175–176

### Mətn ✅

> **m.175.1** Əmək haqqından müvafiq məbləğlər bu maddə ilə müəyyən edilən hallar istisna
> edilməklə **yalnız işçinin yazılı razılığı ilə**, yaxud qanunvericiliklə nəzərdə tutulmuş
> icra sənədləri üzrə tutulur.
>
> **m.175.2 (e)** [işəgötürənin sərəncamı ilə tutulur:] mühasibat tərəfindən ehtiyatsızlıqla
> səhvən yerinə yetirilən **riyazi əməliyyatlar** nəticəsində artıq verilmiş məbləğlər;
>
> **m.175.3** … səhv riyazi hesablamalar nəticəsində düzgün hesablanmamış pulun verildiyi
> gündən **bir ay** müddətində … Bu müddət bitdikdən sonra işçidən həmin məbləğlər tutula bilməz.
>
> **m.175.5** Hesabda səhvə yol verilməsi halları istisna olmaqla, işəgötürən tərəfindən
> işçiyə artıq verilmiş əmək haqqı, o cümlədən müvafiq qanun və digər normativ hüquqi
> aktların düzgün tətbiq edilməməsi nəticəsində verilən məbləğlər **işçidən tutula bilməz.**
>
> **m.176.1** Hər dəfə əmək haqqı verilərkən tutulan bütün məbləğlərin ümumi miqdarı işçiyə
> verilməli olan əmək haqqının **iyirmi faizindən** … artıq ola bilməz.

### Nəticələr və tətbiq

Əmr ödənildikdən sonra ləğv olunanda yaranan artıq ödəniş **riyazi səhv deyil** — ona görə
işəgötürən onu öz sərəncamı ilə tuta bilməz (m.175.5); yalnız işçinin yazılı razılığı ilə
(m.175.1). Buna görə:

- `config/payroll.php` → `recover_revoked_order_pay` **standart olaraq söndürülüb**
  (`PAYROLL_RECOVER_REVOKED_ORDER_PAY=false`). Söndürülü halda retro mühərriki artıq ödənişi
  hesablayır (görünür), amma tutmur. ✅
- Yalnız əmək/kollektiv müqavilədə belə tutulmaya yazılı razılıq olduqda açılmalıdır. Açıq
  olanda tutulma növbəti adi hesablamada `retro_recovery` sətri kimi çıxır və həmin ödənişin
  **20 %**-dən çox olmur (`recovery_cap_ratio`, m.176.1); qalan hissə sonrakı aylara keçir. ✅
- ⚠️ 20 % həddi bütün tutulmaların cəminə aiddir; kod yalnız bu sətri məhdudlaşdırır, digər
  tutulmalar (kredit/avans, icra vərəqəsi) ilə birlikdə yoxlamır. Kredit tutulması işçinin
  ərizəsi ilə (m.175.6) aparıldığı üçün ayrıca qiymətləndirilməlidir — bax §4.
- ⚠️ Fərdi razılığın (hər işçi üzrə) qeydə alınması mexanizmi hələ yoxdur — parametr bütün
  qurulum üçündür.

## 4. Kredit/avans taksiti: adi, off-cycle və son haqq-hesab run-ları — ƏM m.175–176

### Əvvəlki vəziyyət

- Aylıq taksit (`employee_loans.monthly_installment`, qalan borcdan çox olmamaqla) yalnız
  **adi (regular)** run-da `loan` sətri kimi tutulur və run bağlananda (`lock`) kreditə yazılır.
- `f25169e9` commitindən sonra **off-cycle** run taksit tutmur və ödəniş yazmır — eyni ayda
  adi run artıq tutduğu üçün ikinci tutulma olardı.
- «Son haqq-hesab» run növü yox idi; işdən çıxan işçinin qalan borcu heç vaxt tutulmurdu
  (adi run yalnız aylıq taksiti tuturdu, sonrakı aylarda isə işçi run-a düşmür).
- 20 % həddi yalnız ləğv olunmuş əmr üzrə geri tutulmaya (`retro_recovery`) tətbiq olunurdu.

### Qəbul edilən qayda

| Run | Kredit | Əsas |
|---|---|---|
| `regular` | aylıq taksit (dəyişməyib) | işçinin ərizəsi/razılığı ilə müəyyən olunmuş qrafik |
| `off_cycle` (bonus, düzəliş, növbədənkənar ödəniş) | **tutulmur** | taksit ayda bir dəfədir; ikiqat tutulma olmamalıdır |
| işçi bu ay çıxır → onun son əmək haqqını ödəyən run (`regular` və ya yeni `final_settlement`) | taksit yox, **qalan borc**, amma həddə qədər | xitamda haqq-hesab; ƏM m.176.1 həddi |

- **Hədd** (`config/payroll.php` → `final_settlement_loan_cap_ratio`, env
  `PAYROLL_FINAL_SETTLEMENT_LOAN_CAP_RATIO`, standart **0.20**): həmin ödənişdə bütün
  qeyri-statutor tutulmaların cəmi (könüllü tutulma komponentləri + kredit) işçiyə verilməli
  olan əmək haqqının — ümumi hesablanmış məbləğ çıxılsın vergi və məcburi sosial
  ödənişlər — 20 %-ni keçmir (m.176.1, sitat §3-də ✅). Məbləğ qəpiyə **aşağı** yuvarlaqlaşdırılır
  ki, hədd aşılmasın. Hesablama: `LoanService::settlementAmount()`.
- **Sığmayan hissə** kreditdə qalır (`remaining` > 0, status `active`) — işçinin borcu kimi
  qeydə alınır; ayrıca (könüllü ödəniş və ya məhkəmə qaydasında) tələb oluna bilər. Sistem onu
  sonrakı run-larda avtomatik tutmur, çünki işçi artıq əmək haqqı almır.
- **İkiqat tutulma yoxdur:** çıxan işçi ya adi run-da (əmək haqqı şərti ayın sonuna qədər
  açıqdırsa), ya da `final_settlement` run-unda (şərt çıxış günü bağlanıbsa) olur — hər ikisində
  yox. Run bağlananda kreditə **payslip-dəki `loan` sətri qədər** yazılır: əvvəl hər kreditin
  taksiti, qalanı ən köhnə kreditdən başlayaraq. Run yenidən açılarsa (`reopen`) yazılan
  ödənişlər geri qaytarılır.
- `final_settlement` run-u: dövrdə `leave_work_date`-i olan və həmin günə aktiv əmək haqqı
  şərti olan, lakin adi run-a (ayın son günü aktiv şərt) düşməyən işçiləri əhatə edir; maaş
  çıxış gününə olan şərtlə hesablanır, birdəfəlik ödənişlər və əmr ödənişləri (adi run-un
  işidir) daxil edilmir.

### Statuslar və açıq məsələlər

- ✅ m.176.1 — 20 % həddi (mətn §3-də). 11.10.2026-dan **adi aylıq taksitə də** tətbiq olunur — bax §4.1.
- ✅ 50 % / 70 % hədlərinin mətni tapıldı (§4.1); icra sənədi reyestri olmadığı üçün tətbiq edilmir.
- ⚠️ Taksitin işçinin ərizəsi ilə tutulması (m.175.1 «yazılı razılıq», m.175.6) — kreditin
  rəsmiləşdirilməsi zamanı yazılı razılığın alınması fərz olunur; sistem onu saxlamır.
- ✅ `retro_recovery` (§3) artıq ayrıca 20 % hesablamır: eyni ödənişin kredit taksiti və könüllü
  tutulmaları ilə **bir ümumi** 20 % yerini bölüşür (əvvəl kredit, sonra geri tutulma).
- ⚠️ Xitam zamanı qalan borcun tam (həddsiz) tutulmasına işçinin ayrıca yazılı razılığı — qanunda
  belə istisna yoxdur (§4.1); hədd hər halda tətbiq olunur. Hüquqşünas fərqli rəy verərsə dəyişiklik
  kodda edilməlidir (konfiqurasiya həddi yalnız aşağı sala bilər).

### 4.1 Adi aylıq taksit və tutulmaların həddi (11.10.2026)

**Mənbələr** (oxunma tarixi 11.10.2026):
- Əmək Məcəlləsi, m.174–176 — <https://e-qanun.az/framework/46943> (konsolidasiya edilmiş mətn,
  fayl 08.10.2026 tarixli redaksiya).
- «İcra haqqında» Qanun, m.64–65 — e-qanun.az-da konsolidasiya edilmiş mətn açıla bilmədi; Azərbaycan
  Standartlaşdırma İnstitutunun saytındakı nüsxə oxundu:
  <https://azstand.gov.az/uploads/documents/468838918651c37b00ecdc.pdf> (PDF 11.08.2023 tarixlidir) ⚠️
  sonrakı dəyişikliklər üçün rəsmi mətnlə tutuşdurulmalıdır.
- Ali Məhkəmənin mülki və iqtisadi kollegiyalarının birgə qərarı haqqında xəbər (16.12.2022):
  <https://www.e-huquq.az/az/news/mehkeme/59037.html> — köməkçi mənbə (qərarın öz mətni deyil).

**Mətn ✅**

> **ƏM m.175.6** İşçinin ərizəsində göstərdiyi məbləğdə və müddətlərdə əmək haqqının müvafiq hissəsi
> kommunal xərclərin, bankdan aldığı ssudaların, kreditlərin və digər şəxsi borclarının ödənilməsi
> üçün tutularaq kreditorlara göndərilə bilər.
>
> **ƏM m.176.1** Hər dəfə əmək haqqı verilərkən tutulan bütün məbləğlərin ümumi miqdarı işçiyə
> verilməli olan əmək haqqının iyirmi faizindən və müvafiq qanunvericilikdə nəzərdə tutulmuş hallarda
> isə əlli faizindən artıq ola bilməz.
>
> **ƏM m.176.2** Əmək haqqından bir neçə icra sənədinə əsasən məbləğlər tutularkən, bütün hallarda
> işçinin əmək haqqının əlli faizi saxlanmalıdır.
>
> **ƏM m.176.3** … alimentlərin tutulması, sağlamlığa vurulmuş ziyanın ödənilməsi, ailəni dolandıranın
> itkisi … və cinayət nəticəsində vurulmuş ziyanın ödənilməsi zamanı … tutulmaların məbləği işçiyə
> verilməli olan əməkhaqqının 70 faizindən çox ola bilməz.
>
> **«İcra haqqında» m.64** … tutulan məbləğin (tutmanın) miqdarı vergilər tutulduqdan sonra qalan
> məbləğdən hesablanır. **m.65.1** İcra sənədi icra edilərkən … tutmaların məbləği 50 faizdən çox ola
> bilməz. **m.65.3** Alimentlərin tutulması … zamanı … 70 faizdən çox ola bilməz.

**Nəticə.** İşəgötürənin işçiyə verdiyi kredit/avansın taksiti m.175.2-nin «işəgötürənin sərəncamı
ilə» siyahısında yoxdur — yalnız işçinin yazılı razılığı (m.175.1) və ya ərizəsi (m.175.6) ilə tutulur.
Amma m.176.1 tutulmanın əsasından asılı olmayaraq «tutulan **bütün** məbləğlər»dən danışır; razılıqla
həddin aşılmasına icazə verən norma Məcəllədə yoxdur. Ali Məhkəmə də (2022) əmək haqqının 20 %-dən
artığının akseptsiz tutulmasına qabaqcadan razılıq verən müqavilə şərtini etibarsız sayıb. Ona görə:

| Qayda | Kod | Status |
|---|---|---|
| Adi run-da kredit/avans taksiti + könüllü tutulma komponentləri + retro geri tutulma birlikdə ≤ 20 % × (hesablanmış − vergi və məcburi sosial ödənişlər) | `DeductionCap::room()`, `LoanService::installmentAmount()`, `PayrollRunService::computePayslips()` | ✅ m.176.1 |
| Sığmayan hissə kreditdə qalır; növbəti ay yenə **adi taksit** tutulur (iki aylıq yox) — qrafik uzanır | `LoanService::recordRepaymentsForRun()` (`loan_repayments.scheduled_amount`) | ⚠️ «növbəti ay ikiqat tutma yoxdur» seçimdir; ikiqat tutulma onsuz da həddə dirənərdi |
| Hədd konfiq edilə bilər, amma yalnız **aşağı**: `payroll.deduction_cap_ratio` (env `PAYROLL_DEDUCTION_CAP_RATIO`, köhnə `PAYROLL_FINAL_SETTLEMENT_LOAN_CAP_RATIO` də oxunur); 0.20-dən böyük dəyər 0.20-yə sıxılır | `DeductionCap::ratio()` | ✅ qanunda razılıq istisnası yoxdur |
| Yuvarlaqlaşdırma: yer qəpiyə **aşağı** yuvarlaqlaşdırılır (hədd bir qəpik də aşılmasın) | `DeductionCap::room()` | — |
| Könüllü komponent tutulmaları (məs. həmkarlar ittifaqı) həddə sayılır, amma azaldılmır — yer qalmırsa kredit 0 olur | `PayrollCalculator::calculate()` | ⚠️ komponent tutulmalarının özünün həddə görə azaldılması (qalığın uçotu) yoxdur |
| 50 % (bir neçə icra sənədi) və 70 % (aliment və s.) | tətbiq edilmir | ⚠️ icra sənədi reyestri (borc qalığı, növbəlilik, «İcra haqqında» m.64 bazası) yoxdur — ayrıca iş |

**UI.** Payslip-də kredit sətri həddə görə azalanda adı belə olur: «Kredit/avans tutulması (plan 300,00
AZN; ƏM m.176.1 həddinə görə 127,00 AZN sonrakı aylara keçir)». «Maaş → Kredit/avans» cədvəlində
hər kredit üçün **son tutulma** (məbləğ, tarix, «… həddə görə keçdi») və **təxmini qalan ay** göstərilir,
üstdə qaydanın izahı var.

**Rəqəmli nümunə** (özəl qeyri-neft, 2026, əsas iş yeri; `LoanPayrollTest`):

| | Əvvəl | İndi |
|---|---|---|
| Hesablanmış | 1 000,00 | 1 000,00 |
| Vergi / DSMF / işsizlik / tibbi | 30 / 86 / 5 / 20 (vergi azadolmasız) | 24 / 86 / 5 / 20 (§5) |
| Verilməli olan əmək haqqı | 859,00 | 865,00 |
| 20 % həddi | tətbiq edilmirdi | 173,00 |
| Kredit taksiti (plan 300) | 300,00 | **173,00** (127,00 kreditdə qalır) |
| Ödənilən | 559,00 | 692,00 |
| 1 000 AZN-lik kredit | 4 aya bağlanırdı | iyunda 173, iyulda 173 … (≈ 6 ay) |

Əlavə könüllü tutulma 50 AZN olarsa kredit üçün yer 173 − 50 = 123 AZN-dir.

## 5. Gəlir vergisi azadolmaları — Vergi Məcəlləsi m.102 (11.10.2026)

**Mənbələr** (oxunma tarixi 11.10.2026):
- Vergi Məcəlləsi — <https://e-qanun.az/framework/46948> (mətn
  <https://e-qanun.az/frameworks/46/f_46948.html>, 18.09.2026 tarixli konsolidasiya). Qeyd:
  `framework/46947` Cinayət Məcəlləsidir, Vergi Məcəlləsi deyil.
- Vergilər (DVX) — «Qeyri-neft-qaz sektorunun özəl bölməsində fiziki şəxslərdən gəlir vergisi üzrə
  yeni güzəşt mexanizmi»: <https://www.taxes.gov.az/az/post/4461>.
- DVX, «Vergi Məcəlləsində edilmiş dəyişikliklər suallar və cavablarda» (2026), «Vergi
  daxilolmalarının … tənzimlənməsi» bölməsi, sual 1 (s.18):
  <https://www.taxes.gov.az/uploads/2026/faq/FAQ2026.pdf>.

### 5.1 m.102.1.6 — 200 manat ✅

> **102.1.6.** Fiziki şəxsin əsas iş yerində (əmək kitabçasının olduğu yerdə) hər hansı muzdlu işlə
> əlaqədar əldə edilən aylıq gəliri 2500 manatadək olduqda, 200 manat, illik gəliri 30000 manatadək
> olduqda, 2400 manat məbləğində olan hissəsi; [gəlir vergisindən azaddır]

Redaksiya tarixçəsi (VM-in 726-cı qeydi): 200/2400 manat — 1356-VQD (30.11.2018, 2019-dan); 1195-VIQD
(28.06.2024) bəndin mətnindən «(əmək kitabçasının olduğu yerdə)» sözlərini çıxarıb (konsolidasiya
edilmiş mətndə hələ görünür); **297-VIIQD (09.12.2025) bu bəndi dəyişməyib**.

> **DVX (post 4461):** «İşçinin aylıq gəliri 2500 manatadək olduqda həmin məbləğin 200 manat olan
> hissəsindən gəlir vergisinin tutulmaması ilə bağlı güzəşt qüvvədə qalır.»
>
> **DVX (FAQ 2026, s.18):** «… qeyri-dövlət sektoruna aid edilən vergi ödəyicisində işləyən fiziki
> şəxsin 2026-cı ilin yanvar ayı üzrə 2500 manatadək olan muzdlu işlə əlaqədar aylıq gəlirlərindən
> Vergi Məcəlləsinin 102-ci maddəsində müəyyən olunmuş azadolmalar nəzərə alınmaqla 3 faiz dərəcə ilə
> vergi tutulur …»

| Sual | Cavab | Status |
|---|---|---|
| Kimə? | Hər hansı muzdlu işdən gəlir alan fiziki şəxsə — mətn sektor (dövlət/özəl, neft-qaz/qeyri-neft) və ya rejimlə məhdudlaşdırmır | ✅ |
| 101.1-1 (özəl qeyri-neft güzəşt cədvəli) ilə birlikdə? | **Bəli** — DVX-nin həm xəbəri, həm FAQ-ı açıq deyir | ✅ |
| Vergidən, yoxsa bazadan? | **Bazadan**: «200 manat məbləğində olan hissəsi» azaddır → `vergi = cədvəl(gəlir − 200)` | ✅ |
| Hədd daxildirmi? | «2500 manatadək» — 2500,00 daxil, 2500,01 artıq güzəşt almır (101.1 cədvəlindəki «2500 manatadək / 2500 manatdan çox» qarşılaşdırması ilə eyni oxunuş) | ✅ |
| Hansı «aylıq gəlir» ilə yoxlanır? | Həmin işəgötürənin ayın vergi tutulan qazancları (`taxable` komponentlər; hərbidə §6.2 süzgəcindən sonra) | ⚠️ vergidən azad ödənişlərin (məs. ezamiyyət) həddə sayılmaması bizim oxunuşdur |
| Yalnız əsas iş yerində | İşçi kartında «İş yeri: əsas və ya əlavə» (`personnels.workplace_type`) `secondary` olanda güzəşt verilmir; boş və `primary` — əsas (standart: **əsas**) | ✅ m.102.1.6 + m.102.8 |
| Natamam ay (proporsiya) | 200 manat tam verilir (mətndə mütənasiblik yoxdur) | ⚠️ |
| Ayda bir dəfə | Off-cycle run (ayın ikinci ödənişi) güzəşti təkrar vermir; adi və son haqq-hesab run-u verir | ⚠️ ayın bütün ödənişlərini cəmləyib 2500-ü yoxlamaq hələ yoxdur |
| İllik variant (30 000 / 2 400) | Tətbiq edilmir — aylıq əmək haqqı uçotunda aylıq variant işlənir | ⚠️ |

**İmplementasiya.** Yeni cədvəl `statutory_exemptions` (Kompensasiya modulu; effektiv tarixli, rejim
üzrə; növlər `fixed_amount` və `taxable_components`). Seed sətri: `vm_102_1_6`, bütün rejimlər, 200
AZN, hədd 2500, yalnız əsas iş yeri. Ekranda: **Kompensasiya → Vergi/sığorta dərəcələri → Gəlir vergisi
azadolmaları** — məbləğ, hədd, «yalnız əsas iş yerində», tarix dəyişdirilə bilər (yeni sətir əlavə
olunur, əvvəlki sətir bir gün əvvəl bağlanır). Hesablama: `StatutoryEngine::reliefFor()` /
`compute()`; payslip-də gəlir vergisi sətri «Gəlir vergisi (vergidən azad olunan hissə 200,00 AZN —
VM 102.1.6)» kimi görünür.

**Keçmiş aylar dəyişmir.** Retro mühərriki kilidli payslip-ləri cari qaydalarla yenidən hesabladığı
üçün seed sətrinin `effective_from`-u miqrasiya zamanı **ən son kilidlənmiş adi run-un ayından sonrakı
ayın 1-i** götürülür (kilidli ay yoxdursa 01.01.2019). Beləliklə ödənilmiş aylar üçün avtomatik
«geri ödəniş» yaranmır. ⚠️ 01.01.2026-dan bu güzəşt verilməmiş aylar üzrə artıq tutulmuş vergi (məs.
1 100 AZN-də ayda 6 AZN) mühasib tərəfindən bəyannamə/düzəliş qaydasında həll edilməlidir.

**Nümunələr** (`tests/Feature/Payroll/IncomeTaxReliefTest.php`):

| Aylıq gəlir (əsas iş yeri) | Özəl qeyri-neft 2026 — əvvəl | indi | 2027 (5%) | 2028 (7%) | Dövlət/hərbi (14%) — indi |
|---|---|---|---|---|---|
| 1 100 | 33,00 | **27,00** = 900 × 3% | 45,00 | 63,00 | 126,00 = 900 × 14% |
| 2 400 | 72,00 | **66,00** = 2 200 × 3% | 110,00 | 154,00 | 308,00 |
| 2 500 | 75,00 | **69,00** = 2 300 × 3% | 115,00 | 161,00 | 322,00 |
| 2 501 | 75,10 | 75,10 (güzəşt yoxdur) | 125,10 | 175,10 | 350,25 |
| 3 000 | 125,00 | 125,00 | 175,00 | 225,00 | 475,00 = 350 + 500 × 25% |

Əlavə iş yerində 1 100 AZN → 33,00 (güzəşt yoxdur).

### 5.2 Digər güzəştlər — sistemdə YOXDUR (işçi üzrə güzəşt kartı lazımdır)

| Maddə | Kimə | Güzəşt | Qeyd |
|---|---|---|---|
| 102.1-1 | şəhid ailəsi (valideyn, dul arvad/ər, övlad) | aylıq vergi tutulan gəlir **800** AZN azaldılır | |
| 102.2 | Vətən Müharibəsi və Milli Qəhrəmanlar, SSRİ/Sosialist Əməyi Qəhrəmanları, Şöhrət ordeninin 3 dərəcəsi, I–II qrup müharibə əlilləri, həlak olmuş döyüşçülərin dul arvadı/əri və övladları, müharibə veteranları, Çernobıl/şüa xəstəliyi, minatəmizləyənlərin ailəsi və s. | **400** AZN | |
| 102.3 | 61–100 % funksiya pozuntusu ilə əlilliyi olanlar (müharibə əlilləri istisna), əlilliyi olan uşaqlar, belə uşağa/81–100 % əlilə baxan valideyn, ər/arvad, himayəçi, qəyyum | **200** AZN | 102.1.6 ilə eyni məbləğ, amma ayrıca güzəştdir |
| 102.4 | həlak olmuş döyüşçülərin valideynləri, vəzifə icrasında həlak olmuş dövlət qulluqçusunun valideyni və həyat yoldaşı, 20 Yanvar şəhidlərinin ailəsi, Əfqanıstana göndərilmiş hərbçilər, daimi qulluq tələb edən uşağa baxan valideyn, məcburi köçkünlər | **100** AZN | |
| 102.5 | himayəsində azı 3 nəfər olan ər/arvaddan biri | **50** AZN | |

- **102.7:** 102.1-1, 102.2, 102.3, 102.4 üzrə bir neçə hüquq olduqda **yalnız ən böyüyü** verilir.
  102.1.6 bu siyahıda yoxdur — onunla birlikdə tətbiq olunur ⚠️ (mətnin sözbəsöz oxunuşu; DVX FAQ-ı
  «102-ci maddədə müəyyən olunmuş azadolmalar nəzərə alınmaqla» deyir).
- **102.8:** güzəşt sənədlər təqdim edildiyi andan və yalnız əsas iş yerində tətbiq edilir; sənəd
  müqavilədən sonra təqdim olunubsa — müqavilənin qüvvəyə mindiyi tarixdən.
- Bunlar üçün işçi üzrə «güzəşt kartı» (növ, sənəd, tarix aralığı, əsas iş yeri) lazımdır. `statutory_exemptions`
  cədvəli rejim səviyyəsindədir — işçi səviyyəli kart ayrıca iş kimi qalır.

## 6. Dövlət qulluğu və hərbi rejimlərin dərəcələri (11.10.2026)

**Əvvəl:** `statutory_rates`-də yalnız `regime_id = null` (özəl qeyri-neft-qaz sektoru) sətirləri var
idi; `state` və `military` rejimindəki işçilərə də 3% / DSMF 10%-lik pillə / tibbi 2500-lük pillə tətbiq
olunurdu.

**Rejim işçiyə necə təyin olunur.** Kompensasiya → İşçi maaşları → maaş təyinatında «Rejim» seçimi
məcburidir (`employee_compensations.regime_id`; `military`, `state`, `private`). Hesablama run-u da
rejimə görə süzülə bilir. ⚠️ Neft-qaz sahəsində fəaliyyət göstərən **özəl** şirkət və dövlət şirkətləri
də 101.1 / m.14.3 / m.15-10.1.2 dərəcələrinə tabedir — belə işçilər `state` rejimində olmalıdır
(yaxud ayrıca «neft-qaz» rejimi açılmalıdır); `private` = qeyri-neft-qaz, qeyri-dövlət.

### 6.1 Mətnlər ✅

| Komponent | Rəsmi mətn | Mənbə |
|---|---|---|
| Gəlir vergisi | «2500 manatadək — 14 faiz; 2500 manatdan çox olduqda — 350 manat + 2500 manatdan çox olan məbləğin 25 faizi» (1-ci cədvəl; 509-IVQD ilə 2013-dən). 101.1-1 cədvəli yalnız «neft-qaz sahəsində fəaliyyəti olmayan və qeyri-dövlət sektoruna aid edilən» ödəyicilərə aiddir | VM m.101.1, 101.1-1 — <https://e-qanun.az/framework/46948> |
| DSMF | «sığortaedən … əməyin ödənişi fondunun … 22 faizi; sığortaolunan … əməkhaqqının … 3 faizi» (m.14.4 pilləsi yalnız qeyri-dövlət, qeyri-neft-qaz); hərbi qulluqçular (müddətli həqiqi xidmət istisna) m.12 ilə sığortaolunandır | «Sosial sığorta haqqında» m.14.3, 12 — <https://e-qanun.az/framework/3813> |
| İşsizlik | «sığortaedən … 0,5 faizi; sığortaolunan … 0,5 faizi»; sığortaolunan — «işəgötürənlə əmək müqaviləsi bağlamış … işçilər» | «İşsizlikdən sığorta haqqında» m.9.0, 2.0.3 — <https://e-qanun.az/framework/36310> |
| İcbari tibbi | «muzdla işləyən fiziki şəxslər, hərbi qulluqçular (müddətli həqiqi hərbi xidmət … istisna olmaqla) … 8000 manatadək olan hissəsindən 2 faiz, 8000 manatdan yuxarı olan hissəsindən 0,5 faiz» (hər tərəf) | «Tibbi sığorta haqqında» m.15-10.1.2 — <https://e-qanun.az/framework/80> |
| Hərbçinin vergi bazası | «hərbi qulluqçulara … ödənilən bütün növ ödənişlər (vəzifə və hərbi (xüsusi) rütbəyə görə maaşlar istisna olmaqla)» azaddır | VM m.102.1.14.15 |
| Əmək Məcəlləsinin şamil olunmaması | «Aşağıdakı şəxslərə bu Məcəllə şamil edilmir: a) hərbi qulluqçulara» | ƏM m.6 — <https://e-qanun.az/framework/46943> |

Qeyd: tapşırıqdakı «8000-ə qədər 14 %, yuxarı 1 120 + 25 %» köhnə məlumatdır — cari 1-ci cədvəldə
hədd **2500**, sabit hissə **350** manatdır.

### 6.2 Tətbiq

Miqrasiya `2026_10_11_130100_seed_state_and_military_statutory_rates.php` — yalnız həmin rejim +
komponent + ödəyən üçün heç bir sətir yoxdursa yazır (mühasibin sətirlərinə toxunmur), effective_from =
01.01.2026, amma ən son kilidli aydan sonra (§5.1 ilə eyni səbəb):

| Rejim | Gəlir vergisi | DSMF işçi / işəgötürən | İşsizlik | Tibbi (hər tərəf) |
|---|---|---|---|---|
| `state` | 2500-dək 14%, yuxarı 25% (+102.1.6) | 3% / 22% | 0,5% / 0,5% | 8000-dək 2%, yuxarı 0,5% |
| `military` | eyni + **yalnız `base` və `rank_supplement`** vergiyə cəlb olunur (`statutory_exemptions`: `vm_102_1_14_15`, növ `taxable_components`) | 3% / 22% | **0% / 0%** ⚠️ | 8000-dək 2%, yuxarı 0,5% |

**Nümunələr** (`IncomeTaxReliefTest`):
- Dövlət qulluqçusu, 1 100 AZN: vergi 126 (özəl cədvəllə 27), DSMF 33 / 242, işsizlik 5,5 / 5,5, tibbi
  22 / 22 → ödənilən **913,50** (əvvəl özəl dərəcələrlə və azadolmasız 943,50), işəgötürən xərci 269,50.
- Dövlət qulluqçusu, 10 000 AZN: vergi 350 + 7 500 × 25% = **2 225** (özəl cədvəllə 905), DSMF 300 /
  2 200, tibbi 160 + 10 = 170.
- Hərbi qulluqçu: vəzifə maaşı 1 000 + rütbə 300 + dil bilik əlavəsi 200 = 1 500. Vergi bazası 1 300 →
  102.1.6 ilə 1 100 × 14% = **154**; DSMF 45 / 330, tibbi 30 / 30, işsizlik yoxdur.

## 7. Yoxlanılmamış / açıq qalan məsələlər

1. ⚠️ Saatlıq vəzifə maaşının ayın fərdi normasına bölünməklə tapılması — rəsmi izahat
   (Nazirlik məktubu və ya metodik göstəriş) tapılmadı.
2. ⚠️ «Aylıq iş vaxtı norması çərçivəsində» anlayışının məzuniyyət/xəstəlik günləri ilə
   əlaqəsi — şərhimizdir; cəmlənmiş uçot (m.96) olan əməkdaşlar üçün ayrıca qayda lazım ola bilər.
3. ⚠️ Əvəzetmə əlavəsinin iş günlərinə mütənasib bölünməsi — təcrübədir, Məcəllədə formula yoxdur.
   Davamiyyətdə qeydə alınmamış (səbəbsiz) qayıblar hələ çıxılmır, yalnız məzuniyyət, gün üzrə
   icazə/xəstəlik və ezamiyyət çıxılır.
4. ⚠️ Kollektiv və ya əmək müqaviləsi m.164 və m.162.2 üzrə daha yüksək məbləğ müəyyən edə
   bilər — hazırda yalnız qanuni minimum (və əmrdəki razılaşma) tətbiq olunur.
5. ⚠️ **Hərbi rejimdə işsizlik sığortası 0%** — «İşsizlikdən sığorta haqqında» m.2.0.3 (sığortaolunan
   = əmək müqaviləsi bağlamış işçi) və ƏM m.6.a (Məcəllə hərbi qulluqçulara şamil edilmir) əsasında
   çıxarılıb; DSMF/Məşğulluq agentliyinin açıq izahı tapılmadı — mühasib təsdiqi lazımdır.
6. ⚠️ **Hərbi pul təminatında vergiyə cəlb olunan komponentlər** `base` (vəzifə maaşı) və
   `rank_supplement` (rütbə maaşı) kimi götürülüb (VM m.102.1.14.15). Kataloqda başqa adla saxlanan
   vəzifə/rütbə maaşı varsa `statutory_exemptions.component_codes` düzəldilməlidir. DSMF bazası üçün
   «pul təminatına daxil edilməyən ödənişlər» («Sosial sığorta haqqında» m.15) komponent səviyyəsində
   `affects_social` bayrağı ilə idarə olunur — rejimə görə ayrıca süzgəc yoxdur.
7. ⚠️ VM m.102.1.6 «aylıq gəlir 2500 manatadək» şərti həmin run-un vergi tutulan qazancı ilə yoxlanır;
   eyni ayda off-cycle ödəniş olarsa ayın cəmi ilə yenidən yoxlanmır (güzəşt off-cycle-da verilmir,
   amma adi run-da verilmiş güzəşt geri alınmır).
8. ⚠️ 01.01.2026-dan bu dəyişikliyə qədər kilidlənmiş aylarda verilməmiş 102.1.6 güzəşti və özəl
   dərəcə ilə hesablanmış dövlət/hərbi işçilərin vergi/DSMF fərqi avtomatik düzəldilmir (retro
   yaratmamaq üçün seed sətirləri son kilidli aydan sonra qüvvəyə minir) — mühasib bəyannamə
   düzəlişi ilə həll etməlidir.
9. ⚠️ VM m.102.1-1, 102.2–102.5 güzəştləri (800/400/200/100/50 AZN) üçün işçi üzrə güzəşt kartı yoxdur (§5.2).
10. ⚠️ 50 % / 70 % tutulma hədləri (icra sənədləri, aliment) icra sənədi reyestri olmadığı üçün
    tətbiq edilmir (§4.1); «İcra haqqında» Qanunun mətni e-qanun.az-dan deyil, azstand.gov.az
    nüsxəsindən oxunub.
