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

### 5.2 Digər güzəştlər — 11.10.2026-dan işçi üzrə güzəşt kartı ilə tətbiq olunur (§9)

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

### 6.3 Hərbi qulluqçular — yoxlama (11.10.2026, ikinci mərhələ)

**Mənbələr** (oxunma 11.10.2026): «İşsizlikdən sığorta haqqında» <https://e-qanun.az/framework/36310>; «Sosial
sığorta haqqında» <https://e-qanun.az/framework/3813>; «Əmək pensiyaları haqqında» <https://e-qanun.az/framework/11566>;
«Hərbi qulluqçuların statusu haqqında» <https://e-qanun.az/framework/6998>; Əmək Məcəlləsi
<https://e-qanun.az/framework/46943>; Vergi Məcəlləsi <https://e-qanun.az/framework/46948>.

**(a) İşsizlikdən sığorta — 0 % saxlanılır ⚠️**

> **m.2.0.3** sığortaolunan – … işəgötürənlə **əmək müqaviləsi bağlamış** və işsizlikdən sığorta olunan işçilər;
> **m.2.0.13** işçi – işəgötürənlə fərdi qaydada yazılı əmək müqaviləsi bağlayaraq … çalışan fiziki şəxs;
> **m.1.3** Bu Qanun siyasi vəzifə tutan şəxslərə şamil edilmir.
> **ƏM m.6** Aşağıdakı şəxslərə bu Məcəllə şamil edilmir: a) hərbi qulluqçulara; …

Qanunda hərbi qulluqçuların adı ilə nə daxil edilməsi, nə də çıxarılması yoxdur; dövlət qulluqçuları isə açıq
əhatə olunur (m.13.1.2). Hərbi qulluq əmək müqaviləsi ilə deyil, hərbi qulluq haqqında qanunvericiliklə keçildiyi
üçün nəticə — **0 %** (`statutory_rates`, `military`, `unemployment`). Bu, mətnin açıq istisnası deyil, sistemli
şərhdir: DSMF/Məşğulluq Agentliyinin rəsmi izahı tapılmadı — **mühasib təsdiqi tələb olunur** (xüsusən
kontrakt əsasında hərbi qulluqçular üçün).

**(b) Gəlir vergisi — VM m.102.1.14.15 ✅ (kod düzgün idi)**

> **102.1.14.15.** hərbi qulluqçulara, hakimlərə, prokurorluq orqanlarının prokurorluq işçilərinə və prokurorluq
> işçiləri olmayan hərbi qulluqçularına, hüquq mühafizə … orqanlarının … xüsusi rütbəsi olan əməkdaşlarına ödənilən
> bütün növ ödənişlər (**vəzifə və hərbi (xüsusi) rütbəyə görə maaşlar istisna olmaqla**) …

Kataloqla tutuşdurma: `base` (hesablamada vəzifə maaşı sətri) və `rank_supplement` («Rütbə əlavəsi» — hərbi
qulluqçu üçün **hərbi rütbəyə görə maaş** mənasındadır) vergiyə cəlb olunur; qalan bütün qazanclar — staj,
zərərlilik, məxfilik, dil əlavəsi, birdəfəlik mükafat, əmr ödənişləri (istirahət günü işi, əvəzetmə) — azaddır.
Uyğunsuzluq tapılmadı. Komponent səviyyəsində məntiq: komponentin `taxable` bayrağı **ümumi** qaydadır; hərbi
rejimdə əlavə olaraq `statutory_exemptions` (`vm_102_1_14_15`, `taxable_components`) siyahısı tətbiq olunur —
bazaya yalnız həm `taxable`, həm də siyahıda olan kod düşür. Kompensasiya → Kataloq-da hər qazanc komponentində
«Hərbçidə vergiyə cəlb olunur / vergidən azad» nişanı görünür (mənbə — həmin siyahı; ayrıca bayraq yaradılmadı ki,
iki həqiqət mənbəyi olmasın). ⚠️ Hərbi rütbə maaşı kataloqda başqa kodla saxlanırsa siyahı «Vergi/sığorta
dərəcələri» ekranında düzəldilməlidir. Qeyd: 102.1.14.16 («bəzi kateqoriyalı hərbi qulluqçuların xüsusi xidmət
şəraitinə görə … əlavə») 2006-cı ildə çıxarılıb (VM qeydi 742).

**(c) DSMF bazası — «pul təminatı» ✅ (düzəldildi)**

> **«Sosial sığorta haqqında» m.12** … hərbi qulluqçular və xüsusi rütbəli şəxslər (müddətli həqiqi hərbi xidmət
> hərbi qulluqçuları istisna olmaqla) … məcburi dövlət sosial sığortası ilə əhatə olunurlar.
> **m.14.3** … sığortaedən … 22 faizi; … sığortaolunan … 3 faizi. (m.14.4 pilləsi yalnız neft-qaz sahəsində
> fəaliyyəti olmayan qeyri-dövlət sektoru üçündür.)
> **m.15** [haqq bütün əmək ödənişlərindən hesablanır, istisnalar sırasında:] «hərbi qulluqçulara və xüsusi rütbəli
> şəxslərə verilən və «Əmək pensiyaları haqqında» Azərbaycan Respublikasının Qanunu ilə müəyyən edilən təminat
> xərcliyinə (pul təminatına) daxil edilməyən ödənişlər»;
> **«Əmək pensiyaları haqqında» m.1.0.5** təminat xərcliyi (pul təminatı) – hərbi qulluqçuların … aylıq vəzifə
> maaşından, rütbə maaşından, xidmət illərinə görə əlavədən, ərzaq payı əvəzinə verilən pul kompensasiyasından,
> vəzifə maaşından hesablanan digər əlavələrdən (büdcədənkənar vəsaitlər hesabına hesablanan əlavələr və digər
> ödənişlər istisna olmaqla), artımlardan ibarət olan məbləğdir;

| | Əvvəl | İndi |
|---|---|---|
| Dərəcə | 22 % / 3 % | eyni ✅ |
| Baza | bütün `affects_social` qazanclar (birdəfəlik mükafat və əmr ödənişləri də) | yalnız pul təminatı: `base`, `rank_supplement`, `seniority`, `hazard`, `secrecy`, `language` (miqrasiya `2026_10_11_150300`, `statutory_exemptions` `military_dsmf_pay_allowance`, növ `taxable_components`, komponent `dsmf`) |

