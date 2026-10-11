# Vacation Admin Guide

Bu sənəd `Vacation` modulunun admin və process owner istifadəsi üçündür.

## 1. Admin nəyi idarə edir

Vacation modulunda admin üçün əsas nəzarət sahələri:

- illik vacation hüququ və balansı
- order ilə əlaqə
- DOCX template düzgünlüyü
- struktur görünürlüğü
- staj pillələri və məzuniyyət normaları (`Məzuniyyət normaları` ekranı)
- düz 15 il düzəlişi
- vəzifələrin VTİSK kateqoriyası

## 2. İllik balans məntiqi

Vacation məlumatı təkcə cari məzuniyyəti yox, illik vacation limit və qalıq hesabını da daşıyır.

Admin üçün əsas yoxlama:

- yearly vacation list düzgün yaradılıbmı
- reset siyasəti düzgündürmü
- rank/category məntiqi doğrudurmu

## 3. Çap sənədi

Vacation kağızı DOCX template üzərindən yaradılır. Admin üçün:

- template mövcuddurmu
- chief name/rank settings doludurmu
- output sənədində placeholder problemi yoxdurmu

## 4. Struktur görünürlüğü

Vacation siyahısı accessible structure scope ilə işləyir. Bu səbəbdən admin permission və structure access matrisini də düzgün saxlamalıdır.

## 5. Staj pillələri (`Məzuniyyət normaları`)

Staja görə əlavə məzuniyyət qaydaları Admin bölməsində `Məzuniyyət normaları` ekranında, `Staja görə` qrupundadır. Ekran yalnız admin icazəsi (`access-admin`) olanlara açılır.

### Qanuni cədvəl və nə dəyişdirilə bilər
Qanuni üç pillə (ƏM m.116.1) sistemdə hazır gəlir: `Staj 5 – 10 il` (2 gün), `Staj 10 – 15 il` (4 gün), `Staj 15 il və daha çox` (6 gün). Qanun minimumdur, təşkilat daha çox verə bilər (m.7.3), az verə bilməz (m.7.3, m.24.2). Admin bunları dəyişə bilər:

- gün sayını artırmaq
- aşağı həddi azaltmaq
- aşağı həddin daxil olmasını seçmək: `Aşağı hədd daxildir («X il və daha çox»); işarəsiz — «X ildən çox»`
- öz əlavə pilləsini yaratmaq (`Norma əlavə et`)

Sətirdəki nişanlar: `Qanuni`, `Qurumun dəyəri (qanundan əlverişli)`, `Gələcəkdə qüvvəyə minir`, `Tarixçə`. Qanuni pillə silinmir və söndürülmür.

### Pillə dəyişdirmək
1. `Staja görə` qrupunda sətrin `Redaktə et` ikonunu və ya `Norma əlavə et` düyməsini basın.
2. Sahələri doldurun: `Staj, -dan (il)`, `Staj, -dək (il, boş — həddsiz)`, `Gün sayı`, `Hüquqi əsas`, `Qeyd`.
3. `Qüvvəyə minir (bu tarixdən başlayan iş illərinə)` sahəsində tarix seçin. Bu gündən tez tarix qəbul olunmur.
4. `Yadda saxla` basın.

#### Sonra nə olur?
- qüvvədə olan sətir yerində dəyişmir: o, seçilən tarixdən bir gün əvvəl bağlanır və yeni versiya həmin tarixdən başlayır, köhnə versiya `Tarixçə` nişanı ilə qalır
- staj əlavəsi iş ilinin **başlanğıcında** qüvvədə olan pillələrlə hesablandığı üçün artıq başlamış (bitmiş və davam edən) iş illərinin hüququ və istifadə olunmuş günlər dəyişmir
- hələ heç bir iş ilinə tətbiq olunmamış (gələcək tarixli) sətir yerində dəyişir
- hər dəyişiklik audit jurnalına `Məzuniyyət normaları` adı ilə yazılır

### Qanuni minimum yoxlaması
Hər dəyişiklikdən sonra sistem 0–81 il arasında hər staj nöqtəsində (düz N il və N ildən bir az çox) qüvvədə olan pillələrin qanuni cədvəldən az gün vermədiyini yoxlayır. Pozulubsa, yadda saxlanmır.

