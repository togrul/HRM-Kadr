# Hüquqi təsdiq reyestri — mühasib, hüquqşünas və DSMF/Vergi orqanı cavabı gözləyən qərarlar

> Tərtib tarixi: 11.10.2026. Mənbə: `docs/payroll-legal-basis.md`, `docs/vacation-legal-basis.md`,
> `docs/payroll-compensation-plan.md`, `docs/performance-evaluation-module-design.md`,
> `docs/integration-security-and-production-proposal.md` (§5.4), `docs/scenario/*.md` və koddakı şərhlər.
> Hər bənddəki «hazırkı davranış» və «dəyişiklik yeri» koda baxılaraq yoxlanıb (fayl:sətir göstərilib).
> Hüquqi mətnlər yalnız kodda/docs-da olan və ya bu sənədin §6-da qeyd olunan açıq mənbədən götürülüb;
> təsdiqlənə bilməyən yer **«yoxlanmayıb»** yazılıb.

---

## 1. Məqsəd

Mühasib və hüquqşünas aşağıdakı bəndlərə bir səhifədən baxıb **«Bəli» (hazırkı davranış düzgündür)** və ya
**«Xeyr» (dəyişməlidir)** cavabı verə bilsin; «Xeyr» olarsa, sistemdə **nəyin harada dəyişəcəyi** və dəyişikliyin
konfiqurasiya, yoxsa kod tələb etdiyi əvvəlcədən bəlli olsun.

Bu sənəd hüquqi rəy vermir və heç bir qaydanı dəyişmir; yalnız suallar, hazırkı davranış və dəyişiklik xəritəsidir.

## 2. Cavabları necə qeyd etmək

Hər bəndin sonunda boş sahə var:

```
Cavab: [ ] Bəli, hazırkı davranış düzgündür   [ ] Xeyr, dəyişməlidir   [ ] Qismən: ...........
Tarix: ____.____.________      Kim: ____________________ (vəzifə: ________)
Əsas (maddə / yazılı rəy / sənəd nömrəsi): ______________________________
Qüvvəyə minmə tarixi (dəyişiklik olarsa): ____.____.________
```

Qaydalar:
1. «Xeyr» cavabında **qüvvəyə minmə tarixi** mütləq yazılmalıdır — kilidlənmiş aylar avtomatik dəyişmir, keçmiş
   aylar üçün `Maaş → Vergi düzəliş hesabatı` ayrıca işlənir (`payroll-legal-basis.md` §11).
2. Cavab yazılı əsas (maddə və ya orqanın yazılı izahı) ilə birgə saxlanmalıdır.
3. «Konfiqurasiya» — mühasib/admin ekranda və ya `.env`-də dəyişə bilər; «Kod» — developer dəyişikliyi + test lazımdır.

## 3. Prioritet cədvəli (təsir ↓)

Sıralama bu sənədi hazırlayanın qiymətləndirməsidir: təsir dairəsi (neçə işçi), aylıq təkrarlanma və səhv istiqaməti
(dövlət/işçi/işəgötürən öhdəliyi) nəzərə alınıb. Rəqəmlər aşağıdakı bəndlərdəki nümunələrdəndir.

| # | Bənd | Kim təsdiqləməlidir | Təsir | Dəyişiklik növü («Xeyr» olarsa) |
|---|---|---|---|---|
| 1 | **H-06** Şirkətin NK 56 sektor statusu; subpodratçı 50% həddi izlənmir | Mühasib + Vergi/DSMF orqanı | Çox yüksək — bütün işçilər, hər ay (1 100 AZN-də işçi başına ~99 AZN vergi fərqi) | Konfiqurasiya (sektor) + toplu düzəliş; 50% izləmə — kod |
| 2 | **H-04** Yemək kartı və idman zalının DSMF/işsizlik/tibbi bazası | Mühasib + Vergi/DSMF orqanı | Yüksək — imtiyaz alan hər işçi, hər ay; DVX izahı (10.07.2026) hazırkı defaultla **ziddir** | Qismən konfiqurasiya (paket bayrağı), 5 AZN/gün norması — kod |
| 3 | **H-02** VM 102.1.6/102.5 toplanması, natamam ay, 50%+70% | Mühasib (+ hüquqşünas 50/70 üçün) | Orta-yüksək — çox işçi, kiçik məbləğ (6–28 AZN/ay), ilk ikisi şübhəlidir | Kod (konfiqurasiya yoxdur) |
| 4 | **H-07** Xitamda qalan borcun 20%-dən artıq tutulması | Hüquqşünas | Orta — hər çıxan borclu işçi; yüzlərlə AZN | Kod (+ razılıq qeydi yoxdur) |
| 5 | **H-08** Vergi düzəlişi: işçiyə qaytarma, DSMF/işsizlik/tibbi düzəliş | Mühasib + Vergi orqanı | Orta — keçmiş aylar üzrə birdəfəlik; yanlış qaytarma ikiqat ödəniş riskidir | Prosedur / kiçik kod |
| 6 | **H-01** Hərbçilərdə işsizlik sığortası 0% | Mühasib + DSMF/Məşğulluq agentliyi | Orta — yalnız `military` rejimi; işçi başına ~7,50–10 AZN/ay hər tərəf | Konfiqurasiya |
| 7 | **H-05** 18.02.2026-dan əvvəl çıxanlar — düz 15 il +2 gün | Hüquqşünas | Orta-aşağı — bir neçə nəfər, işçi başına ~99 AZN | Kod yoxdur (yalnız qərar/ödəniş əmri) |
| 8 | **H-03** «İcra haqqında» Qanunun redaksiyası, m.64–75, xitamda köçürmə | Hüquqşünas | Orta — icra sənədi olan hər işçi; m.68.1.5 boşluğu | Kod |
| 9 | **H-10** 6 ay növbəti şərait ilində tamamlanır | Hüquqşünas | Aşağı — dar hal (bir neçə gün/işçi) | Kod + məlumat düzəlişi |
| 10 | **H-09** Hamiləlik/doğuş məzuniyyətinin şərait stajına daxil edilməsi | Hüquqşünas | Aşağı — dar hal, işçinin lehinədir | Kod |
| 11 | **H-11** KPI bonusunun hüquqi təbiəti (əsasnamə/müqavilə) | Hüquqşünas | Şirkət siyasəti; qanun tənzimləmir | Konfiqurasiya |

Əlavə bəndlər (§5) ayrıca cədvəldədir; onlardan yüksək təsirlisi: **E-01** (VM 102.1.6-nın «aylıq gəlir» hədd hesabı və
off-cycle), **E-03** (məzuniyyət normalarının toplanması — 332-VIIQD qüvvəyə minmə tarixi), **E-06** (hərbçi pul
təminatı siyahısı).

---

## 4. Əsas bəndlər

### H-01. Hərbi qulluqçular üçün işsizlik sığortası 0%

| | |
|---|---|
| **Sual** | Hərbi qulluqçuların əmək haqqından işçi və işəgötürən tərəfdən işsizlikdən sığorta haqqı tutulmamalıdırmı (0% / 0%)? |
| **Kim** | Mühasib; DSMF və ya Məşğulluq Agentliyinin yazılı izahı tövsiyə olunur |
| **Hazırkı davranış** | `military` rejimində `unemployment` / `ee` və `er` dərəcəsi **0**; `state` və `private` — 0,5% / 0,5%. Yazı: `app/Modules/Compensation/Database/Migrations/2026_10_11_130100_seed_state_and_military_statutory_rates.php:119-120` (cədvəl `statutory_rates`). Sətir yoxdursa mühərrik `regime_id = null` (özəl) sətrinə düşür (`EloquentCompensationReadRepository::statutoryRatesFor`, sətir 82-96) |
| **Hüquqi əsas (kodda/docs-da)** | «İşsizlikdən sığorta haqqında» m.2.0.3 (sığortaolunan — əmək müqaviləsi bağlamış işçi), m.2.0.13, m.1.3; ƏM m.6.a (Məcəllə hərbi qulluqçulara şamil olunmur). `payroll-legal-basis.md` §6.3(a): **açıq istisna yoxdur, sistemli şərhdir** |
| **«Xeyr» olarsa nə dəyişir** | `statutory_rates`-də `regime=military`, `component=unemployment`, `payer=ee` və `er` sətirlərini 0,5% ilə əvəz etmək (yeni qüvvəyə minmə tarixi ilə yeni sətir; köhnə sətir bir gün əvvəl bağlanır) — ekran: **Kompensasiya → Vergi/sığorta dərəcələri**. **Konfiqurasiya ilə.** Diqqət: (1) tarix ən son kilidli aydan sonra olmalıdır, əks halda retro mühərriki kilidli payslip-ləri yenidən hesablayıb «geri ödəniş» yaradır; (2) hərbi rejimdə DSMF-in bazası «pul təminatı» ilə məhdudlaşıb (`military_dsmf_pay_allowance`, `component_code='dsmf'`); işsizlik üçün belə qayda yoxdur, ona görə 0,5% **bütün** sosial qazanclardan (mükafat daxil) hesablanar (`StatutoryEngine::socialBases`, sətir 123-147). Baza məhdudlaşdırılacaqsa `statutory_exemptions`-a `component_code='unemployment'`, növ `taxable_components` sətri əlavə edilir — yenə konfiqurasiya |
| **Yanlış olarsa risk** | 0% səhvdirsə: işçinin əlinə 0,5% çox çatır, işəgötürən işsizlik haqqını az köçürür (öhdəlik işəgötürəndədir — borc + sanksiya). 0,5% səhvdirsə: işçidən və işəgötürəndən artıq tutulur. Miqyas: vəzifə 1 000 + rütbə 300 + dil 200 = 1 500 AZN pul təminatı → **7,50 AZN işçi + 7,50 AZN işəgötürən / ay**; 500 mükafat əlavə olunsa baza 2 000 → 10,00 + 10,00. 100 hərbçi üçün ≈ 1 500–2 000 AZN/ay (ikitərəfli) |
| **Müvəqqəti tövsiyə** | Qərar gələnədək 0% saxlanılsın (sənədləşmiş şərh), amma mühasib hər ay fərqi `Maaş → Vergi düzəliş hesabatı` ilə izləsin və cavab «Xeyr» olarsa qüvvəyə minmə tarixi **ilk əmək haqqı ayından** (01.01.2026) götürülsün ki, düzəliş hesabatı tam fərqi göstərsin. Şübhəli hallarda (kontrakt əsasında xidmət) işçilərin siyahısı ayrıca çıxarılsın |