Mühərrik: `StatutoryEngine::socialBases()` — sosial bazalı komponent üçün `taxable_components` qaydası varsa həmin
komponentin bazası ayrıca hesablanır. Nümunə (`MilitaryPayStatutoryTest`): vəzifə 1 000 + rütbə 300 + dil 200 +
birdəfəlik mükafat 500 → vergi 154,00; DSMF bazası 1 500 → 45,00 / 330,00 (əvvəl 2 000 → 60,00 / 440,00).
⚠️ Mühasib təsdiqi: (1) «ərzaq payı əvəzinə pul kompensasiyası» kataloqda yoxdur — varsa siyahıya əlavə edilməlidir;
(2) büdcədənkənar vəsait hesabına olan əlavələr siyahıdan çıxarılmalıdır; (3) icbari tibbi sığortanın hərbçi
üçün bazası bu yoxlamada oxunmadı — tibbi sığorta hələ ümumi sosial bazadan hesablanır.

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
5. ⚠️ **Hərbi rejimdə işsizlik sığortası 0%** (11.10.2026 yenidən oxundu — §6.3(a), açıq istisna yoxdur, sistemli şərh) — «İşsizlikdən sığorta haqqında» m.2.0.3 (sığortaolunan
   = əmək müqaviləsi bağlamış işçi) və ƏM m.6.a (Məcəllə hərbi qulluqçulara şamil edilmir) əsasında
   çıxarılıb; DSMF/Məşğulluq agentliyinin açıq izahı tapılmadı — mühasib təsdiqi lazımdır.
6. ✅ **Hərbi pul təminatında vergiyə cəlb olunan komponentlər** — 102.1.14.15 mətni ilə tutuşduruldu, DSMF bazası «pul təminatı» ilə məhdudlaşdırıldı (§6.3(b)–(c)). Əvvəlki qeyd: `base` (vəzifə maaşı) və
   `rank_supplement` (rütbə maaşı) kimi götürülüb (VM m.102.1.14.15). Kataloqda başqa adla saxlanan
   vəzifə/rütbə maaşı varsa `statutory_exemptions.component_codes` düzəldilməlidir. DSMF bazası üçün
   «pul təminatına daxil edilməyən ödənişlər» («Sosial sığorta haqqında» m.15) komponent səviyyəsində
   `affects_social` bayrağı ilə idarə olunur — rejimə görə ayrıca süzgəc yoxdur.
7. ⚠️ VM m.102.1.6 «aylıq gəlir 2500 manatadək» şərti həmin run-un vergi tutulan qazancı ilə yoxlanır;
   eyni ayda off-cycle ödəniş olarsa ayın cəmi ilə yenidən yoxlanmır (güzəşt off-cycle-da verilmir,
   amma adi run-da verilmiş güzəşt geri alınmır).
8. ✅ (§11 — «Vergi düzəliş hesabatı») 01.01.2026-dan bu dəyişikliyə qədər kilidlənmiş aylarda verilməmiş 102.1.6 güzəşti və özəl
   dərəcə ilə hesablanmış dövlət/hərbi işçilərin vergi/DSMF fərqi avtomatik düzəldilmir (retro
   yaratmamaq üçün seed sətirləri son kilidli aydan sonra qüvvəyə minir) — mühasib bəyannamə
   düzəlişi ilə həll etməlidir.
9. ✅ (§9 — güzəşt kartı) VM m.102.1-1, 102.2–102.5 güzəştləri (800/400/200/100/50 AZN) üçün işçi üzrə güzəşt kartı yoxdur (§5.2).
10. ✅ (§10 — icra sənədləri reyestri) 50 % / 70 % tutulma hədləri (icra sənədləri, aliment) icra sənədi reyestri olmadığı üçün
    tətbiq edilmir (§4.1); «İcra haqqında» Qanunun mətni e-qanun.az-dan deyil, azstand.gov.az
    nüsxəsindən oxunub.

## 8. Şirkətin vergi sektoru və standart rejim (11.10.2026)

**Mənbələr** (oxunma tarixi 11.10.2026):
- Vergi Məcəlləsi m.101.1, 101.1-1, 101.1-4 — <https://e-qanun.az/framework/46948> (fayl
  <https://e-qanun.az/frameworks/46/f_46948.html>, Word faylı 18.09.2026; ən son dəyişdirici qanun 14.07.2026 № 470-VIIQD).
- Nazirlər Kabinetinin 18 fevral 2019-cu il tarixli 56 nömrəli Qərarı «Neft-qaz sahəsində fəaliyyətin və
  qeyri-dövlət sektorunun Meyarları» — DVX: <https://www.taxes.gov.az/az/post/321>; imzalı mətnin PDF-i:
  <https://audit.gov.az/uploads/nk_qerar_2019.pdf>. Hər iki nüsxədə dəyişiklik qeydi yoxdur.

### 8.1 Mətn ✅

> **VM m.101.1-1.** Neft-qaz sahəsində fəaliyyəti olmayan və qeyri-dövlət sektoruna aid edilən vergi
> ödəyicilərində işləyən fiziki şəxslərin muzdlu işdən aylıq gəlirlərindən vergi aşağıdakı dərəcələrlə tutulur: …
>
> **VM m.101.1-4.** Bu Məcəllənin məqsədləri üçün neft-qaz sahəsində fəaliyyətin və qeyri-dövlət sektorunun
> meyarları müvafiq icra hakimiyyəti orqanının müəyyən etdiyi orqan (qurum) tərəfindən müəyyən edilir.

VM m.13.2-də (anlayışlar) «neft-qaz sahəsində fəaliyyət», «dövlət sektoru» anlayışı **yoxdur** — meyarlar
NK-nın 56 nömrəli Qərarındadır (2019-01-01-dən tətbiq olunur; eyni meyarlar «Sosial sığorta haqqında» Qanunun
m.14.7-si üçün də tətbiq edilir):

> **2.1.1.** Azərbaycan Respublikasının Dövlət Neft Şirkəti və onun strukturuna daxil olan müəssisələr, habelə
> hasilatın pay bölgüsü haqqında, əsas boru kəməri haqqında və digər bu qəbildən olan sazişlər üzrə podratçılar
> və əməliyyat şirkətləri fəaliyyət növündən asılı olmayaraq tam tərkibdə;
> **2.1.2.1.** [subpodratçılar] … əvvəlki təqvim ili ərzində əldə olunan illik gəlirlərin … həcmi məcmu illik
> gəlirlərin 50 faizindən çox olduğu təqdirdə; **2.1.2.2.** il ərzində yeni yaradılacaq subpodratçılar … 50 faizdən
> çox olduğu təqdirdə, növbəti ayın 1-dən etibarən təqvim ilinin sonunadək güzəşt hüququnu itirir …
>
> **2.2.** Qeyri-dövlət sektoru – dövlət adından yaradılan publik hüquqi şəxslər, büdcə təşkilatları, dövlət
> büdcəsindən və dövlətə məxsus digər fondlardan maliyyələşən digər orqan və qurumlar, habelə səhmlərinin və ya
> paylarının 51 və daha artıq faizi birbaşa və ya dolayısilə dövlətə məxsus olan hüquqi şəxslər istisna olmaqla,
> … hüquqi şəxslər və vergi ödəyicisi olan fiziki şəxslər.