Rəqəmli nümunə:

| Dəyişiklik | Nəticə |
|---|---|
| `Staj 10 – 15 il` pilləsi 4 gündən 5 günə, qüvvəyə minmə 01.01.2027 | qəbul olunur; 01.06.2026-da başlayan iş ili 4 gün, 01.06.2027-də başlayan (13 il staj) iş ili 5 gün alır |
| `Staj 15 il və daha çox` pilləsi 8 gün | qəbul olunur (qanundan əlverişlidir) |
| düz 15 il üçün pilləni 5 günə salmaq | rədd: `Staj düz 15 il olanda pillələr 5 gün verir, qanun isə ən azı 6 gün tələb edir (ƏM m.116.1)…` |
| keçmiş tarixlə qüvvəyə minmə | rədd: `Staj pilləsinin dəyişikliyi keçmiş tarixdən qüvvəyə minə bilməz…` |

Digər xəta mesajları: `Gün sayı qanuni minimumdan (:days gün) az ola bilməz.`, `Stajın yuxarı həddi aşağı həddən böyük olmalıdır.`, `Bu sətir artıq bağlanıb (tarixçə)…`, `Staj pilləsi söndürülmür…`.

### Qanuni dəyərə qaytarmaq
Qanundan fərqli qanuni pillədə `Qanuni dəyərə qaytar` düyməsi görünür. Basanda pillə **bu gündən** qanundakı dəyərə qaytarılır (yeni versiya yaranır, köhnə tarixçədə qalır). Mesaj: `Pillə bu gündən qanuni dəyərə qaytarıldı.`

## 6. Düz 15 il düzəlişi

### Nə üçündür
Standart əvvəl düz 15 ili 4 gün sayırdı. ƏM m.116.1 «on ildən on beş ilədək» ifadəsini X daxil, Y xaric işlədir (m.119.1, m.46, m.77 ilə eyni üslub), ona görə düz 15 il «15 ildən çox» pilləsinə, yəni 6 gün pilləsinə düşür. Standart `15 il və daha çox` → 6 gün oldu. Lakin yeni norma versiyası yalnız onun qüvvəyə mindiyi gündən **başlayan** iş illərinə tətbiq olunur. Əvvəl başlamış (davam edən və bitmiş) iş illərində stajı iş ilinin başlanğıcında düz 15 il olan işçilərə 4 gün hesablanıb. Düzəliş bu iş illərinə +2 gün yazır.

Hüquqi məntiq: bu, geriyə qüvvə deyil, səhvin düzəldilməsidir. İşçinin 6 gün hüququ qanundan yaranır; sistem parametri onu ləğv edə bilməz (m.7.1, m.7.3). İstifadə olunmamış məzuniyyət yanmır və sonrakı illərə keçir (m.134–135), xitamda isə bütün iş illəri üçün kompensasiya olunur (m.144.2). Ona görə bitmiş iş illəri də düzəldilir.

### Ekranda necə işlədilir
1. `Məzuniyyət normaları` → `Staja görə` qrupunu açın. Səhifənin altında `Düz 15 il düzəlişi` bölməsi görünür.
2. `Təsirlənənləri tap` basın. Önizləmə iki cədvəldir:
   - `İşləyən işçilər — balansa düzəliş yazılır`: `İşçi`, `İş ili`, `Staj əlavəsi (hesablanıb)`, `Düzəliş`, `Vəziyyət` (`Gözləyir` və ya `Yazılıb`)
   - `Xitam olunmuş işçilər — kompensasiya fərqi (mühasibatlıq üçün)`: `İşçi`, `İş ili`, `Xitam tarixi`, `Fərq (gün)`, `Qeyd`
3. Önizləməni yoxlayın və `Düzəlişi yaz` basın. Təsdiq pəncərəsi çıxır: `Düzəliş yazılsın?`
4. Təsdiqləyin. Mesaj: `Düzəliş yazıldı: N iş ili.` Yazılacaq heç nə yoxdursa: `Yazılacaq düzəliş yoxdur.`