```
Cavab: [ ] Bəli (0%)   [ ] Xeyr (0,5%/0,5%)   [ ] Qismən: ...........
Tarix: ____.____.________      Kim: ____________________
Əsas: ______________________________      Qüvvəyə minmə: ____.____.________
```

---

### H-02. VM m.102 şəxsi güzəştlərin birləşməsi (üç alt sual)

**Ümumi hazırkı davranış.** `StatutoryEngine::reliefFor()` (`app/Modules/Payroll/Application/Services/StatutoryEngine.php:160-218`):
102.1.6 (200 AZN, ≤ 2500) + `max(102.1-1 / 102.2 / 102.3 / 102.4)` + 102.5 (50 AZN). «Ən böyüyü» qrupu sabitdir:
`EmployeeTaxRelief::LARGEST_ONLY` (`app/Models/EmployeeTaxRelief.php:48`). Hesablama: `PayrollCalculator::calculate()`
sətir 141. Məbləğlər `statutory_exemptions` cədvəlindədir (kodlar `vm_102_1_6`, `vm_102_1_1` 800, `vm_102_2` 400,
`vm_102_3` 200, `vm_102_4` 100, `vm_102_5` 50; ekran: **Kompensasiya → Vergi/sığorta dərəcələri → Gəlir vergisi
azadolmaları**).

**Hüquqi mətn** (`payroll-legal-basis.md` §9.1): VM m.102.7 — «102.1-1, 102.2, 102.3 və 102.4 üzrə güzəşt hüququ olduqda
… bu güzəştlərdən biri, məbləğcə ən böyük olanı verilir»; 102.1.6 və 102.5 bu siyahıda **yoxdur** (sözbəsöz oxunuş).

#### H-02a. 102.1.6 (200) və 102.5 (50) güzəştləri 102.7-nin ən böyük güzəştinə əlavə olunur?

| | |
|---|---|
| **Sual** | 102.1.6 (200 AZN) və 102.5 (50 AZN) məbləğləri 102.1-1/102.2/102.3/102.4-dən ən böyüyünün üstünə **toplanırmı**? |
| **Kim** | Mühasib; Vergi orqanının (DVX) yazılı izahı |
| **Hazırkı davranış** | Toplanır: 1 100 AZN, 102.3 kartı → 200 + 200 = 400 azad, vergi (1 100 − 400) × 3% = **21,00**; 102.2 + 102.5 → 200 + 400 + 50 |
| **Əsas** | VM m.102.7 mətninin sözbəsöz oxunuşu; `payroll-legal-basis.md` §9.2 «⚠️ DVX FAQ 2026-da bu barədə izah yoxdur». Açıq mənbə: DVX-in 18.10.2022 tarixli sual-cavabında («suallar-ve-cavablar», səhifə 60) 102.2.7 (400) üzrə cavabda «**Əlavə olaraq**, 102.1.6-cı maddəyə əsasən … 200 manat» yazılıb — 102.1.6-nın 102.2 ilə toplandığını dəstəkləyir (bu sessiyada fetch aləti ilə qismən oxunub; 102.5 üçün **yoxlanmayıb**) |
| **«Xeyr» olarsa** | Yeni qayda: 102.1.6 və/və ya 102.5 də «ən böyüyü» qrupuna daxil olur. Dəyişiklik yeri: `StatutoryEngine::reliefFor()` (sətir 170-215) — 102.1.6 `KIND_FIXED_AMOUNT` kimi ayrıca toplanır; `EmployeeTaxRelief::LARGEST_ONLY` (sətir 48) genişləndirilməli və 102.1.6-nın növü `personal` qrupuna gətirilməlidir. **Kod dəyişikliyi** (konfiqurasiya ilə ifadə olunmur). Test: `tests/Feature/Payroll/TaxReliefCardTest.php` |
| **Risk** | «Bəli» səhvdirsə — işçilərin vergisi az tutulur (işəgötürən vergi agentidir, fərq + faiz). Miqyas: **200 AZN × 3% = 6,00 AZN/ay** işçi başına (2027-də 5% → 10,00; 2028-də 7% → 14,00; dövlət/neft-qaz rejimində 14% → **28,00**); 102.5 üçün 50 × 3% = 1,50 AZN/ay. 1 000 işçi × 6 AZN × 12 = 72 000 AZN/il (yalnız ikinci kateqoriya güzəşti olanlar üçün; real say azdır) |
| **Müvəqqəti tövsiyə** | **Ehtiyatlı variant:** kartı olan işçilərdə 102.1.6-nın toplanması mübahisəlidir — qərar gələnədək hazırkı davranış (daha əlverişli, 2022 DVX cavabı ilə dəstəklənir) saxlanıla bilər, amma kart-sahibi işçilərin siyahısı və aylıq fərq qeydiyyatda saxlansın ki, «Xeyr» olarsa düzəliş hesabatı tez hazırlansın |

#### H-02b. Natamam ayda 200 AZN tam verilir (proporsiya yoxdur)

| | |
|---|---|
| **Sual** | İşçi ayın yalnız bir hissəsini işləyibsə (qəbul/çıxış/məzuniyyət), 102.1.6-nın 200 AZN-i tam çıxılırmı, yoxsa işlənmiş günə mütənasib? |
| **Kim** | Mühasib; DVX izahı |
| **Hazırkı davranış** | Tam 200 (maaş proporsiya əmsalı ilə azalır — `PayrollCalculator::calculate()` sətir 65-67 — amma azadolma `min(amount, income)` ilə tam verilir: `StatutoryEngine::reliefFor()` sətir 217) |
| **Əsas** | VM m.102.1.6 mətnində mütənasiblik yoxdur («aylıq gəliri 2500 manatadək olduqda, 200 manat … hissəsi»). `payroll-legal-basis.md` §5.1 ⚠️; DVX izahı tapılmayıb |
| **«Xeyr» olarsa** | `PayrollCalculator::calculate()` sətir 141-də `proration_factor` (`$factor`) `StatutoryEngine::compute()`-a ötürülməli, `reliefFor()` sətir 208 `fixed_amount` üçün onunla vurulmalıdır. **Kod dəyişikliyi** |
| **Risk** | Nümunə: 1 100 AZN/ay, 10/22 gün işləyib → baza 500; tam 200 ilə vergi (500−200)×3% = **9,00**; proporsional 200×10/22 = 90,91 ilə (500−90,91)×3% = **12,27** → fərq **3,27 AZN**, işçinin lehinə/büdcənin əleyhinə. Kiçik, amma işə qəbul/çıxış ayları üçün kütləvidir |
| **Müvəqqəti tövsiyə** | Hazırkı davranış saxlansın (mətn mütənasiblik tələb etmir, işçi üçün əlverişlidir); risk aşağıdır |

#### H-02c. 50% + 70% qarışıq icra halı

| | |
|---|---|
| **Sual** | Eyni işçidə aliment (70%-lik) və adi icra sənədi (50%-lik) birlikdə olduqda: ümumi hədd 70%-dirmi və adi sənədlər öz aralarında 50%-i keçmirmi? |
| **Kim** | Hüquqşünas |
| **Hazırkı davranış** | Ümumi hədd 70%; adi (50%-lik) sənədlərə ayrıca 50% yer: `EnforcementService::plan()` (`app/Modules/Payroll/Application/Services/EnforcementService.php:134-214`, xüsusən sətir 163-168, 178-181); 70%-lik növlər `EnforcementDocument::SEVENTY_PERCENT_KINDS` (`app/Models/EnforcementDocument.php:55`) |
| **Əsas** | «İcra haqqında» m.65.1–65.3; ƏM m.176.1–176.3 (mətn bu sessiyada azstand.gov.az nüsxəsindən təkrar oxunub, §6). m.65.3: aliment və s. zamanı 65.1 və 65.2 məhdudiyyətləri «tətbiq edilmir», tutma 70%-dən çox ola bilməz. Qarışıq hal mətndə **birbaşa yoxdur** (kodda ⚠️) |
| **«Xeyr» olarsa** | Seçim A: adi sənədlər də ümumi 70% içində sərbəst (50%-lik alt hədd silinir) — `plan()` sətir 168, 178-181 silinir. Seçim B: aliment olduqda adi sənəd üçün 30% — sətir 165-168. **Kod dəyişikliyi** (hədd nisbətləri 0.50/0.70 sətirlərdə sabitdir) |
| **Risk** | Nümunə (baza 865): aliment 10% = 86,50 + adi sənəd 500 tələbi. İndi adi = 432,50 (50%), cəmi 519,00; Seçim A ilə adi = 500, cəmi 586,50 (≤ 605,50). **Fərq 67,50 AZN/ay** tələbkarın lehinə, işçinin əleyhinə. Yanlış halda işəgötürən tələbkar qarşısında məsuliyyət daşıyır (az köçürmə) və ya işçinin 50% saxlanma hüququ pozulur |
| **Müvəqqəti tövsiyə** | Hazırkı ehtiyatlı variant saxlansın (işçi üçün daha qorunan); qarışıq halı olan işçilər `İcra sənədləri` ekranından siyahı ilə hüquqşünasa göndərilsin |

```
H-02a  Cavab: [ ] Bəli, toplanır   [ ] Xeyr   [ ] Qismən: (yalnız 102.1.6 / yalnız 102.5)
H-02b  Cavab: [ ] Bəli, tam 200    [ ] Xeyr, proporsional
H-02c  Cavab: [ ] Bəli (hazırkı)   [ ] Xeyr — Seçim A / B
Tarix: ____.____.________      Kim: ____________________
Əsas: ______________________________      Qüvvəyə minmə: ____.____.________
```