**Nəticə.** Sektor işçinin deyil, **işəgötürənin** xüsusiyyətidir. Neft-qaz sahəsində fəaliyyəti olan özəl
şirkət də, dövlət sektoruna aid olan şirkət də ümumi qaydalara tabedir: VM m.101.1 1-ci cədvəl (2500-dək 14%,
yuxarı 350 + 25%), DSMF m.14.3 (22/3), tibbi sığorta 8000-lük pillə (§6). Sistemdə bu `state` rejimidir; ad
«Dövlət və neft-qaz sektoru» oldu (`private` — «Özəl qeyri-neft-qaz sektoru»; miqrasiya yalnız seed adını
dəyişir).

### 8.2 Tətbiq

| Əvvəl | İndi |
|---|---|
| Rejim yalnız işçinin maaş təyinatında seçilirdi; işə qəbul qaralaması (`createDraftForHire`) və əmrlə maaş dəyişikliyində rejim yoxdursa **sıralamada ilk aktiv rejim** — `military` (sort = 1) — götürülürdü | Şirkət sektoru: `private_non_oil` → `private`, `oil_gas` → `state`, `state` → `state`, `military` → `military` (`CompanySectorSettings`). Yeni təyinat formu, işə qəbul qaralaması və əmrlə maaş dəyişikliyi bu standartı alır |
| — | Sektor seçilməyibsə quraşdırma profilindən (APP_TYPE, `config/profiles.php`; Admin → «Publik» profili — HRM-55): `military` → hərbi, `public` → dövlət, qalanları → özəl qeyri-neft-qaz |
| — | Sektor `settings` cədvəlində (`Payroll company sector`) saxlanır; dəyişiklik səbəblə və audit jurnalına (`company_sector_changed`) yazılır |

**Ekran:** Kompensasiya → **Rejim və sektor**: sektor seçimi (NK 56 meyarlarının qısa izahı ilə, təsdiq modalı),
«Rejimi şirkət standartından fərqli olan işçilər» siyahısı (struktur görünürlüyü ilə), toplu düzəliş.

### 8.3 Mövcud işçilər — toplu düzəliş

- Avtomatik dəyişmə **yoxdur**: sektor dəyişəndə yalnız yeni təyinatların standartı dəyişir.
- Toplu düzəliş (`RegimeAlignmentService::realign`): seçilmiş işçilər, qüvvəyə minmə tarixi (**ayın 1-i**),
  məcburi səbəb, təsdiq modalı. Hər işçi üçün **yeni effektiv təyinat** yaranır (eyni baza, valyuta, pillə,
  komponent sətirləri, yalnız rejim fərqli), köhnəsi bir gün əvvəl bağlanır; audit — `regime_realigned`
  (köhnə/yeni rejim, tarix, səbəb, sektor).
- Tarix işçinin **son kilidlənmiş ayından sonra** olmalıdır; əks halda işçi «keçirilmədi» siyahısına düşür
  (səbəb ekranda). Gələcək tarixli başqa təyinatı olan işçi də keçirilmir (onu bağlamamaq üçün).
- Kilidlənmiş aylar köhnə təyinatla qalır; retro mühərriki də köhnə təyinatı görür — avtomatik «geri ödəniş»
  yaranmır. Keçmiş aylar üzrə fərq — §11.

**Nümunə** (`CompanyRegimeTest`, `TaxAdjustmentReportTest`): neft-qaz subpodratçısı, işçi səhvən özəl rejimdə,
1 100 AZN, iyun kilidli → 01.07.2026-dan `state`. İyun üçün düzgün: vergi (1100 − 200) × 14% = 126 (tutulmuş 33),
DSMF işçi 3% = 33 (tutulmuş 96), DSMF işəgötürən 22% = 242 (ödənilmiş 179).

⚠️ **Mühasib/hüquqşünas təsdiqi:** (1) şirkətin NK 56 üzrə statusu (xüsusən subpodratçının 50% həddi il
ərzində dəyişə bilər — 2.1.2.2-yə görə status növbəti ayın 1-dən dəyişir; sistem bunu avtomatik izləmir,
sektor əl ilə dəyişdirilməli və toplu düzəliş edilməlidir); (2) bir quraşdırmada bir şirkət fərz olunur —
çox şirkətli quraşdırma üçün sektor struktur səviyyəsində saxlanmalıdır.

## 9. İşçi üzrə gəlir vergisi güzəşti kartı — VM m.102.1-1, 102.2–102.8 (11.10.2026)

**Mənbə:** Vergi Məcəlləsi — <https://e-qanun.az/framework/46948> (yuxarıdakı redaksiya), oxunma 11.10.2026.

### 9.1 Mətnlər ✅