#### Sonra nə olur?
- hər təsirlənən iş ilinə `düzəliş` (adjustment) hərəkəti yazılır, məbləği 6 − hesablanmış gündür (adətən +2)
- hərəkət sabit açar ilə (`seniority15-fix:{tabel}:{iş ili}`) bir dəfə yazılır; təkrar işə salmaq ikinci dəfə yazmır
- yazıdan əvvəl iş ilinin sətri kilidlənir, paralel iki yazının qarşısı alınır
- hər yazı audit jurnalına düşür (jurnal adı `vacation`, hadisə `seniority_fifteen_fix`: staj əlavəsi əvvəl, günlər, iş ili, hüquqi əsas)
- siyahı və yazı yalnız sizin **struktur görünürlüyünüzdəki** işçilərə şamil olunur; görmədiyiniz işçiyə yazılmır
- işəgötürənin norma versiyası və dondurulmuş iş ili dəyişmir, fərq ayrıca izlənə bilən hərəkətdir

### Kimlər düzəlişə düşür
- mülki (ümumi iş ili) işçi, hesablanmış iş ili (köçürülmüş «köhnə/açılış» qalığı deyil)
- ƏM m.118–121 üzrə «əlavə məzuniyyət verilmir» istisnası olmayan işçi
- staj iş ilinin başlanğıcında təqvimlə düz 15 il olan
- staj əlavəsi qanuni 6 gündən az olan

### Xitam olunmuş işçilər
Onlara məzuniyyət vermək olmaz, yalnız pul (m.144.2). Sistem fərqi balansa **yazmır**, ayrıca siyahıda göstərir; mühasibatlıq kompensasiya əmri ilə rəsmiləşdirir. Bir günün məbləği orta aylıq əmək haqqı ÷ 30,4 (m.140.3).

- 18.02.2026-dan (Konstitusiya Məhkəməsi qərarı, KMQ16) sonrakı xitam: əlavə məzuniyyətin kompensasiyası məcburidir; `Qeyd`: `Kompensasiya fərqi ödənilməlidir (m.144.2, KMQ16).`
- 18.02.2026-dan **əvvəlki** xitam: o vaxt m.144.3 əlavə məzuniyyətin kompensasiyasını istisna edirdi; `Qeyd`: `Xitam 18.02.2026-dan (KMQ16) əvvəldir … hüquqşünas qərar verməlidir.` Bu halda qərarı hüquqşünas verməlidir, sistem özü qərar vermir. Könüllü ödəniş qadağan deyil.

### Rəqəmli nümunələr

| İşə qəbul | İş ili | Hesablanıb | Düzəliş | Nəticə |
|---|---|---|---|---|
| 01.06.2011 | 16-cı, 01.06.2026–31.05.2027 (davam edir) | 21 + 4 | +2 | 27 |
| 01.06.2010 | 16-cı, 01.06.2025–31.05.2026 (bitib) | 21 + 4 | +2, qalığa yazılır və keçir | 27 |
| 01.06.2010 | 17-ci, 01.06.2026-dan (16 il) | 21 + 6 | yoxdur | 27 |
| 01.12.2011 | 16-cı, 01.12.2026-dan (yeni qayda) | 21 + 6 | yoxdur | 27 |
| 01.03.2011, xitam 30.09.2026 | 16-cı, 01.03.2026-dan | 21 + 4 | balansa yox | 2 gün kompensasiya fərqi |

Pul nümunəsi (şərti): orta aylıq 1520 AZN olsa, 1520 ÷ 30,4 = 50 AZN; 2 gün fərq = 100 AZN.

### Nə vaxt hansını seçməli
- **Düzəliş (`Düzəlişi yaz`)** — işləyən işçinin davam edən və ya bitmiş iş ili üçün.
- **Kompensasiya əmri (mühasibatlıq)** — xitam olunmuş işçi üçün.
- **Açılış qalığına əl ilə əlavə** — uçot başlanğıcından əvvəlki iş illəri üçün. Köhnə/açılış qalığının tərkibi olmadığından ekran onları yoxlamır.
- **Hüquqşünas** — 18.02.2026-dan əvvəl xitam olunanlar.