---

### H-03. «İcra haqqında» Qanunun rəsmi son redaksiyası — m.64–75 və xitamda köçürmə müddəti

| | |
|---|---|
| **Sual** | Koddakı qaydalar (baza, 50%/70% hədləri, qorunan gəlirlər, növbəlilik) «İcra haqqında» Qanunun **rəsmi son redaksiyası** ilə uyğundurmu? Həmçinin: işdən çıxan işçinin icra sənədi üzrə tutulan məbləğin köçürmə müddəti və icra məmuruna bildiriş qaydası nədir? |
| **Kim** | Hüquqşünas (rəsmi konsolidasiya ilə tutuşdurma) |
| **Hazırkı davranış** | Baza = hesablanmış − vergi − məcburi sosial ödənişlər − zərərlilik əlavəsi (`PayrollCalculator::calculate()` sətir 148-152; `EnforcementService::PROTECTED_EARNINGS = ['hazard']`, sətir 40); növbə: `EnforcementService::defaultPriority()` (sətir 43-53); növbə içində 1–4 mütənasib, 5-ci daxil olma ardıcıllığı. Xitam üçün köçürmə müddəti/bildiriş **kodda yoxdur** — sistem yalnız tutulma siyahısını (XLSX) hazırlayır (`payroll-legal-basis.md` §10.2 son sətir) |
| **Hüquqi əsas (kodda)** | `payroll-legal-basis.md` §10.1: m.64, 65.1–65.3, 68.1.4–68.1.6, 75.2–75.8; ƏM m.175.1–175.2, 176.1–176.3. Docs-da mənbə qeyd olunub: azstand.gov.az PDF-i (11.08.2023 tarixli) — **qeyri-rəsmi nüsxə** |

**Tutuşdurma nəticələri (bu sessiyada, §6-da təfərrüat):**

| Maddə | Docs-dakı sitat | azstand nüsxəsi | Nəticə |
|---|---|---|---|
| m.64 | «vergilər tutulduqdan sonra qalan məbləğdən» | eyni | uyğun; amma «vergilər» sözü sosial haqları **açıq** əhatə etmir (aşağıda H-03-1) |
| m.65.1–65.3 | 50% / 65.2 / 70% | eyni | uyğun; 65.4 (təqaüd, pensiya, müəlliflik haqqı) kodda tətbiq edilmir — işəgötürənin əmək haqqına aid deyil |
| m.67 | — | sosial sığorta müavinətlərinə yönəltmə yalnız məhkəmə qərarı/aliment ilə | docs-da yoxdur; əmək haqqı run-una aid deyil |
| m.68.1.4 / 68.1.5 | 68.1.4 sitat edilib; 68.1.5 (işdənçıxarma müavinəti) sitatda var, **kodda qorunmur** | «68.1.5. işdənçıxarma müavinətinin məbləğinə» | **Boşluq:** `PROTECTED_EARNINGS` yalnız `hazard`-dır; işdənçıxarma müavinəti ödənişi kataloqda ayrıca kodla yoxdur (yoxlanıb: `grep severance/işdənçıxarma` — Payroll/Compensation-da nəticə yoxdur) |
| m.75.2–75.8 | sitat edilib | eyni | uyğun |
| m.68.1.3 | — | «18 yaşınadək əlilliyi müəyyən edilmiş» | 172-VIIQD (11.04.2025) bu sözləri «əlilliyi olan uşaq» ilə əvəz edib; payroll hesablamasına təsiri yoxdur |

**Alt suallar və dəyişiklik yerləri**

| Alt sual | «Xeyr» olarsa nə dəyişir | Növ |
|---|---|---|
| **H-03-1.** Baza yalnız **gəlir vergisini** çıxır (sosial haqlar bazada qalır) — m.64 «vergilər» | `PayrollCalculator::calculate()` sətir 148-152: `$statutory` yalnız `income_tax_ee`-ni çıxsın. Nümunə (1 000 AZN): baza 865 → 976; 50% = 432,50 → 488,00, **fərq 55,50 AZN/ay** tələbkarın lehinə. Yanlış «bəli» halda işəgötürən icra sənədi üzrə az köçürür | Kod |
| **H-03-2.** 68.1.5 işdənçıxarma müavinəti qorunan gəlirdir | `EnforcementService::PROTECTED_EARNINGS` (sətir 40) siyahısına müavinətin komponent kodu əlavə edilir (kod kataloqda yaradılmalı); baza çıxarılması `PayrollCalculator` sətir 151 | Kod (+ komponent kataloqu) |
| **H-03-3.** Xitamda köçürmə müddəti və icra məmuruna bildiriş | Qanunun mətnində bu bölmədə işəgötürən öhdəliyi (köçürmə müddəti, xitam bildirişi) **tapılmadı** («işəgötürən» sözü nüsxədə yoxdur). Cavab verilərsə: `final_settlement` run-undan sonra bildiriş/siyahı avtomatlaşdırması (`EnforcementService::reportRows`, sətir 291) | Kod (yeni funksiya) |
| **H-03-4.** Növbə nömrələri/aliment növləri redaksiyada dəyişib | `EnforcementService::defaultPriority()` (sətir 43-53), `EnforcementDocument::KINDS/SEVENTY_PERCENT_KINDS` (sətir 52, 55). Qismən konfiqurasiya: növbə sənəd üzrə əl ilə də verilə bilir (`priority` sahəsi, sətir 77) | Qismən konfiqurasiya / kod |
| **H-03-5.** Xüsusi icra məmurları (249-VIIQD, 14.07.2025) — yeni icra subyekti | `EnforcementDocument::ISSUER_TYPES = ['court','enforcement_officer','notary','other']` (`app/Models/EnforcementDocument.php:59`) — xüsusi icra məmuru «other»/«enforcement_officer» kimi daxil edilə bilir; ayrıca növ lazımdırsa | Kod (kiçik) |

| | |
|---|---|
| **Yanlış olarsa risk** | Az tutma → işəgötürən tələbkar və icra orqanı qarşısında məsuliyyət daşıyır (aliment alan uşağın əleyhinə); çox tutma → işçinin 50%/70% saxlanma hüququ pozulur |
| **Müvəqqəti tövsiyə** | Hazırkı qaydalar işçi üçün daha qorunan istiqamətdədir (sosial haqlar çıxılır, hədd sıxdır); icra sənədi olan işçilər üçün hər ay tutulma siyahısı mühasib tərəfindən icra məmuru ilə tutuşdurulsun; xitam halında icra məmuruna əl ilə bildiriş verilsin |

```
Cavab: [ ] Bəli, kod rəsmi redaksiyaya uyğundur   [ ] Xeyr — alt bənd(lər): H-03-__ , H-03-__
Rəsmi redaksiyanın tarixi / mənbəyi: ______________________________
Tarix: ____.____.________      Kim: ____________________
Əsas: ______________________________      Qüvvəyə minmə: ____.____.________
```

---

### H-04. Yemək kartı və idman zalı imtiyazlarının DSMF (və işsizlik/tibbi) bazasına daxil edilməsi