> **102.1-1.** şəhid statusu almış şəxslərin valideynlərinin, dul arvadlarının (ərlərinin) və övladlarının hər
> hansı muzdlu işdən vergi tutulmalı olan aylıq gəliri 800 manat məbləğində azaldılır.
>
> **102.2.** Aşağıdakı fiziki şəxslərin … aylıq gəliri 400 manat məbləğində azaldılır: 102.2.1 Vətən Müharibəsi
> Qəhrəmanları; 102.2.1-1 Milli Qəhrəmanlar; 102.2.2 Sovet İttifaqı və Sosialist Əməyi Qəhrəmanları; 102.2.3 Şöhrət
> ordeninin üç dərəcəsi; 102.2.4 I və II qrup müharibə ilə əlaqədar əlilliyi olan şəxslər; 102.2.5 həlak olmuş,
> yaxud sonralar vəfat etmiş döyüşçülərin (şəhid statusu almış şəxslər istisna olmaqla) dul arvadları (ərləri) və
> övladları; 102.2.6 1941–1945-ci illərdə arxa cəbhə; 102.2.7 müharibə veteranları; 102.2.8 Çernobıl, şüa xəstəliyi;
> 102.2.9 minatəmizləmə zamanı həlak olanların valideynləri, dul arvadları (ərləri) və övladları (27.04.2026
> № 396-VIIQD ilə əlavə).
>
> **102.3.** Orqanizmin funksiyalarının 61–100 faiz pozulmasına görə əlilliyi müəyyən edilmiş şəxslərin (müharibə ilə
> əlaqədar … istisna olmaqla), əlilliyi olan uşaqların, daimi qulluq tələb edən əlilliyi olan uşağa və ya … 81–100
> faiz pozulmasına görə əlilliyi müəyyən edilmiş şəxsə baxan və onunla birlikdə yaşayan valideynlərdən birinin …,
> arvadın (ərin), himayəçinin və ya qəyyumun … aylıq gəliri 200 manat məbləğində azaldılır.
>
> **102.4.** … 100 manat: 102.4.1 həlak olmuş döyüşçülərin valideynləri, vəzifə icrasında həlak olmuş dövlət
> qulluqçularının valideynləri və arvadları (ərləri) (təkrar nikaha girməyiblərsə); 102.4.2 20 Yanvar və ərazi
> bütövlüyünün müdafiəsi zamanı həlak olanların ailəsi; 102.4.3 Əfqanıstana … göndərilmiş hərbi qulluqçular;
> 102.4.4 daimi qulluq tələb edən uşağa və ya I dərəcə əlilliyi olan şəxsə baxan …; 102.4.5 məcburi köçkünlər …
>
> **102.5.** … himayəsində azı üç nəfər, o cümlədən gündüz təhsil alan 23 yaşınadək şagirdlər və tələbələr olan ər
> və ya arvaddan birinin vergi tutulmalı olan aylıq gəliri 50 manat məbləğində azaldılır. … uşağın doğulduğu və
> himayədə olanın himayəyə götürüldüyü aydan başlayaraq azaldılır. … sayı azaldıqda … azaldığı aydan sonrakı aydan
> başlayaraq … xitam verilir. (102.6 — kimlər himayədə sayılmır.)
>
> **102.7.** Fiziki şəxsin bu Məcəllənin 102.1-1-ci, 102.2-ci, 102.3-cü və 102.4-cü maddələri üzrə güzəşt hüququ
> olduqda, ona bu güzəştlərdən biri, məbləğcə ən böyük olanı verilir.
>
> **102.8.** Əmək haqqından tutulan vergi hesablanarkən fiziki şəxslərin bu maddədə sadalanan vergi güzəştləri
> hüququ müvafiq icra hakimiyyəti orqanı tərəfindən müəyyənləşdirilmiş sənədlərin təqdim edildiyi andan tətbiq edilir
> və yalnız fiziki şəxsin əsas iş yerində … həyata keçirilir. Fiziki şəxsin … statusu olduğu, lakin … sənədləri
> əmək müqaviləsi (kontraktı) hüquqi qüvvəyə mindikdən sonra təqdim etdiyi halda vergi güzəşti həmin fiziki şəxsin
> əmək müqaviləsi (kontraktı) hüquqi qüvvəyə mindiyi tarixdən hesablanır.

Qeyd: konsolidasiya edilmiş mətndə 102.1.6 və 102.8-də «(əmək kitabçasının olduğu yerdə)» hələ görünür, amma
28.06.2024 № 1195-VIQD ilə çıxarılıb (VM-in 726 və 790-cı qeydləri) — qüvvədə olan mətn «əsas iş yerində»dir.

### 9.2 Birləşmə qaydası

| Sual | Cavab | Status |
|---|---|---|
| 102.1-1/102.2/102.3/102.4 bir neçəsi | yalnız məbləğcə ən böyüyü (102.7) | ✅ mətn |
| 102.1.6 (200, ≤ 2500) ilə | 102.7-nin siyahısında **yoxdur** → üstəgəl verilir (məs. 61–100% əlil, gəlir ≤ 2500: 200 + 200) | ⚠️ siyahının sözbəsöz oxunuşu; DVX FAQ 2026-da bu barədə izah yoxdur |
| 102.5 (50) ilə | 102.7-nin siyahısında yoxdur → üstəgəl | ⚠️ eyni |
| 102.1.6-nın 2500 həddi | kartların çıxılmasından **əvvəlki** aylıq vergi tutulan gəlirlə yoxlanır | ⚠️ |
| Vergidən, yoxsa bazadan | bazadan: «aylıq gəliri … azaldılır» | ✅ |
| Hansı iş yerində | yalnız əsas iş yerində (`personnels.workplace_type ≠ secondary`) | ✅ 102.8 |
| Off-cycle ödəniş | verilmir (aylıq güzəşt ayda bir dəfə — §5.1 ilə eyni) | ⚠️ |
| Natamam ay | kart ayın hər hansı günü qüvvədədirsə tam məbləğ | ⚠️ 102.5 «aydan başlayaraq» deyir; digərləri üçün mətn yoxdur |

### 9.3 Tarixlər — VM 102.8

- `legal_from` (qanuni başlanğıc): status işə qəbul (əmək müqaviləsinin qüvvəyə minməsi — `join_work_date`)
  tarixində və ya ondan əvvəl var idisə → **işə qəbul tarixi**; status sonra yaranıbsa → **sənədlərin təqdim
  edildiyi tarix** (statusdan tez olmayaraq).
- `payroll_from` (maaşda tətbiq): `legal_from`, amma işçinin **son kilidlənmiş ayından sonra**. Aradakı (ödənilmiş)
  aylar avtomatik yenidən hesablanmır — ekranda xəbərdarlıq çıxır, fərq §11 hesabatında görünür.
- `valid_to`: sənədin müddəti və ya statusun itirilməsi. «Güzəşti bitir» kilidli aya düşən tarixi qəbul etmir;
  kilidli ayda tətbiq olunmuş kart silinmir.

### 9.4 Tətbiq

- Cədvəl `employee_tax_reliefs` (kateqoriya, dəqiq bənd/izah, sənəd nömrəsi/tarixi/verən orqan, **fayl — özəl
  disk** `local`, `tax-reliefs/<tabel>`; açılış `compensation.tax-relief.document` route-u ilə, yalnız
  `show-compensation` + struktur görünürlüyü), status/təqdim/son tarixlər, `legal_from`, `payroll_from`. Audit —
  Spatie activitylog (`compensation`).
- Məbləğlər kodda deyil: `statutory_exemptions`, yeni növ `personal` (kodlar `vm_102_1_1` 800, `vm_102_2` 400,
  `vm_102_3` 200, `vm_102_4` 100, `vm_102_5` 50; effektiv tarixli, mühasib dəyişə bilər).
- Hesablama: `StatutoryEngine::reliefFor()` — 102.1.6 + max(102.1-1/102.2/102.3/102.4) + 102.5; payslip sətri
  «Gəlir vergisi (vergidən azad olunan hissə 600,00 AZN — VM 102.1.6, VM 102.2)».
- Ekran: Kompensasiya → **Vergi güzəştləri** (işçi seçilir, kart əlavə et / bitir / sil).

**Nümunələr** (`tests/Feature/Payroll/TaxReliefCardTest.php`, özəl qeyri-neft 2026, əsas iş yeri):