### Tipik xətalar
- Önizləmə boşdur, amma düzəliş lazım olduğuna əminsiniz: işçinin ümumi stajı (əvvəlki işəgötürənlər) işçi kartında natamam ola bilər, yaxud işçi sizin struktur görünürlüyünüzdə deyil.
- `Düzəlişi yaz` düyməsi görünmür: bütün sətirlər artıq `Yazılıb`dır.
- Düzəlişi yazdıqdan sonra tərkibdə `Staj +4` qalır: cəm düzgündür, düzəliş ayrıca hərəkət kimi toplanır.
- Düzəliş səhv yazılıbsa: `source` açarı ilə (`seniority15-fix:` prefiksi) tapıla bilər; ekranda və ya komandada geri alma düyməsi yoxdur.

## 7. NK 95 b.10: «÷ 30,4» hesabı

Əmək şəraitinə görə əlavə məzuniyyətdə tam ayın sayı: şəraitdə sayılan **təqvim günləri ÷ 30,4**, qalıq 15,2 gündən azdırsa atılır, çoxdursa tam ay (ildə ≤ 12). Qərarın mətnində «iş günləri» yazılıb, lakin bölən həmin bənddə «təqvim günlərinin orta aylıq sayı» adlanır; qaydaların öz misalları (aprel–iyun = 3 ay) və ƏM m.140.3 (30,4 = ayın təqvim günlərinin orta illik miqdarı) yalnız təqvim variantına uyğundur. Sistemin hesabı qanuna uyğun sayılıb və dəyişdirilməyib. İş günü variantı tam il üçün cəmi 8–9 ay verərdi.

Admin üçün nəticə: istifadəçi «16 gün 1 ay verir, 15 gün vermir» deyirsə, bu, xəta deyil. Hərfi «iş günü» oxunuşu tətbiq edilsə, işçinin hüququ azalardı; təqvim variantı heç vaxt ondan az vermir. Rəsmi izah (Nazirlik, Ali Məhkəmə) tapılmayıb, ona görə Əmək Müfəttişliyi fərqli oxunuş tətbiq edərsə, hüquqşünas qərar verməlidir.

### NK 95 fasilə halları (b.6–11)
Şərait ilində fasilə hallarında sistemin davranışı `Məzuniyyət İstifadəçi Bələdçisi`ndəki `Şərait ilində fasilə olanda nə baş verir` cədvəlindədir. Admin üçün əsas məqamlar:

- **«Şəraitdən kənar» sətri** (`Bu dövrdə işçi iş gününün 90%-dən az həmin şəraitdə çalışıb`): gündəlik 90% faktını sistem bilmir, HR dövr kimi qeyd etməlidir (b.12).
- **Dövrlü işçi sətri**: peşəsi siyahıda olmayan işçiyə müəyyən dövrdə siyahı işi tapşırılıbsa (b.13).
- **Köçürmə tarixi**: şəraitə köçürmə əmrindəki `Köçürmə tarixi` sahəsindən sayılır; boşdursa əmrin tarixi.
- **Açıq risklər (hüquqşünas təsdiqi lazımdır):** 6 ay növbəti şərait ilində tamamlananda əvvəlki qısa ilin aylarının verilib-verilməməsi; hamiləlik və doğuş məzuniyyətinin staja daxil edilməsi.

## 8. VTİSK kateqoriyası

ƏM m.114.3 «b» (332-VIIQD, 2026) rəhbər və mütəxəssis kateqoriyasına aid işçilərə 30 gün əsas məzuniyyət verir. Kateqoriya vəzifədən götürülür.

1. Admin → `Vəzifə` ekranını açın.
2. Cədvəldə `VTİSK kateqoriyası` sütunu var: `Rəhbər`, `Mütəxəssis`, `Texniki icraçı`, `Fəhlə` və ya `Təyin olunmayıb` (sarı nişan).
3. Başlıqdakı `Yalnız VTİSK kateqoriyası təyin olunmamış` süzgəcini seçin: yanındakı say kateqoriyasız vəzifələrin sayını göstərir.
4. Hər vəzifəni açın və `VTİSK kateqoriyası` sahəsini seçin.

Diqqət: kateqoriyası seçilməyən vəzifədə VTİSK üzrə 30 günlük sətir işləmir; başqa norma şamil olunmursa, işçi 21 gün alır. Sürücü fəhlədir, kargüzar texniki icraçıdır; 30 gün yalnız səhv seçimlə verilə bilər. Yeni vəzifə əlavə edəndə kateqoriyanı yoxlayın.

