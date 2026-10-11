# Əmək haqqı hesablamasının hüquqi əsası — əmrdən irəli gələn ödənişlər

> Mənbə: **Azərbaycan Respublikasının Əmək Məcəlləsi** (ƏM), e-qanun.az-dakı cari
> redaksiya — <https://e-qanun.az/framework/46943> (mətn:
> <https://e-qanun.az/frameworks/46/f_46943.html>), 2026-10-09 tarixində oxunub.
> Sitatlar həmin mətndən hərfi götürülüb. Kodda bu sənədə istinad edən yerlər:
> `Payroll\Application\Services\OrderEarningsService`, `PayrollRunService`, `RetroService`,
> `LoanService`, `PayrollCalculator`, `config/payroll.php`.
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

- ✅ m.176.1 — 20 % həddi (mətn §3-də).
- ⚠️ **50 % istisnası** (bir neçə icra sənədi, aliment və s. üzrə tutulmalarda daha yüksək hədd)
  burada **tətbiq edilmir**: m.176-nın həmin hissəsinin dəqiq mətni bu sənəd hazırlanarkən
  e-qanun.az-dan oxuna bilmədi (giriş bağlı idi) — **yoxlanmalıdır**. Kredit/avans icra sənədi
  deyil, ona görə 20 % ümumi hədd seçilib. İcra sənədləri üçün ayrıca modul yoxdur.
- ⚠️ Taksitin işçinin ərizəsi ilə tutulması (m.175.1 «yazılı razılıq»; §3-dəki m.175.6 istinadı)
  — kreditin rəsmiləşdirilməsi zamanı yazılı razılığın alınması fərz olunur; sistem onu
  saxlamır.
- ⚠️ **Adi aylıq taksitə 20 % həddi tətbiq olunmur** (əvvəlki davranış saxlanılıb). m.176.1
  «bütün məbləğlər» dediyi üçün ciddi oxunuşda adi taksit də həddə tabe olmalıdır — bu
  davranış dəyişikliyi istifadəçi/hüquqşünas təsdiqi gözləyir.
- ⚠️ `retro_recovery` (§3) öz 20 %-ni ayrıca hesablayır; eyni ödənişdə kredit hesablaşması ilə
  birlikdə cəmi yoxlanmır (hər ikisi standart olaraq nadir: recovery söndürülüb).
- ⚠️ Xitam zamanı qalan borcun tam (həddsiz) tutulmasına işçinin ayrıca yazılı razılığı olarsa
  icazə verilib-verilmədiyi — hüquqşünas rəyi lazımdır; hazırda hədd hər halda tətbiq olunur.

## 5. Yoxlanılmamış / açıq qalan məsələlər

1. ⚠️ Saatlıq vəzifə maaşının ayın fərdi normasına bölünməklə tapılması — rəsmi izahat
   (Nazirlik məktubu və ya metodik göstəriş) tapılmadı.
2. ⚠️ «Aylıq iş vaxtı norması çərçivəsində» anlayışının məzuniyyət/xəstəlik günləri ilə
   əlaqəsi — şərhimizdir; cəmlənmiş uçot (m.96) olan əməkdaşlar üçün ayrıca qayda lazım ola bilər.
3. ⚠️ Əvəzetmə əlavəsinin iş günlərinə mütənasib bölünməsi — təcrübədir, Məcəllədə formula yoxdur.
   Davamiyyətdə qeydə alınmamış (səbəbsiz) qayıblar hələ çıxılmır, yalnız məzuniyyət, gün üzrə
   icazə/xəstəlik və ezamiyyət çıxılır.
4. ⚠️ Kollektiv və ya əmək müqaviləsi m.164 və m.162.2 üzrə daha yüksək məbləğ müəyyən edə
   bilər — hazırda yalnız qanuni minimum (və əmrdəki razılaşma) tətbiq olunur.