| Gəlir | Kartlar | Azad olunan | Vergi (əvvəl → indi) |
|---|---|---|---|
| 1 100 | — | 200 | 27,00 → 27,00 |
| 1 100 | 102.3 | 200 + 200 | 27,00 → **21,00** |
| 1 100 | 102.2 + 102.3 | 200 + 400 | 27,00 → **15,00** |
| 1 100 | 102.2 + 102.3 + 102.5 | 200 + 400 + 50 | 27,00 → **13,50** |
| 1 100 | 102.1-1 + 102.2 | 200 + 800 | 27,00 → **3,00** |
| 3 000 | 102.2 | 400 (102.1.6 yoxdur) | 125,00 → **85,00** |
| 1 100, dövlət/neft-qaz | 102.3 | 400 | 126,00 → **98,00** |
| 1 100, əlavə iş yeri | 102.2 | 0 | 33,00 → 33,00 |

⚠️ **Təsdiq tələb edən:** 102.1.6 və 102.5-in 102.7 ən böyük güzəşti ilə toplanması; 102.8-də «müvafiq icra
hakimiyyəti orqanı tərəfindən müəyyənləşdirilmiş sənədlər»in siyahısı (sistem sənəd növünü yoxlamır, yalnız
qeyd edir); natamam ay qaydası; 102.5 üçün himayədəkilərin sayının izlənməsi (sistem yalnız kartın tarixlərini bilir).

## 10. İcra sənədləri reyestri və 50 % / 70 % hədləri (11.10.2026)

**Mənbələr** (oxunma 11.10.2026):
- Əmək Məcəlləsi m.174–176 — <https://e-qanun.az/framework/46943> (rəsmi konsolidasiya).
- «İcra haqqında» Qanun — e-qanun.az-da framework id tapılmadı; **qeyri-rəsmi nüsxə** oxundu:
  <https://azstand.gov.az/uploads/documents/468838918651c37b00ecdc.pdf> (son dəyişiklik qeydi 2023). 2025–2026-cı il
  dəyişiklikləri (189-VIIQD, 249-VIIQD, 389-VIIQD, 469-VIIQD) bu nüsxədə yoxdur ⚠️ — maddə nömrələri və mətn rəsmi
  redaksiya ilə tutuşdurulmalıdır.

### 10.1 Mətn

> **ƏM m.175.1** Əmək haqqından müvafiq məbləğlər … yalnız işçinin yazılı razılığı ilə, yaxud qanunvericiliklə
> nəzərdə tutulmuş icra sənədləri üzrə tutulur. **175.2** İşəgötürənin sərəncamı ilə … a) vergilər, sosial sığorta
> haqları …; b) icra sənədləri üzrə müəyyən edilmiş məbləğ; …
> **ƏM m.176.1** Hər dəfə əmək haqqı verilərkən tutulan bütün məbləğlərin ümumi miqdarı işçiyə verilməli olan əmək
> haqqının iyirmi faizindən və müvafiq qanunvericilikdə nəzərdə tutulmuş hallarda isə əlli faizindən artıq ola bilməz.
> **176.2** … bir neçə icra sənədinə əsasən məbləğlər tutularkən, bütün hallarda işçinin əmək haqqının əlli faizi
> saxlanmalıdır. **176.3** … alimentlərin tutulması, sağlamlığa vurulmuş ziyanın ödənilməsi, ailəni dolandıranın
> itkisi … və cinayət nəticəsində vurulmuş ziyanın ödənilməsi zamanı … tutulmaların məbləği işçiyə verilməli olan
> əməkhaqqının 70 faizindən çox ola bilməz.
>
> **«İcra haqqında» m.64** Borclunun əmək haqqından … tutulan məbləğin (tutmanın) miqdarı vergilər tutulduqdan sonra
> qalan məbləğdən hesablanır. **m.65.1** … tutmaların məbləği 50 faizdən çox ola bilməz. **65.2** … bir neçə icra
> sənədi üzrə … qazancın 50 faizi borcluda qalmalıdır. **65.3** Alimentlərin tutulması, sağlamlığa vurulmuş ziyanın …,
> ailəni dolandıranın itkisi … və cinayət nəticəsində vurulmuş ziyanın ödənilməsi zamanı … 65.1 və 65.2 …
> məhdudiyyətlər tətbiq edilmir. Belə hallarda … 70 faizdən çox ola bilməz.
> **m.68.1** [tutma yönəldilə bilməz:] … 68.1.4. ağır və zərərli əmək şəraitində işə görə ödənilən məbləğlərə;
> 68.1.5. işdənçıxarma müavinətinin məbləğinə; 68.1.6. aliment öhdəlikləri üzrə ödənilən məbləğlərə; …
> **m.75.2** Birinci növbədə alimentlərin tutulması, sağlamlığa vurulmuş ziyanın, habelə ailəni dolandıranın itkisi
> nəticəsində … ziyanın ödənilməsi üzrə tələblər; **75.3** İkinci növbədə əmək hüququ münasibətlərindən əmələ gələn
> tələblər …; **75.4** Üçüncü növbədə məcburi dövlət sosial sığorta haqları və işsizlikdən sığorta haqları …, habelə
> icbari tibbi sığorta haqları …; **75.5** Dördüncü növbədə dövlət büdcəsinə və … büdcədənkənar dövlət fondlarına
> ödənişlər; **75.6** Bütün qalan tələblər beşinci növbədə … icra sənədlərinin daxil olma ardıcıllığı əsas götürülür.
> **75.7** Hər sonrakı növbədən olan tələblər əvvəlki növbədən olan tələblər tam ödənildikdən sonra təmin edilir.
> **75.8** … bir növbədən olan bütün tələbləri tam təmin etmək üçün yetərli olmadıqda, … mütənasib olaraq təmin edilir.

### 10.2 Qərarlar