| | |
|---|---|
| **Sual** | İşəgötürənin ödədiyi yemək kartı və idman zalı dəyəri işçinin gəlir vergisi və sosial sığorta (DSMF, işsizlik, tibbi) bazasına daxildirmi? |
| **Kim** | Mühasib; DSMF və DVX izahı |
| **Hazırkı davranış** | `config/benefits.php:28` — `meal_card`: `taxable=false`, `affects_social=true`; `config/benefits.php:31` — `gym`: `taxable=false`, `affects_social=true`. `affects_social=true` olan imtiyazın işəgötürən payı hər ay `benefit_in_kind` (+) və `benefit_in_kind_offset` (−) sətirləri ilə **bütün** sosial bazaya (DSMF, işsizlik, icbari tibbi) əlavə olunur: `app/Modules/Benefits/Application/Services/BenefitPayrollSync.php:161-230`. Defaultlar yalnız **yeni** paket formasını doldurur (`BenefitTypes::defaults`, `Benefits/Application/Services/BenefitTypes.php:32-43`); mövcud paketin bayraqları `benefit_plans.taxable/affects_social` sütunlarındadır |
| **Hüquqi əsas (kodda)** | VM m.98.5 + m.109.3/119.2 (gəlir vergisi); «Sosial sığorta haqqında» Qanun m.15 (istisnalar siyahısında adi yemək və idman yoxdur) — hərfi oxunuş. `payroll-legal-basis.md` («İmtiyazlar» → «Açıq qalanlar» 1): DSMF təcrübəsi fərqli şərh edə bilər |
| **Açıq mənbə yoxlaması (yeni)** | **DVX, 10.07.2026, «Vergi ödəyicilərinin işçilərlə bağlı çəkilmiş yemək xərcləri ilə bağlı şərh»** (<https://www.taxes.gov.az/az/post/4909>; səhifə iki dəfə oxundu, birinci oxunuşda sitat parçaları gəldi): VM m.119.2 üzrə gündəlik norma **5 manat**; norma daxilində məbləğ «muzdlu işlə əlaqədar əldə olunan gəlirlərə aid edilmədiyindən» **gəlir vergisinə və məcburi dövlət sosial sığorta haqqına cəlb edilmir**; normadan artıq hissə işçinin vergitutulan gəlirinə aid edilir (gəlir vergisi + DSMF); ictimai iaşə müəssisəsindən elektron qaimə ilə alınan yemək üçün məbləğdən asılı olmayaraq cəlb olunmur; **işsizlikdən sığorta və icbari tibbi sığorta haqqına cəlb olunmur** (norma daxilində də, artıqda da). Bu, DVX-in izahıdır, qanun mətni deyil; DSMF-in öz izahı **yoxlanmayıb**. İdman zalı üçün açıq mənbə **tapılmayıb** |

**Hazırkı default ilə DVX izahı arasında fərq (yemək kartı):**

| Komponent | Sistem (hazırkı) | DVX izahı (10.07.2026) |
|---|---|---|
| Gəlir vergisi | vergisiz (`taxable=false`) — normadan artıq hissə də | normadan artıq hissə **vergiyə cəlb olunur** |
| DSMF | **bazaya daxildir** (bütün məbləğ) | norma daxilində daxil deyil; artıq hissə daxildir |
| İşsizlik, icbari tibbi | **bazaya daxildir** | heç vaxt daxil deyil |
| 5 AZN/gün norması | **yoxdur** | var |

| | |
|---|---|
| **«Xeyr» (DVX izahı tətbiq olunsun) olarsa nə dəyişir** | 1) **Dərhal, konfiqurasiya ilə:** yemək kartı paketində `affects_social=false` (**Benefits → Planlar**, `PlansTab` — hər mövcud paket üçün; yeni paketlər üçün `config/benefits.php:28`). Bu, DSMF-i də, işsizlik/tibbi bazanı da götürür — DVX izahına görə norma daxilində düzgündür. 2) **Normadan artıq hissənin** vergi və DSMF-ə cəlbi: sistemdə 5 AZN/gün normasının hesabı yoxdur — `BenefitPayrollSync::syncImputedIncome()` (sətir 161-230) məbləği norma ilə bölməli (iş günü sayı × 5), artıq hissəni `taxable=true, affects_social=true`, norma hissəsini sıfır bayraqla yazmalıdır; işsizlik/tibbi üçün ayrıca bayraq (hazırda tək `affects_social` bayrağı var, `StatutoryEngine::compute` sətir 45 hamısını eyni `socialBase`-dən hesablayır) — **kod dəyişikliyi**. İdman zalı üçün cavab gözlənilir; dəyişiklik eyni yerdə (`config/benefits.php:31`) |
| **Yanlış olarsa risk** | Hazırkı default **çox hesablayır** (DSMF + işsizlik + tibbi) və normadan artıq hissədə gəlir vergisini **az hesablayır**. Nümunə: işəgötürən yemək kartı üçün ayda 100 AZN ödəyir (norma daxilində): sistem işçidən 12,50 (DSMF 10 + işsizlik 0,5 + tibbi 2) və işəgötürəndən 17,50 hesablayır = **30,00 AZN/ay**; 100 işçi × 50 AZN kart → 1 500 AZN/ay, 18 000 AZN/il artıq hesablanmış sığorta haqqı (pillələr 200–8000 AZN aralığında götürülüb). Əks halda (norma artığında) vergi az tutulur |
| **Müvəqqəti tövsiyə** | DVX izahı ilə fərq açıqdır, ona görə **yemək kartı** üçün mühasib qərar verənədək paket bayrağı ilə (`affects_social=false`) yalnız **norma daxilində** olan paketlər işlənsin; norma artıq olan paketlər üçün (gündə 5 AZN-dən çox) bayraqlar əl ilə hesablanıb ayrıca birdəfəlik sətirlə daxil edilsin. İdman zalı üçün qərar gələnədək hazırkı davranış (DSMF-ə daxil, vergisiz) saxlanıla bilər, çünki izah yoxdur |

```
Yemək kartı:  Cavab: [ ] Hazırkı (DSMF+işsizlik+tibbi daxil)   [ ] DVX izahı (norma 5 AZN/gün; işsizlik/tibbi daxil deyil)
İdman zalı:   Cavab: [ ] Hazırkı (DSMF daxil, vergisiz)        [ ] Xeyr — daxil deyil   [ ] Vergiyə cəlb olunur
Tarix: ____.____.________      Kim: ____________________
Əsas: ______________________________      Qüvvəyə minmə: ____.____.________
```

---

### H-05. 18.02.2026-dan əvvəl işdən çıxanlar — «düz 15 il» +2 gün kompensasiya fərqi

| | |
|---|---|
| **Sual** | KMQ16-dan (18.02.2026) əvvəl işdən çıxmış və iş ilinin başlanğıcında staj düz 15 il olan işçilər üçün staj əlavəsinin 4 gün yox, 6 gün olması səbəbindən yaranan **2 günlük kompensasiya fərqi** ödənilməlidirmi? (Qeyd: 6 günlük normaya görə, m.116.1 «on beş ildən çox» — düz 15 il boşluğu: `vacation-legal-basis.md` (b-2)) |
| **Kim** | Hüquqşünas |
| **Hazırkı davranış** | Balansa avtomatik yazılmır; siyahıda `terminated=true`, `before_kmq16=true` kimi işarələnir: `app/Modules/Vacation/Application/Services/FifteenYearSeniorityFix.php:34` (`KMQ16_DATE = '2026-02-18'`), sətir 98 (`if ($row['terminated'] \|\| $row['applied']) continue`), sətir 173. Ekran: Admin → Məzuniyyət normaları → «Staja görə» → «Düz 15 il düzəlişi»; konsol: `php artisan vacation:seniority-fifteen-fix` (`docs/scenario/vacation-ops-commands-guide.md:80`) |
| **Hüquqi əsas (docs)** | ƏM m.116.1, m.7.1, m.7.3, m.135, m.144.2; **m.144.3** (əlavə məzuniyyətlərə xitamda kompensasiya verilmir — **KMQ16 ilə tətbiqi dayandırılıb**, qərar «qüvvəyə mindikdən sonrakı xitamlara və məhkəmələrdə olan işlərə» şamil olunur); m.296.4 (pul tələbi üçün iddia müddəti 1 il, «aşkar etdiyi gün» — m.296.3) |
| **«Xeyr» olarsa** | «Xeyr» = ödəniş olunmasın → **sistemdə dəyişiklik yoxdur** (siyahı yalnız göstərir). «Bəli» = mühasibatlıq «İstifadə olunmamış məzuniyyətə görə kompensasiya» əmri ilə rəsmiləşdirir (`VacationSettings`, m.144.2); avtomatik yazı yoxdur. Əgər (b-2) — «düz 15 il → 6 gün» qaydasının özü rədd edilsə: `vacation_norms` cədvəlində `seniority_15` sətri (`min_inclusive=true`) geri qaytarılır (**konfiqurasiya**; miqrasiya `2026_10_11_190000_include_full_fifteen_years_in_seniority_leave`) və yazılmış düzəlişlər `source = seniority15-fix:{tabel}:{iş ili}` açarı ilə tapılıb geri alınır |
| **Yanlış olarsa risk** | Ödənilməli olsa və ödənilməsə → işçinin iddiası (pul tələbi 1 il ərzində, aşkar etdiyi gündən), m.7.3 üzrə ziyanın tam ödənilməsi. Ödənilməməli olsa və ödənilsə → könüllü artıq ödəniş (qadağan deyil, amma geri tutula bilməz — m.175.5). Miqyas: **2 gün × orta günlük** (m.140.3: orta aylıq ÷ 30,4); 1 500 AZN → 49,34 × 2 = **98,68 AZN** işçi başına, hər təsirlənmiş iş ili üçün (xitamda «bütün iş illəri» — m.144.2) |
| **Müvəqqəti tövsiyə** | Avtomatik ödəniş olmasın (indiki kimi); təsirlənən xitam olunmuşların siyahısı (konsol/ekran) hüquqşünasa verilsin; iddia müddəti (1 il) izlənsin — erkən xitam olunanlar üçün müddət xitam günündən sayılır (m.296.3 «aşkar etdiyi gün» mübahisəlidir) |

```
Cavab: [ ] Ödənilməsin   [ ] Ödənilsin (kompensasiya əmri ilə)   [ ] Yalnız müraciət edənlərə
Tarix: ____.____.________      Kim: ____________________
Əsas: ______________________________
```

---

### H-06. NK 56 üzrə şirkət statusu (neft-qaz / dövlət / özəl) və 50% subpodratçı həddi

| | |
|---|---|
| **Sual** | Şirkət Nazirlər Kabinetinin 18.02.2019 tarixli 56 nömrəli Qərarı ilə hansı sektora aiddir (özəl qeyri-neft-qaz / neft-qaz / dövlət sektoru)? Subpodratçıdırsa, əvvəlki ilin gəlirinin 50%-dən çoxu neft-qaz müqavilələrindəndirmi? |
| **Kim** | Mühasib; təsdiq — Vergi/DSMF orqanı və ya şirkətin audit rəyi |
| **Hazırkı davranış** | Sektor `settings` cədvəlində, ad `Payroll company sector` (`app/Modules/Compensation/Application/Services/CompanySectorSettings.php:28`). Seçilməyibsə quraşdırma profilindən: `military` → hərbi, `public` → dövlət, **qalan hamısı → özəl qeyri-neft-qaz** (sətir 47-50, 62-67). Sektor → rejim: `private_non_oil→private`, `oil_gas→state`, `state→state`, `military→military` (sətir 33-40). Dərəcələr rejimə görə: `private` (m.101.1-1: 3% və s.) vs `state` (14%/25%). **50% həddi izlənmir** — sektor əl ilə dəyişdirilir və toplu düzəliş `RegimeAlignmentService::realign()` (`app/Modules/Compensation/Application/Services/RegimeAlignmentService.php:89`) ilə icra olunur |
| **Hüquqi əsas (docs)** | VM m.101.1, 101.1-1, 101.1-4; NK 56, b.2.1.1, 2.1.2.1, 2.1.2.2 (50% həddi; yeni subpodratçı üçün status növbəti ayın 1-dən dəyişir), b.2.2 (qeyri-dövlət sektoru); «Sosial sığorta haqqında» m.14.3/14.4/14.7; `payroll-legal-basis.md` §8. Meyar mətnləri DVX (<https://www.taxes.gov.az/az/post/321>) və imzalı PDF (audit.gov.az) ilə oxunub; bu sessiyada **yenidən yoxlanmayıb** |
| **«Xeyr» olarsa nə dəyişir** | a) **Sektor səhvdirsə:** Kompensasiya → **Rejim və sektor** ekranında sektor dəyişdirilir (səbəblə, audit `company_sector_changed`), sonra «Rejimi şirkət standartından fərqli olan işçilər» siyahısından toplu düzəliş (`realign`, ayın 1-i, kilidli aydan sonra) — **konfiqurasiya**. b) **Keçmiş aylar:** `Maaş → Vergi düzəliş hesabatı` + qaytarma (`payroll-legal-basis.md` §11). c) **50% həddinin avtomatik izlənməsi:** gəlir bölgüsünün (neft-qaz müqavilələri / ümumi) uçotu və ayın 1-də statusun dəyişməsi sistemdə **yoxdur** — **kod dəyişikliyi** (yeni məlumat və yoxlama); hazırda yalnız əl ilə |
| **Yanlış olarsa risk** | **Neft-qaz/dövlət** şirkəti səhvən `private` sayılsa: işəgötürən **az vergi** tutur və **az DSMF işəgötürən payı** ödəyir, işçidən isə **artıq DSMF** tutur. Nümunə (1 100 AZN, `payroll-legal-basis.md` §8.3): vergi 27 (olmalı 126) → **99 AZN az**; DSMF işçi 96 (olmalı 33) → **63 AZN çox**; DSMF işəgötürən 179 (olmalı 242) → **63 AZN az** → işəgötürən üzrə **162 AZN/ay** çatışmazlıq, işçi isə cəmi 36 AZN az ödəyir. 100 işçi → ≈ 16 200 AZN/ay büdcəyə borc + faiz/sanksiya. Tərsi (özəl şirkət səhvən `state`): işçilərdən 99 AZN/ay artıq vergi tutulur |
| **Müvəqqəti tövsiyə** | Sektor **açıq şəkildə** seçilsin (profildən götürülməsin) və səbəb yazılsın; subpodratçı olan şirkət üçün mühasib hər ilin yanvarında əvvəlki il gəlirini hesablayıb nəticəni `Rejim və sektor` ekranında qeyd etsin (yeni subpodratçı üçün yarım ildə bir) və 50% həddinə yaxınlaşanda hüquqşünas/Vergi orqanı ilə razılaşdırsın. Çoxşirkətli quraşdırma varsa: sektor tək `settings` açarıdır (`payroll-legal-basis.md` §8.3 ⚠️) |