## 9. Tipik ssenarilər

### Yeni il üçün vacation balansı

1. yearly list import işlədilir
2. nümunə personal üzərində nəticə yoxlanılır
3. qalıq və total days qarşılaşdırılır

### Print sənədi səhv çıxır

1. source template yoxlanılır
2. setting dəyərləri yoxlanılır
3. modeldəki tarix və order dəyərləri yoxlanılır

### Düz 15 il stajlıların balansı 4 gün göstərir

1. `Məzuniyyət normaları` → `Staja görə` ekranında `Staj 15 il və daha çox` sətrinin 6 gün olduğunu yoxlayın
2. `Düz 15 il düzəlişi` → `Təsirlənənləri tap`
3. Önizləməni nümunə işçi üzərində yoxlayın, `Düzəlişi yaz` basın
4. Xitam olunmuşlar siyahısını mühasibatlığa və hüquqşünasa ötürün

### Təşkilat staj pilləsini artırmaq istəyir

1. `Staja görə` qrupunda pilləni açın, gün sayını artırın
2. `Qüvvəyə minir` sahəsinə gələcək tarix yazın
3. Yadda saxlayın, `Gələcəkdə qüvvəyə minir` nişanını yoxlayın
4. Keçmiş iş illərinin dəyişmədiyini nümunə işçi üzərində qarşılaşdırın

## 10. Tez-tez verilən suallar

**Düz 15 il üçün niyə 6 gün, 4 deyil?**
Məcəllə «X-dan Y-dək» ifadəsini ardıcıl olaraq X daxil, Y xaric işlədir. Düz 15 il «10–15» pilləsində deyil, 15 ildən başlayan pillədədir. Qanundan çox vermək qanunidir, az vermək minimumun pozulmasıdır.

**Qurum pilləsini qanundan aşağı sala bilərəmmi?**
Xeyr. Sistem rədd edir (m.7.3, m.24.2).

**Dəyişiklik köhnə iş illərini niyə dəyişmir?**
Staj əlavəsi iş ilinin başlanğıcında qüvvədə olan pillə ilə hesablanır. Qurumun müqavilə ilə verdiyi şərtlər qüvvəyə minmə tarixindən tətbiq olunur. Qanuni minimumun düzgün oxunuşu isə tarixə bağlı deyil, ona görə düz 15 il halı ayrıca düzəliş ilə həll olunur.

**Düzəlişi iki dəfə işə salsam, iki dəfə yazılacaqmı?**
Xeyr. Hər iş ilinin hərəkəti sabit açarla bir dəfə yazılır; təkrar işə salma `Yazılıb` olanları keçir.

**Düzəliş xitam olunmuş işçinin balansına yazılırmı?**
Xeyr. Siyahıda `Fərq (gün)` kimi göstərilir, mühasibatlıq kompensasiya əmri ilə rəsmiləşdirir.

**`Məzuniyyət normaları` ekranını kim görür?**
`access-admin` icazəsi olan istifadəçi. `Düz 15 il düzəlişi` də eyni icazə ilə işləyir.

## Yadda saxlayın
- Qanuni minimum: düz 5 il 2, düz 10 il 4, düz 15 il 6 gün.
- Pillə dəyişikliyi yalnız bu gündən və sonrakı tarixdən, yalnız başlayacaq iş illərinə tətbiq olunur.
- `Düz 15 il düzəlişi` əvvəl önizləmə, sonra təsdiq: bitmiş iş illəri də daxildir.
- Xitam olunmuş işçilərin fərqi balansa yazılmır; kompensasiya əmri lazımdır.
- 18.02.2026-dan əvvəl xitam olunanlar üzrə qərar hüquqşünasındır.
- Vəzifənin `VTİSK kateqoriyası` boşdursa, 30 günlük qayda işləmir.

## 11. Bu sənədlə birlikdə baxılmalı sənədlər

- `[Vacation User Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/vacation-user-guide.md)`
- `[Vacation Approval Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/vacation-approval-guide.md)`
- `[Vacation Ops / Commands Guide](/Users/togruljalalli/Desktop/projects/HRM/docs/scenario/vacation-ops-commands-guide.md)`