| Qayda | Tətbiq | Status |
|---|---|---|
| Baza | hesablanmış − vergi və məcburi sosial ödənişlər (m.64; ƏM 176.1 ilə eyni), **çıxılsın** zərərli əmək şəraitinə görə ödəniş (`hazard`, m.68.1.4) | ✅ / ⚠️ m.64 yalnız «vergilər» deyir; sosial haqların da çıxılması əvvəlki qərarla (§4.1) eynidir. Zərərlilik əlavəsi brüt məbləği ilə çıxılır (vergi payı ayrılmır) |
| Hədd | yalnız adi icra sənədləri — 50 %; aliment / sağlamlıq / ailə başçısı / cinayət ziyanı varsa — 70 % | ✅ 176.1–176.3, 65.1–65.3 |
| Qarışıq hal | ümumi 70 %; adi (50 %-lik) sənədlər öz aralarında 50 %-i keçmir | ⚠️ mətndə birbaşa yoxdur, ehtiyatlı oxunuş |
| Növbə | 1 — aliment, sağlamlıq, ailə başçısı; 2 — əmək tələbləri; 3 — sosial/işsizlik/tibbi sığorta haqları; 4 — büdcə; 5 — qalanları (cinayətlə vurulmuş ziyan da) | ✅ m.75 (cinayət ziyanı 70 %-lik hədd alır, amma növbəsi 5-dir) |
| Növbə daxilində | 1–4: yer çatmırsa mütənasib (75.8, qəpiyə aşağı); 5: daxil olma tarixi ilə ardıcıl (75.6) | ✅ |
| Kredit/avans, könüllü tutulma | öz 20 %-i daxilində (ƏM 176.1) **və** icra sənədləri ilə birlikdə ümumi 50/70 %-i keçmir; icra sənədi birinci tutulur | ⚠️ «ümumi tavan» oxunuşu — mətn «bütün məbləğlərin ümumi miqdarı» deyir |
| Retro geri tutulma | eyni qayda (20 % + ümumi hədd) | ⚠️ |
| Sabit/faiz | sabit aylıq məbləğ yalnız ayın öz ödənişində (adi və son haqq-hesab run-u); faizlə olan sənəd hər ödənişdən (off-cycle da) | ⚠️ «hər dəfə əmək haqqı verilərkən» (176.1) |
| Qalıq borc | `total_debt` verilibsə tutulma qalıqdan çox olmur; kilidlə azalır, qalıq 0 olanda sənəd «Bitib»; run yenidən açılanda bərpa | ✅ |
| Retro | kilidli ayın yenidən hesablanmasında icra tutulması (kredit kimi) hər iki tərəfdən çıxarılır — bağlanmış sənəd ödənilmiş ayı «geri ödəmir» | ✅ |
| İşəgötürənin köçürmə müddəti, xitamda icra məmuruna bildiriş | **tapılmadı** — sistem yalnız siyahını hazırlayır | ⚠️ |

### 10.3 Tətbiq

- Cədvəllər: `enforcement_documents` (növ, növbə, sənədi verən — məhkəmə/icra məmuru/notarius, nömrə, tarix, daxil
  olma tarixi, sabit məbləğ və ya faiz, alıcı və hesabı, ümumi borc/qalıq, başlama/bitmə, status, qeyd; audit —
  activitylog `payroll`), `enforcement_deductions` (run üzrə faktiki tutulma, tələb olunan, baza, qalıq — unikal
  sənəd+run).
- Hesablama: `EnforcementService::plan()` → `PayrollCalculator` (statutor tutulmalardan sonra, kreditdən əvvəl);
  payslip sətri «İcra sənədi üzrə tutulma — Aliment, № İV-77», hədd/növbəyə görə azalanda «(tələb 700,00 AZN;
  hədd/növbəyə görə azaldıldı)». Kilid — `recordForRun()`, yenidən açma — `reverseForRun()`.
- Ekran: Maaş → **İcra sənədləri**: işçi seçilir, sənəd əlavə et / dayandır / bitir / bərpa et (səbəblə, təsdiq
  modalı), sənəd üzrə tarixçə və qalıq; **İcra məmuruna göndəriləcək tutulma siyahısı** (kilidli run üzrə, XLSX:
  tabel №, FİN, növ, məhkəmə/icra məmuru, sənəd, alıcı və hesabı, baza, tələb, tutulub, qalıq, tarix).

**Nümunələr** (`tests/Feature/Payroll/EnforcementPayrollTest.php`; 1 000 AZN, özəl, statutor 135 → baza 865,00):

| Hal | Tutulma | Ödənilən |
|---|---|---|
| Aliment 25 % | 216,25 | 648,75 |
| Aliment sabit 700 | 605,50 (70 % həddi) | — |
| Adi icra sənədi sabit 700 | 432,50 (50 % həddi) | — |
| Adi 500 (yanvarda daxil olub) + aliment 25 % (martda) | aliment 216,25 əvvəl (1-ci növbə), adi 389,25 (70 %-dən qalan) | 259,50 |
| İki aliment 40 % + 40 % (692 > 605,50) | 302,75 + 302,75 (mütənasib) | — |
| İki adi 300 + 300 | 300,00 (əvvəl daxil olan) + 132,50 | — |
| Aliment 25 % + kredit 300 | 216,25 + 173,00 (kredit 20 %-də) | 475,75 |
| Baza 1 000 + zərərlilik 200, aliment 25 % | (1 034 − 200) × 25 % = 208,50 | — |

## 11. Keçmiş aylar — «Vergi düzəliş hesabatı» (11.10.2026)

**Problem.** 01.01.2026-dan bu yeniləməyədək kilidlənmiş (ödənilmiş) aylarda: VM 102.1.6 (200 AZN) güzəşti
verilməyib (seed sətri son kilidli aydan sonra qüvvəyə minib — §5.1), dövlət/neft-qaz və hərbi işçilərə özəl
qeyri-neft dərəcələri tətbiq olunub (§6), işçi güzəşt kartları (§9) yox idi. **Avtomatik retro düzəliş
edilmir** — aylar ödənilib, bəyannamələr verilib.

### 11.1 Qanun ✅

Mənbə: Vergi Məcəlləsi <https://e-qanun.az/framework/46948>, oxunma 11.10.2026. (Tapşırıqdakı «VM 34 / 86»
nömrələri cari mətnə uyğun deyil: m.34 — uçot, m.86 — vergilərin ödənildiyi yer; düzgün maddələr aşağıdadır.)