```
Cavab: Sektor: [ ] Özəl qeyri-neft-qaz   [ ] Neft-qaz   [ ] Dövlət   [ ] Hərbi
Subpodratçı: [ ] Bəli, 50%-dən çox   [ ] Xeyr   [ ] Tətbiq olunmur
Tarix: ____.____.________      Kim: ____________________
Əsas (sənəd/hesablama): ______________________________      Qüvvəyə minmə: ____.____.________
```

---

### H-07. Xitamda qalan borcun 20% həddindən artıq tutulması — işçi razılığı

| | |
|---|---|
| **Sual** | İşçinin ayrıca yazılı razılığı ilə xitamda qalan kredit/avans borcu ƏM m.176.1-in 20% həddindən **artıq** tutula bilərmi? |
| **Kim** | Hüquqşünas |
| **Hazırkı davranış** | Hədd hər halda tətbiq olunur; razılıq istisnası yoxdur: `app/Modules/Payroll/Application/Services/DeductionCap.php:21` (`LEGAL_RATIO = 0.20`), sətir 26 (`min(LEGAL_RATIO, config)` — konfiqurasiya yalnız **aşağı** sala bilər). Xitamda: `LoanService::settlement()` (`app/Modules/Payroll/Application/Services/LoanService.php:80-92`), çağırış `PayrollCalculator::calculate()` sətir 179; sığmayan hissə kreditdə `remaining > 0, active` qalır, avtomatik tutulmur. Konfiqurasiya: `config/payroll.php:52` (`deduction_cap_ratio`, `PAYROLL_DEDUCTION_CAP_RATIO`) |
| **Hüquqi əsas (docs)** | ƏM m.175.1, 175.2, 175.5, 175.6, **176.1** («tutulan bütün məbləğlərin ümumi miqdarı … iyirmi faizindən … artıq ola bilməz»); Ali Məhkəmənin birgə qərarı (2022, xəbər: <https://www.e-huquq.az/az/news/mehkeme/59037.html>, köməkçi mənbə): 20%-dən artığın qabaqcadan razılıqla tutulmasına dair müqavilə şərti etibarsızdır. `payroll-legal-basis.md` §4.1: razılıqla hədd aşılmasına icazə verən norma **yoxdur** |
| **«Xeyr» (razılıqla artıq tutula bilər) olarsa nə dəyişir** | **Kod dəyişikliyi:** (1) `DeductionCap::ratio()` sətir 24-27 və `LEGAL_RATIO` sətir 21 — hədd işçi razılığına görə fərqli olmalıdır; (2) işçi üzrə yazılı razılığın qeydi (hazırda **yoxdur** — `payroll-legal-basis.md` §3 ⚠️, §4 ⚠️): yeni sahə/cədvəl + sənəd faylı; (3) `LoanService::settlement()` sətir 92-də `DeductionCap::room(...)` əvəzinə razılıq varsa 1.0; (4) test və UI izahı. Konfiqurasiya ilə bu mümkün deyil — `0.20`-dən böyük dəyər ataraq 0.20-yə sıxılır |
| **Yanlış olarsa risk** | «Xeyr» səhv olsa (razılıq etibarsızdırsa) — işçidən artıq tutulan məbləğ geri qaytarılmalı, ziyan işəgötürənə aid olur (m.175.5). «Bəli» səhv olsa — qalan borc tutulmur, işəgötürən məhkəmə yolu ilə tələb etməli olur. Nümunə: ümumi hesablanmış 1 000, vergi/sosial 135 → baza 865, hədd **173 AZN**; qalan borc 700 olsa **527 AZN** kreditdə qalır. Qeyd: xitam run-unda birdəfəlik/əmr ödənişləri (məs. məzuniyyət kompensasiyası) **bazaya daxil edilmir** (`payroll-legal-basis.md` §4) — hədd bazasının bunlarla genişlənib-genişlənməməsi ayrı hüquqi sualdır |
| **Müvəqqəti tövsiyə** | Hədd 20% saxlanılsın; qalan borc üçün ayrıca könüllü ödəniş sazişi və ya məhkəmə qaydası; **xitam sənədlərində** borcun qalığı işçiyə yazılı bildirilsin (kreditin `remaining` dəyəri ekranda görünür) |

```
Cavab: [ ] Hədd həmişə 20% (hazırkı)   [ ] Razılıqla artıq tutula bilər — hədd: ____%
Tarix: ____.____.________      Kim: ____________________
Əsas: ______________________________      Qüvvəyə minmə: ____.____.________
```

---

### H-08. Vergi düzəlişi: artıq tutulan verginin işçiyə qaytarılması; DSMF/işsizlik/tibbi düzəlişi

| | |
|---|---|
| **Sual** | (a) İşəgötürən artıq tutulmuş gəlir vergisini düzəliş hesabatından sonra **əmək haqqı ilə işçiyə qaytara bilərmi**, yoxsa yalnız dəqiqləşdirilmiş bəyannamə ilə və işçinin özünün 149.5 müraciəti ilə? (b) İşçidən tutulmuş DSMF/işsizlik/tibbi artığı və işəgötürən payı üzrə düzəliş necə aparılır? (c) Az tutulmuş vergi üçün 58.1 sanksiyası düzəlişdən əvvəl tətbiq olunurmu? |
| **Kim** | Mühasib; Vergi orqanı (VM m.72.5, 87, 149.5, 150.4, 58.1) və DSMF |
| **Hazırkı davranış** | `Maaş → Vergi düzəliş hesabatı`: yalnız oxu + XLSX (`TaxAdjustmentReportService::build/recompute`, `app/Modules/Payroll/Application/Services/TaxAdjustmentReportService.php:55, 104`). **Qaytarma** — mühasibin açıq təsdiqi ilə (`recordRefunds`, sətir 178): yalnız **müsbət** fərq, yalnız işçi tərəfi komponentlər (`EMPLOYEE_SIDE`, sətir 43), standart seçim yalnız gəlir vergisi (`TaxAdjustmentTab.php:33`); kodu `tax_adjustment_refund`, vergidən və sosial bazadan **kənar** birdəfəlik ödəniş (sətir 227); az tutulmuş məbləğ avtomatik tutulmur. DSMF/işsizlik/tibbi fərq hesabatda göstərilir, amma onların bəyannamə düzəlişi sistemdə yoxdur |
| **Hüquqi əsas (docs)** | VM m.72.5, 87.1, 87.3, 149.5, 150.3, 150.4, 58.1, 85.5. `payroll-legal-basis.md` §11.1: «işəgötürənin artıq tutduğu vergini işçiyə necə qaytarması VM-də **açıq yazılmayıb** ⚠️»; DSMF/işsizlik/tibbi düzəliş qaydası «**yoxlanmayıb** ⚠️» |
| **«Xeyr» olarsa nə dəyişir** | (a) Qaytarmanın yalnız bəyannamə yolu ilə olması → `recordRefunds` istifadə edilməsin (**prosedur**, kod dəyişməz) və ya düymə icazə ilə söndürülsün (**kiçik kod**: `TaxAdjustmentTab` / `export-payroll` icazəsi). (b) DSMF/işsizlik/tibbi üçün qaytarma qadağandırsa — `TaxAdjustmentTab.php:33,132-133` və `EMPLOYEE_SIDE` (sətir 43)-dən `dsmf_ee`, `unemployment_ee`, `medical_ee` çıxarılır (**kod**). (c) Hesabat «qayda tarixi» fərziyyəsi (il ərzində qanun dəyişməyib) — `rulesDate()` (sətir 261) |
| **Yanlış olarsa risk** | Qaytarma düzgündürsə və edilməsə — işçi 149.5 üzrə özü müraciət edir (məsuliyyət işəgötürənə düşmür). Qaytarma düzgün deyilsə və edilsə — ikiqat qaytarma riski (işçi də bəyannamə versə) və işəgötürənin xalis xərci. Nümunə (`TaxAdjustmentReportTest`): 1 100 AZN, 102.1.6 verilməyib (vergi 33 yerinə 27) → **6,00 AZN/ay**; 6 ay = 36 AZN/işçi; 500 işçi ≈ 18 000 AZN |
| **Müvəqqəti tövsiyə** | `Vergi düzəliş hesabatı` yalnız **oxu** rejimində istifadə edilsin; qaytarma düyməsi mühasib qərar verənədək işlədilməsin; dəqiqləşdirilmiş bəyannamə (72.5) ayrıca hazırlansın; az tutulmuş vergi üçün 58.1 «yoxlamadan əvvəl düzəliş» istisnası **yoxlanmayıb** — vergi məsləhətçisi ilə |

```
(a) İşçiyə əmək haqqı ilə qaytarma: [ ] Bəli   [ ] Xeyr, yalnız bəyannamə/işçinin müraciəti ilə
(b) Qaytarıla bilən komponentlər: [ ] yalnız gəlir vergisi   [ ] + DSMF   [ ] + işsizlik   [ ] + tibbi
(c) Az tutulmuş vergi: [ ] işçidən tutulmur (hazırkı)   [ ] işçidən tutulsun
Tarix: ____.____.________      Kim: ____________________
Əsas: ______________________________
```

---

### H-09. Hamiləlik/doğuş məzuniyyətinin əmək şəraitinə görə əlavə məzuniyyət stajına daxil edilməsi

| | |
|---|---|
| **Sual** | ƏM m.125 üzrə hamiləlik və doğuş məzuniyyəti müddəti NK 95-ə əsasən əmək şəraitinə görə əlavə məzuniyyət stajına **sayılırmı**? |
| **Kim** | Hüquqşünas |
| **Hazırkı davranış** | **Sayılır** (boşluq kimi qeyd edilmir): staja yalnız ödənişsiz məzuniyyət (effekt `unpaid_leave`) və uşağa qulluq (`usaga_qulluq_mezuniyyeti`) daxil edilmir — `app/Services/Vacation/OrderLeaveFacts.php:37-44` (`seniorityGaps`), `VacationSettings::WORK_YEAR_EXCLUDED_TEMPLATES` (`VacationSettings.php:29`); gün şəraitdə sayılırsa `VacationNormEvaluator::countedNormOn()` (sətir 267-276) |
| **Hüquqi əsas (docs)** | NK 95, b.8 (sayılan dövrlər qapalı siyahıdır: faktiki iş, iş yeri və əmək haqqı saxlanılan dövr, məcburi işburaxma, müvəqqəti əmək qabiliyyətsizliyi, bəraət alanın dövrü) — hamiləlik/doğuş **adı çəkilmir**; ƏM m.125. `vacation-legal-basis.md` (c-2) «qalıq risk 2» — işçinin xeyrinə şərhdir |
| **«Xeyr» olarsa nə dəyişir** | Hamiləlik/doğuş məzuniyyəti şablonlarının kodları `OrderLeaveFacts::seniorityGaps()`-ə (sətir 37-44) əlavə edilir (və ya yeni sabit `VacationSettings`-də). **Kod dəyişikliyi** (+ test `VacationNormsTest`). Texniki olaraq həmin əmr şablonunun effektini `unpaid_leave`-ə dəyişməklə də alınar, amma bu effekt başqa yerlərdə də işlənir (`OrderPeriodGuard`, `OrderEffectCatalog`) — tövsiyə olunmur. Doğuş şablonunun dəqiq kodu bu sessiyada yoxlanmayıb |
| **Yanlış olarsa risk** | «Bəli» səhvdirsə — işçiyə artıq əlavə məzuniyyət günü verilir (lehinə). «Xeyr» səhvdirsə — işçinin haqqı pozulur (m.7.3 ziyan). Miqyas: 12 günlük norma üçün hər 30,4 şərait günü ≈ 1 gün; 4 ay = **4 gün** (NK 95 b.10 + b.7) |
| **Müvəqqəti tövsiyə** | Hazırkı davranış (sayılır) saxlansın — işçi üçün əlverişlidir, m.7.3 ziyan riskini azaldır |

```
Cavab: [ ] Bəli, sayılır (hazırkı)   [ ] Xeyr, sayılmır
Tarix: ____.____.________      Kim: ____________________
Əsas: ______________________________      Qüvvəyə minmə: ____.____.________
```

---

### H-10. 6 ay növbəti şərait ilində tamamlanan hal

| | |
|---|---|
| **Sual** | Birinci şərait ilində şəraitdə 6 aydan az işlənibsə və 6 ay yalnız **növbəti** şərait ilində tamamlanırsa, birinci ilin aylarına görə əlavə məzuniyyət günləri geriyə dönük verilməlidirmi? |
| **Kim** | Hüquqşünas |
| **Hazırkı davranış** | Birinci il üçün **0**, ikinci il üçün həmin ilin mütənasib günləri: `VacationNormEvaluator::conditionsDays()` (`app/Modules/Vacation/Application/Services/VacationNormEvaluator.php:165-207`; sətir 179: `conditionsDaysUntil(...) / 30.4 < 6 → 0`). Nümunə (docs): 2024-də 4 ay, 01.03.2025-dən qayıdış → 2024: 0, 2025: 10 gün |
| **Hüquqi əsas (docs)** | ƏM m.131.6; NK 95, b.7 («üst-üstə azı altı ay»), b.10, b.11. Mətn bu halı **həll etmir**; `vacation-legal-basis.md` (c-2): «Mövcud saxlanıldı, hüquqşünas təsdiqi lazımdır» |
| **«Xeyr» olarsa** | Hüquq yarananda birinci ilin aylarının da verilməsi: `conditionsDays()` (sətir 177-206) əvvəlki şərait ilinin aylarını da cəmləməli; dondurulmuş iş illərini (`vacation_work_years`) geriyə dəyişməyi tələb edir — onun əvəzinə `vacation_balance_entries.kind = adjustment` hərəkəti (H-05-dəki mexanizm kimi). **Kod dəyişikliyi + məlumat düzəlişi** (konfiqurasiya yoxdur) |
| **Yanlış olarsa risk** | «Xeyr» səhvdirsə — işçi 3–6 gün az alır (nümunədə 4 gün), m.7.3 üzrə ziyan; «Bəli» səhvdirsə — artıq günlər (işçinin lehinə, geri tutulmur). Miqyas: təsirlənən işçi azdır |
| **Müvəqqəti tövsiyə** | Hazırkı davranış saxlansın; təsirlənən işçilər (6 ay növbəti ildə tamamlananlar) siyahıdan hüquqşünasa ötürülsün |

```
Cavab: [ ] Hazırkı (birinci il 0)   [ ] Xeyr — birinci ilin ayları da verilsin
Tarix: ____.____.________      Kim: ____________________
Əsas: ______________________________      Qüvvəyə minmə: ____.____.________
```

---

### H-11. KPI bonusunun hüquqi təbiəti (əsasnamə / müqavilə) və dövr bitmədən çıxan işçinin bonusu

| | |
|---|---|
| **Sual** | KPI bonusu qanunla tənzimlənmir; şirkətin əsasnaməsi/əmək müqaviləsi hansı qaydanı müəyyən edir: dövr bitmədən **işdən çıxan** işçiyə bonus (a) işlənmiş günlərə mütənasib, (b) dövrün sonuna qədər tam, (c) verilmir? |
| **Kim** | Hüquqşünas (şirkətin daxili əsasnaməsi/kollektiv müqavilə ilə) |
| **Hazırkı davranış** | Standart `prorata`: `config/performance_evaluation.php:13` (`kpi.early_close_bonus`, env `PERFORMANCE_KPI_EARLY_CLOSE_BONUS`); dövr üzrə qayda `performance_bonus_rules.early_close_policy` (ekran: KPI → Bonus → «İşdən çıxan əməkdaşın bonusu»); tətbiq: `BonusService::bonusProrata()` (`app/Modules/PerformanceEvaluation/Application/Services/Kpi/BonusService.php:276-289`). Vəzifə dəyişikliyi ilə bağlanan kart həmişə pro-rata. Bonus payroll-a `kpi_bonus` kodu ilə birdəfəlik ödəniş kimi verilir; defaultlarla **vergiyə və sosial bazaya cəlb olunan** (`PayrollOneOffEarnings::record(... $taxable = true, $affectsSocial = true)`, `BonusService::exportToPayroll` sətir 378-381) |
| **Hüquqi əsas (docs)** | `performance-evaluation-module-design.md` §17.1: «Əmək Məcəlləsi KPI bonusunu ayrıca tənzimləmir»; seçim əmək müqaviləsi/kollektiv müqavilə/daxili əsasnamə ilə müəyyən edilir; «⚠️ Konkret maddə nömrələri bu sənəddə təsdiqlənməyib» |
| **«Xeyr» olarsa** | **Konfiqurasiya ilə:** (1) şirkət standartı — `.env` `PERFORMANCE_KPI_EARLY_CLOSE_BONUS=full\|none\|prorata` və ya `config/performance_evaluation.php:13`; (2) hər dövr üçün — KPI → Bonus ekranında qayda; mövcud dövrlər miqrasiya ilə `prorata` alıb (`2026_10_11_120000_add_early_close_policy_to_performance_bonus_rules_table.php`) və dəyişmir. Kod lazım deyil |
| **Yanlış olarsa risk** | Kimin lehinə/əleyhinə seçimə bağlıdır: `full` işəgötürənin əleyhinə (işlənməmiş günlər üçün ödəniş), `none` işçinin əleyhinə (qazanılmış mükafat). Nümunə: hədəf 1 000 AZN, nəticə 100%, dövrün 90 gününün 45-ni işləyib: `prorata` **500**, `full` **1 000**, `none` **0** |
| **Müvəqqəti tövsiyə** | `prorata` saxlansın (docs-dakı əsaslandırma: iştirak payına mütənasib); əsasnamədə «bonus yalnız dövrün sonunda işləyənlərə» yazılıbsa `none` seçilsin; qərar əsasnamənin tarixi və maddəsi ilə qeyd edilsin |

```
Cavab: [ ] prorata   [ ] full   [ ] none
Əsasnamə/müqavilə maddəsi: ______________________________
Tarix: ____.____.________      Kim: ____________________      Qüvvəyə minmə: ____.____.________
```

---

## 5. Əlavə bəndlər (kodda/docs-da «⚠️», «təsdiq», «mühasib/hüquqşünas» ilə qeyd olunanlar)

Qısa formada; hər birinin «Xeyr» halı üçün dəyişiklik yeri göstərilib. **Cavab / tarix / kim** sahəsi ayrıca cədvəldədir (§5.1).

| ID | Sual | Hazırkı davranış | Əsas | «Xeyr» olarsa dəyişiklik | Növ | Kim |
|---|---|---|---|---|---|---|
| **E-01** | VM 102.1.6 üçün «aylıq gəlir 2500» yoxlanışı və off-cycle: ayın bütün ödənişləri cəmlənməlidirmi? | Hər run ayrıca (taxable baza); off-cycle güzəşt vermir, amma adi run-da verilmiş güzəşt geri alınmır | `payroll-legal-basis.md` §5.1 ⚠️, §7.7; `PayrollCalculator::calculate()` sətir 56, 141 (`$monthlyRelief`) | Ay üzrə cəm baza və güzəşt qaytarması — `PayrollCalculator` / `StatutoryEngine::reliefFor()` | Kod | Mühasib |
| **E-02** | VM 102.8: güzəşt sənədlərinin siyahısı və 102.5 üçün himayədəkilərin sayının izlənməsi | Sistem sənəd növünü yoxlamır, yalnız qeyd edir; 102.5 kart tarixləri ilə | §9.4 ⚠️; `employee_tax_reliefs` | Sənəd növü siyahısı və himayədə olan sayının uçotu | Kod | Mühasib |
| **E-03** | ƏM m.136.2 (332-VIIQD): staj + şərait əlavələri **toplanır**; qüvvəyə minmə tarixi təsdiqlənməyib — 2026-dan əvvəlki iş illərində köhnə qayda (ən böyüyü) tətbiq olunmalıdırmı? | Həmişə toplanır: `EntitlementBreakdown::total()` (`app/Modules/Vacation/Application/Services/EntitlementBreakdown.php:21-24`); tarix parametri **yoxdur** | `vacation-legal-basis.md` (h) «Qüvvəyə minmə tarixi: Təsdiqlənmədi»; «Sistem üçün nəticələr» 8 | `VacationNormEvaluator::evaluate()` sətir 75-79 — iş ilinin başlanğıcı tarixdən əvvəldirsə `max(seniority, conditions)`; tarix `VacationSettings`-də | Kod | Hüquqşünas |
| **E-04** | İşləmə dövründə məzuniyyət kompensasiyası (ƏM m.135.2) | **Bağlıdır**: `Vacation compensation without termination allowed` (`VacationSettings.php:20`, ekran: Admin → Tənzimləmələr) | `vacation-legal-basis.md` (g), «Sistem üçün nəticələr» 17: «Təsdiqlənmədi» | Ayarı `1/bəli` etmək — **konfiqurasiya** | Konfiqurasiya | Hüquqşünas |
| **E-05** | Natamam iş ili üçün kompensasiya düsturu (günlərə mütənasib) | Məcəllədə düstur bu bölmədə yoxdur — «Təsdiqlənmədi» | `vacation-legal-basis.md` (g) | Kompensasiya əmri günləri və orta əmək haqqı (m.140.3) — Vacation/Orders modulu | Kod | Hüquqşünas |
| **E-06** | Hərbçi DSMF bazası («pul təminatı») siyahısı: (1) «ərzaq payı əvəzinə pul kompensasiyası» əlavə edilsin? (2) büdcədənkənar vəsait hesabına əlavələr çıxarılsın? (3) icbari tibbi sığortanın hərbçi üçün bazası | Siyahı: `base, rank_supplement, seniority, hazard, secrecy, language` (`2026_10_11_150300_seed_military_dsmf_base_rule.php:49`); tibbi ümumi sosial bazadan | `payroll-legal-basis.md` §6.3(c) ⚠️ | `statutory_exemptions` `military_dsmf_pay_allowance.component_codes` redaktəsi (**konfiqurasiya**); tibbi üçün ayrıca sətir `component_code='medical'` (konfiqurasiya) | Konfiqurasiya | Mühasib |
| **E-07** | Hərbi rütbə maaşı kataloqda başqa kodla saxlanırsa vergi/DSMF siyahısı | `vm_102_1_14_15` siyahısı: `base`, `rank_supplement` | §6.3(b) ⚠️ | `statutory_exemptions.component_codes` redaktəsi | Konfiqurasiya | Mühasib |
| **E-08** | Saatlıq vəzifə maaşı = aylıq vəzifə maaşı ÷ həmin ayın fərdi norması (m.164 üçün) | `AttendanceWorkNormService::employeeMonthNorm` | §1, §7.1–7.2 ⚠️ | `OrderEarningsService` formulası | Kod | Mühasib |
| **E-09** | m.164 «norma çərçivəsində» anlayışı və cəmlənmiş uçot (m.96); kollektiv müqavilədə daha yüksək məbləğ | Norma daxili = 1×, artıq = 2×; kollektiv müqavilə nəzərə alınmır | §7.2, §7.4 ⚠️ | `OrderEarningsService` — sətir/qayda | Kod | Mühasib + hüquqşünas |
| **E-10** | Müvəqqəti əvəzetmə (m.162): iş günlərinə mütənasiblik; razılaşdırılmış əlavə fərqdən çox olsa çoxu ödənsin | Qeyd olunan düstur: `aylıq = max(fərq, razılaşdırılmış)`; faktiki iş günlərinə mütənasib | §2, §7.3 ⚠️ | `OrderEarningsService` | Kod | Mühasib |
| **E-11** | Ləğv olunmuş əmr üzrə artıq ödənişin işçidən tutulması üçün **fərdi** yazılı razılıq qeydi | `recover_revoked_order_pay` **söndürülüb** (`config/payroll.php:22`); fərdi razılıq mexanizmi yoxdur | §3 ⚠️ | `PAYROLL_RECOVER_REVOKED_ORDER_PAY=true` (konfiqurasiya, bütün qurulum üçün) + fərdi razılıq qeydi (kod) | Konfiqurasiya + kod | Hüquqşünas |
| **E-12** | Kredit/avans taksitinin işçinin yazılı razılığı ilə tutulması (m.175.6) — razılıq qeydi | Sistem razılığı **saxlamır** (kredit rəsmiləşdirilərkən fərz olunur) | §4 ⚠️ | Kredit qeydində razılıq sənədi sahəsi | Kod | Hüquqşünas |
| **E-13** | Könüllü tutulma komponentləri (məs. həmkarlar ittifaqı) 20%-lik həddə sayılır, amma özləri azaldılmır — yer qalmırsa kredit 0 | `PayrollCalculator::calculate()` sətir 177-181 | §4.1 ⚠️ | Komponent tutulmalarını həddə görə azaltmaq + qalıq uçotu | Kod | Hüquqşünas |
| **E-14** | Zərərlilik əlavəsi brüt məbləği ilə çıxılır (vergi payı ayrılmır) icra bazasından | `PayrollCalculator` sətir 151 | §10.2 ⚠️ | Zərərlilik üzrə xalis hissə | Kod | Hüquqşünas |
| **E-15** | Kredit/avans icra sənədləri ilə birlikdə ümumi 50/70% «ümumi tavan» oxunuşu; retro geri tutulmada eyni qayda | `PayrollCalculator::calculate()` sətir 184-186 | §10.2 ⚠️ | Sətir 185 | Kod | Hüquqşünas |
| **E-16** | Sabit aylıq icra məbləği yalnız ayın öz ödənişində, faiz hər ödənişdə (off-cycle da) | `EnforcementService::plan()` sətir 144-146 | §10.2 ⚠️ | `$includeFixed` məntiqi | Kod | Hüquqşünas |
| **E-17** | Staj əlavəsi iş ilinin başlanğıcı tarixinə görə qiymətləndirilir (məzuniyyət tarixinə yox) | `VacationNormEvaluator::seniorityBandApplies()` sətir 319 | `vacation-legal-basis.md` (b-2) «qalıq risk 3», «Sistem üçün nəticələr» 3 | Tarixin seçimi (konfiqurasiya parametri kimi) | Kod | Hüquqşünas |
| **E-18** | Əvvəlki əmək fəaliyyəti (m.116.2) natamam daxil edilibsə «düz 15 il» tarixi səhv ola bilər | Düzəliş faktlara əsaslanır | (b-3) «qalıq risk 3» | Əl ilə yoxlama | Prosedur | HR + hüquqşünas |
| **E-19** | NK 95 b.10: «iş günləri ÷ 30,4» — sistem təqvim günlərini sayır (A variantı); rəsmi izah yoxdur; b.12-nin 90% faktı avtomatik bilinmir | `VacationNormEvaluator::fullMonths()` (sətir 40-43) | (c-3) «qalıq risk» | Düstur `fullMonths()` | Kod | Hüquqşünas |
| **E-20** | NK 95 b.7, 1-ci misalda 3 gün; sistem b.8.2-yə görə 4 verir | Sistem 4 | (c-2) «qalıq risk 4» | — | — | Hüquqşünas |
| **E-21** | Qarışıq vəzifəli iş ilində 21 ↔ 30 günlük əsas məzuniyyətin köçürmə günündə proporsional bölünməsi | Proporsiya tətbiq olunmur; qayda təsdiqlənməyib | `vacation-legal-basis.md`, «11.10.2026 — köçürmə …» | Əsas norma düsturu | Kod | Hüquqşünas |
| **E-22** | Md. 119 yaş qaydası: iş ili ərzində yaş dəyişirsə qayda hansı tarixə görə | İş ilinin başlanğıcı | «Sistem üçün nəticələr» 2 | Parametr | Kod | Hüquqşünas |
| **E-23** | Md. 114.6: bayramın köçürülməsindən yaranan istirahət günü məzuniyyətə sayılırmı | Təsdiqlənməyib | (h) «Təsdiqlənmədi» | İstehsalat təqvimi qaydası | Kod | Hüquqşünas |
| **E-24** | ƏM m.117.1 (172-VIIQD, 11.04.2025): «18 yaşınadək əlilliyi müəyyən edilmiş» → «əlilliyi olan uşaq»; uşaqlı valideyn əlavəsinin əlilliyi olan uşağa görə 5 günü bu redaksiya ilə tutuşdurulsun | `childrenRuleApplies()` / `disabledChildren()` (`VacationNormEvaluator.php:345-380`); «əlilliyi var» qeydi | `172-VIIQD` (§6) — **yoxlanmayıb**: m.117 yeni redaksiyasının tam mətni | `disabledChildren()` yaş həddi | Kod | Hüquqşünas |
| **E-25** | HRM-55: «Publik» şirkət profili hansı rejim/hesablama profilini göstərir; adı vergi kateqoriyasını sübut etmir | Sektor yoxdursa `public` → `state` | `integration-security-and-production-proposal.md` §5.4 | `CompanySectorSettings` (H-06 ilə eyni) | Konfiqurasiya | Mühasib/HR |
| **E-26** | Təkrar KPI faktı: əvəz edilir, yoxsa toplanır; erkən bağlanan kartın (köçürmə/texniki) bonusu | İdxal — **əvəz** (snapshot); köçürmə — həmişə pro-rata | `performance-evaluation-module-design.md` §17.2; `integration-security-and-production-proposal.md` §5.4 | `ScorecardService::recordActual(replace: …)` | Kod | HR/hüquqşünas |
| **E-27** | Vergi düzəlişi hesabatındakı «qayda tarixi» fərziyyəsi: il ərzində qanun dəyişməyib (2026) | `rulesDate()` sətir 261 | `TaxAdjustmentReportService` sənəd şərhi ⚠️ | Qayda tarixi — hər ay üzrə | Kod | Mühasib |
| **E-28** | İşəgötürən xərcində «həyat/pensiya sığortası» VM 102.1.8: 3 il və gəlirin 50%-i həddi; mobil telefon (şəxsi istifadə hissəsi, VM 98.2.2) | Avtomatik yoxlanmır; mobil — ya tam gəlir, ya tam işgüzar xərc | `payroll-legal-basis.md` («İmtiyazlar» → «Açıq qalanlar» 2–3) | `BenefitPayrollSync` + paket sahələri | Kod | Mühasib |

### 5.1. Əlavə bəndlər üçün cavab cədvəli

| ID | Cavab (Bəli / Xeyr / Qismən) | Tarix | Kim | Əsas / qeyd |
|---|---|---|---|---|
| E-01 | | | | |
| E-02 | | | | |
| E-03 | | | | |
| E-04 | | | | |
| E-05 | | | | |
| E-06 | | | | |
| E-07 | | | | |
| E-08 | | | | |
| E-09 | | | | |
| E-10 | | | | |
| E-11 | | | | |
| E-12 | | | | |
| E-13 | | | | |
| E-14 | | | | |
| E-15 | | | | |
| E-16 | | | | |
| E-17 | | | | |
| E-18 | | | | |
| E-19 | | | | |
| E-20 | | | | |
| E-21 | | | | |
| E-22 | | | | |
| E-23 | | | | |
| E-24 | | | | |
| E-25 | | | | |
| E-26 | | | | |
| E-27 | | | | |
| E-28 | | | | |

---

## 6. Açıq mənbə yoxlama jurnalı (11.10.2026)

| Mənbə | Nəticə | Status |
|---|---|---|
| e-qanun.az (konsolidasiya, «İcra haqqında» m.64–75) | Bu qanunun framework nömrəsi tapılmadı (1–400 aralığı yoxlandı: nəticə yox); başqa qanunların `frameworks/<id÷1000>/f_<id>.html` faylları `Referer` ilə açılır, amma «İcra haqqında» üçün istifadə olunmadı | **Yoxlanmayıb** (rəsmi konsolidasiya alınmadı) |
| azstand.gov.az PDF (<https://azstand.gov.az/uploads/documents/468838918651c37b00ecdc.pdf>, 11.08.2023 tarixli nüsxə), mətn `pdftotext` ilə çıxarıldı | m.63.1, 64, 65.1–65.4, 67, 68.1.1–68.1.7, 74, 75.1–75.8 — docs-dakı sitatlarla **uyğundur**. «işəgötürən» sözü və xitamda köçürmə müddəti/bildirişi nüsxədə **yoxdur** | Oxundu; qeyri-rəsmi nüsxə |
| meclis.gov.az dəyişdirici qanunlar: 172-VIIQD (11.04.2025), 189-VIIQD (01.05.2025), 196-VIIQD (16.05.2025), 249-VIIQD (14.07.2025), 389-VIIQD (21.04.2026), 429-VIIQD (30.06.2026), 469-VIIQD (14.07.2026) — PDF-lər yüklənib | Heç biri m.64, 65, 67, 68.1.4–68.1.5, 74, 75-i dəyişmir: 172 — m.68.1.3 (əlillik sözləri); 189 — m.8.2, 45.2, 80.1, 81.1 və s.; 196 — m.4.1, 5, 8.1, 27, 32, 45, 72, 83, 84-1; 249 — «xüsusi icra məmurları» (m.2.3–2.5, 6.1.6-1, 8.1, 13.3, 20.7, 23, 42.7, 54.5, 70.4, 81-1.5, 81-2, 82.3, 84.4 və s.); 389 — m.17.1.4, 27, 84-1; 429 — m.89; 469 — m.27.4 | Oxundu; **bütün** dəyişdirici zəncir (2025 qanunvericilik toplusu № 4–№ 8) yoxlanmayıb |
| DVX post 4909 (10.07.2026), yemək xərcləri | H-04-də göstərilib; səhifə iki dəfə oxundu | Oxundu (DVX izahı, qanun mətni deyil) |
| DVX FAQ səhifəsi (`suallar-ve-cavablar?page=60`) | 18.10.2022: 102.2.7 (400) + «əlavə olaraq» 102.1.6 (200) — H-02a-da | Qismən oxundu (fetch aləti xülasəsi) |
| DVX: 102.1.6 + 102.5 (50) toplanması | Axtarış nəticəsi «200+100+50 = 350» yazdı, amma mənbə səhifəsi açılmadı | **Yoxlanmayıb** |
| Hərbçilərdə işsizlik sığortası (DSMF/Məşğulluq Agentliyi izahı) | `sosial.gov.az/en/state-compulsory-personal-insurance/military-personnel` — hərbçilərin icbari **şəxsi** sığortasıdır, işsizlik deyil; izah tapılmadı | **Yoxlanmayıb** |
| family.gov.az, NK 56 yenidən oxunması, VM 102.1.8, ƏM m.125/131/132 | Bu sessiyada yoxlanmadı | **Yoxlanmayıb** |

Qeyd: fetch aləti PDF və səhifələri xülasə etdiyi üçün tam sitatlar yalnız yerli `pdftotext` ilə çıxarılan PDF-lərdən (azstand, meclis.gov.az) götürülüb.

---

## 7. Qısa tövsiyə siyahısı

1. **Əvvəl H-06 və H-04-ü bağlayın.** Sektor statusu bütün işçiləri, yemək kartı bütün imtiyaz alanları əhatə edir; DVX-in 10.07.2026 izahı (H-04) hazırkı default ilə ziddir — mühasib qısa müddətdə qərar verməlidir.
2. **Sektoru açıq seçin** (profildən götürülməsin) və səbəbi yazın; subpodratçı olan şirkət üçün 50% hədd yoxlaması ildə bir dəfə əl ilə aparılsın.
3. **H-01 və E-06/E-07** (hərbi rejim) üç sual birlikdə mühasibə verilsin: işsizlik 0%, pul təminatı siyahısı, tibbi baza — hamısı konfiqurasiya ilə düzəlir, kod dəyişmir.
4. **H-02a və H-02b** üçün DVX-ə yazılı sorğu göndərin (kiçik məbləğ, çox işçi); 2022 cavabı 102.2.7 + 102.1.6-nı dəstəkləyir, 102.5 üçün cavab yoxdur.
5. **H-03:** «İcra haqqında» Qanunun rəsmi konsolidasiyasını (e-qanun.az, tarixi ilə) hüquqşünas bu reyestrə əlavə etsin; **m.68.1.5 (işdənçıxarma müavinəti)** boşluğu və **m.64 bazası** (H-03-1) birinci yoxlanılsın.
6. **H-07:** hüquqşünas qərar verənədək 20% həddi saxlanılsın; «Bəli» olarsa razılıq qeydi (E-12 ilə birlikdə) bir iş kimi planlaşdırılsın.
7. **H-08:** qaytarma düyməsi mühasib/Vergi orqanı cavabı gələnədək istifadə edilməsin; dəqiqləşdirilmiş bəyannamə ayrıca hazırlansın.
8. **Məzuniyyət bəndləri (H-05, H-09, H-10, E-03, E-04, E-17–E-24):** hüquqşünasla bir yığıncaqda həll edin; yalnız **E-03** (332-VIIQD qüvvəyə minmə tarixi) cəmlənmə qaydasını (staj + şərait) iş illəri üzrə dəyişə bilər.
9. **H-11** konfiqurasiya məsələsidir — əsasnamə maddəsini göstərib `early_close_policy`-ni yazılı qərarla seçin.
10. Hər cavab bu faylda tarix və təsdiqləyənlə qeyd olunsun; «Xeyr» cavabında qüvvəyə minmə tarixi olmadan dəyişiklik edilməsin (kilidli aylar üçün H-08 hesabatı).