> **72.5.** … əvvəlki hesabat dövrləri üzrə təqdim edilmiş hesabatlardakı məlumatlar ilkin uçot sənədləri üzrə
> məlumatlara uyğun olmadığı hallarda vergi ödəyiciləri tərəfindən həmin hesabat dövrü üçün dəqiqləşdirilmiş
> hesabat təqdim edilməlidir. Dəqiqləşdirilmiş hesabat üzrə büdcəyə ödənilməli vergi məbləği əvvəl təqdim edilmiş
> hesabat üzrə … çox olduqda, əlavə hesablanmış vergi məbləği faizlərlə birlikdə büdcəyə ödənilir, az olduqda isə
> hesablanmış vergi və faiz məbləğləri azaldılır. … dəqiqləşdirilmiş və ya vaxtında təqdim edilməmiş hesabatlar
> vergi ödəyicisində səyyar vergi yoxlamasının başlandığı günədək təqdim edilə bilər.
>
> **87.1.** … artıq ödənilmiş məbləğlər: 87.1.1. digər vergilərin, faizlərin, maliyyə sanksiyalarının və inzibati
> cərimələr üzrə borcların ödənilməsi hesabına aid edilir; 87.1.2. vergi ödəyicisinin razılığı ilə sonrakı öhdəliklər
> üzrə ödəmələrin hesabına aid edilir. **87.3.** … qalan məbləğləri vergi ödəyicisinin yazılı ərizəsinə əsasən 45 gün
> ərzində vergi ödəyicisinə qaytarılır.
>
> **150.3.1.** … fiziki şəxslərin hesablanmış aylıq gəlirinə gəlir vergisini hesablamağa və hesablanmış vergini
> növbəti ayın 20-dən gec olmayaraq büdcəyə köçürməyə; **150.3.3.** … ödəmə mənbəyində tutulan vergilər barədə …
> bəyannaməni rüb başa çatdıqdan sonra növbəti ayın 20-dən gec olmayaraq vergi orqanına verməyə borcludurlar.
>
> **149.5.** Bəyannamə verməli olmayan fiziki şəxs verginin yenidən hesablanması və vəsaitlərin qaytarılması haqqında
> tələb irəli sürülən bəyannamə verə bilər. **150.4.** … Vergi ödəyicisi bu Məcəllənin 149.5-ci maddəsində nəzərdə
> tutulmuş hüquqlardan istifadə etmirsə, tutulmuş vergi gəlir vergisinin tam məbləği sayılır.
>
> **58.1.** Verginin (o cümlədən ödəmə mənbəyində verginin) hesabatda göstərilən məbləği … azaldılmışdırsa …
> azaldılmış … vergi məbləğinin … 50 faizi miqdarında maliyyə sanksiyası tətbiq edilir. (85.5 — yenidən hesablama və
> qaytarma tələbi dövr bitdikdən sonra 5 il ərzində.)

**Nəticə.** İşəgötürən (vergi agenti) həmin rüb/ay üçün **dəqiqləşdirilmiş hesabat** verir (72.5): az tutulmuş vergi
faizlə ödənilir, artıq ödənilmiş vergi sonrakı öhdəliklərə aid edilir və ya ərizə ilə qaytarılır (87). İşəgötürənin
artıq tutduğu vergini işçiyə necə qaytarması VM-də açıq yazılmayıb ⚠️ — praktik yol: dəqiqləşdirmədən sonra məbləğ
işçiyə əmək haqqı ilə qaytarılır; işçi özü də 149.5 üzrə bəyannamə verə bilər. Az tutulmuş vergi üçün 58.1 sanksiyası
və «yoxlamadan əvvəl düzəliş» istisnası haqqında açıq norma tapılmadı ⚠️ (yalnız 72.5-1/72.5-2 könüllü açıqlama).
DSMF/işsizlik/tibbi sığorta haqları üzrə hesabat düzəlişi ayrıca qaydadadır — bu sənəddə yoxlanmayıb ⚠️.

### 11.2 Hesabat — `TaxAdjustmentReportService`

- Seçilmiş dövrün (ay–ay) **kilidlənmiş** payslip-ləri yaddaşda yenidən hesablanır; heç bir cədvələ yazılmır.
- Qazanc sətirləri payslip-in özündəndir (ödənilmiş fakt, `taxable` / `affects_social` bayraqları ilə; `retro`
  sətri xaric — o, xalis məbləğin əlavəsidir).
- Rejim: işçinin **bugünkü** aktiv təyinatının rejimi (§8.3 düzəlişindən sonra düzgün olan); yoxdursa ödəniş
  anındakı rejim.
- Dərəcə/azadolmalar: «qayda tarixi» = min(bu gün, həmin ilin 31 dekabrı), ayın sonundan tez olmayaraq. Səbəb:
  seed sətirləri kilidli aylardan sonraya çəkilib; ilin sonundakı qayda dəsti həmin il qüvvədə olan qanunu əks
  etdirir. ⚠️ Fərziyyə: il ərzində qanun dəyişməyib (2026-da bütün dəyişikliklər 01.01.2026-dan qüvvədədir — 297-VIIQD).
- Güzəşt kartları: qanuni başlanğıcdan (`legal_from`, §9.3).
- Komponentlər: gəlir vergisi, DSMF işçi/işəgötürən, işsizlik işçi/işəgötürən, tibbi işçi/işəgötürən — hər biri
  üçün «tutulmuş → düzgün» və fərq (müsbət — artıq tutulub/ödənilib).
- Ekran: Maaş → **Vergi düzəliş hesabatı** (yalnız `view-compensation-amounts`; struktur görünürlüyü), XLSX ixracı
  (`export-payroll`).

### 11.3 Qaytarma — ayrıca, açıq təsdiqli əməliyyat

- Mühasib sətirləri seçir, komponentləri (standart: yalnız gəlir vergisi; işçi tərəfi DSMF/işsizlik/tibbi
  isteğe bağlı), ödəniş ayını və səbəbi yazır, təsdiq modalı ilə `recordRefunds()` çağırır.
- Yalnız **müsbət** fərq (artıq tutulmuş) qaytarılır; az tutulmuş məbləğ işçidən avtomatik tutulmur.
- Qaytarma mövcud birdəfəlik ödəniş mexanizmi ilə (`PayrollOneOffEarnings::record`, kod `tax_adjustment_refund`,
  **vergidən və sosial bazadan kənar**) seçilmiş ayın adi run-una düşür; ay kilidlidirsə növbəti aya keçir. Mənbə
  açarı payslip üzrədir (`tax_adjustment:payslip:<id>`) — təkrar əməliyyat ikinci ödəniş yaratmır; hesabatda
  «qaytarma əlavə olunub» görünür. Audit — `tax_adjustment_refund_recorded`.
- ⚠️ Qaytarma payslip-də «qazanc» sətri kimi görünür (brüt cəmə düşür, vergi/sosial bazaya düşmür) — dövlət
  hesabatında brüt sütunu bunu da göstərir; mühasib qaytarmanı dəqiqləşdirilmiş bəyannamə ilə uzlaşdırmalıdır.

**Nümunələr** (`tests/Feature/Payroll/TaxAdjustmentReportTest.php`):
- Özəl qeyri-neft, 1 100 AZN, iyun kilidli (102.1.6 verilməyib: vergi 33,00), sonradan 102.3 kartı (status 2025-dən):
  düzgün (1100 − 200 − 200) × 3% = 21,00 → **artıq 12,00**; qaytarma iyul run-una 12,00 AZN vergidən kənar sətir.
- Neft-qaz şirkəti, işçi iyunda özəl rejimlə ödənilib, iyuldan `state`: vergi az 93,00 (126 − 33), DSMF işçi artıq
  63,00 (96 − 33), DSMF işəgötürən az 63,00 (242 − 179).

---

## İmtiyazlar — natura formasında gəlir (Benefits modulu, 11.10.2026)

Mənbələr 11.10.2026 tarixində e-qanun.az-ın konsolidasiya edilmiş mətnindən oxunub:
Vergi Məcəlləsi <https://e-qanun.az/framework/46948>, «Sosial sığorta haqqında» Qanun
<https://e-qanun.az/framework/3813>. Kod: `app/Modules/Benefits`, standartlar `config/benefits.php`.

### Mətnlər ✅

- **VM m.98.1** — muzdlu işlə əlaqədar gəlir «əmək haqqı, bu işdən alınan hər hansı ödəmə və ya fayda»dır.
- **VM m.98.2** — gəlirin məbləği «işçinin əldə etdiyi gəlirə görə ödədiyi hər hansı xərc çıxıldıqdan sonra qalan
  məbləğ»dir; o cümlədən **98.2.2** (işəgötürənin verdiyi mal/iş/xidmət — bazar qiyməti ilə), **98.2.3** (xərclərin
  əvəzi, m.119.2 istisna olmaqla), **98.2.5** («İşəgötürən tərəfindən ödənilən sığorta haqları»).
- **VM m.98.5** — «İşə götürən tərəfindən bu Məcəllənin 109.3-cü və 119.2-ci maddələrində nəzərdə tutulmuş xərclərin
  çəkilməsinin nəticəsi kimi əldə edilən gəlir vergi tutulan gəlirə aid deyildir.»
- **VM m.109.3** — 119.2 istisna olmaqla, «işçilərin və onların ailə üzvlərinin sosial xarakterli xərclərinin və
  əyləncə xərclərinin gəlirdən çıxılmasına yol verilmir». **m.119.2** — işçilərlə bağlı yemək (və s.) xərclərinin
  gəlirdən çıxılması normaları müvafiq orqan tərəfindən müəyyən olunur.
- **VM m.102.1.8** — gəlir vergisindən azaddır: «…həmçinin işəgötürən tərəfindən ödənilən bütün növ icbari sığorta və
  könüllü tibbi sığorta haqları, 3 ildən az olmayan müddətə bağlanan müqavilə ilə həyatın yığım sığortası və pensiya
  sığortası üzrə qeyri-dövlət sektoruna aid edilən işəgötürənin sığortaolunanın vergiyə cəlb olunan gəlirlərinin
  50 faizindən çox olmayan hissəsindən Azərbaycan Respublikasının sığortaçılarına ödədikləri sığorta haqları…».
- **«Sosial sığorta haqqında» Qanun m.15** — məcburi dövlət sosial sığorta haqqı «sosial sığorta haqqı hesablanmayan
  … gəlir növləri istisna olunmaqla əmək ödənişinin və gəlirlərin bütün növləri üzrə hesablanır»; istisnalar
  siyahısında işəgötürənin ödədiyi icbari və könüllü tibbi sığorta haqları, 3 ildən az olmayan yığım/pensiya sığortası
  (50 % həddi ilə) və zərərli işlərdə verilən müalicə-profilaktik yemək var; adi yemək, idman, nəqliyyat yoxdur.

### Standart vergi rejimi (mühasib paket səviyyəsində dəyişə bilər)

| Növ | Gəlir vergisi | DSMF/işsizlik/tibbi bazası | Əsas |
|---|---|---|---|
| Tibbi sığorta (könüllü) | azad | azad | VM 102.1.8; Qanun m.15 |
| Həyat sığortası (risk) | **vergiyə cəlb** | **daxil** | VM 98.2.5 (102.1.8 yalnız yığım/pensiyaya aiddir) |
| Həyatın yığım / pensiya sığortası (≥ 3 il) | azad | azad | VM 102.1.8 — 3 il və 50 % həddini mühasib yoxlayır ⚠️ |
| Yemək kartı | azad | **daxil** ⚠️ | VM 98.5 + 109.3/119.2; Qanun m.15 adi yeməyi istisna etmir |
| Nəqliyyat | **vergiyə cəlb** | **daxil** | VM 98.2.3 |
| Mobil telefon limiti (xidməti) | gəlir deyil | gəlir deyil | işgüzar xərc; şəxsi istifadə/limitdən artıq — VM 98.2.2 ⚠️ |
| İdman zalı | azad | **daxil** ⚠️ | VM 98.5 + 109.3 (sosial xarakterli xərc) |
| Digər | **vergiyə cəlb** | **daxil** | VM 98.1, 98.2.2 |

Vergiyə cəlb olunan məbləğ **yalnız işəgötürən payıdır** — işçinin özünün ödədiyi hissə VM 98.2-yə görə çıxılır.

### Tətbiq (Payroll koduna toxunmadan)

- **İşçi payı** → Compensation kataloqunda `benefit_employee_share` (tutulma, vergisiz) komponenti ilə işçinin cari aktiv
  kompensasiyasına sətir (`note = benefits#<qoşulma id>`). Payroll onu digər könüllü tutulmalar kimi hər ay çıxır.
- **Natura formasında gəlir** (işəgötürən payı, paket «vergiyə cəlb» və ya «DSMF bazası» qeyd olunubsa) → hər ay
  `PayrollOneOffEarnings::record()` ilə `benefit_in_kind` (+məbləğ, paketin bayraqları ilə) və `benefit_in_kind_offset`
  (−məbləğ, vergisiz, sosial bazadan kənar). Nağd brüt/xalis dəyişmir, yalnız vergi/sığorta bazası artır. Açar
  `benefits:<qoşulma>:<YYYY-MM>:value|offset` — təkrar sinxron ikinci sətir yaratmır; ay kilidlidirsə hər iki sətir
  birlikdə növbəti aya keçir. Davamiyyət proporsiyası tətbiq olunmur (sığorta haqqı ay üzrə tam ödənilir).
- Nümunə (`tests/Feature/Benefits/BenefitPayrollIntegrationTest.php`): 1 500 AZN maaş, həyat sığortası 40 AZN
  (100 % işəgötürən) + tibbi 100 AZN (80/20): brüt dəyişmir, 20 AZN işçi payı tutulur, vergi/DSMF bazası 40 AZN artır.

### Açıq qalanlar ⚠️

1. Yemək kartı və idman üçün DSMF tətbiqi Qanunun hərfi mətninə görə qurulub; DSMF təcrübəsi fərqli şərh edə bilər.
2. VM 102.1.8 yığım/pensiya sığortası üçün «3 il» və «vergiyə cəlb olunan gəlirin 50 %-i» həddi avtomatik yoxlanmır.
3. Mobil telefon üçün şəxsi istifadə hissəsinin ayrılması (VM 98.2.2) sistemdə yoxdur — paket bütövlükdə ya gəlir, ya
   işgüzar xərc sayılır.
4. `benefit_in_kind_offset` mənfi «qazanc» sətridir: GL/dövlət hesabatı ixracında `benefit_in_kind` ilə birlikdə
   uzlaşdırılmalıdır (cəmi sıfırdır).
