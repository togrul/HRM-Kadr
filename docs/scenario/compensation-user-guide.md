# Kompensasiya istifadəçi bələdçisi

## Bu modul nə üçündür?
Bu modul maaşla bağlı əsas məlumatları bir yerdə saxlamaq üçündür.

Sadə dildə burada bunları idarə edirsiniz:
- maaş şkalaları və onların pillələri
- əlavə və tutulmaların kataloqu (məsələn, mükafat, ərzaq pulu, kredit tutulması)
- hər əməkdaşın cari maaşı
- əməkdaşın bank hesabları
- maaş dəyişikliklərinin tarixçəsi
- vergi və sığorta dərəcələri
- şirkətin vergi sektoru və yeni maaş təyinatlarının standart rejimi
- işçinin gəlir vergisi güzəşti kartları (şəhid ailəsi, əlillik və s.)

`Əmək haqqı` modulu hesablamanı məhz buradakı məlumatlara əsasən aparır. Ona görə burada düzgün məlumat olmalıdır.

## Harada açılır?
Sol menyudan `Kompensasiya` bölməsini açın.

Səhifənin solunda panel var. Orada bölmələr sıralanır:
- `Şkalalar`
- `Kataloq`
- `İşçi maaşları`
- `Bank`
- `Tarixçə`
- `Vergi/sığorta dərəcələri`
- `Rejim və sektor`
- `Vergi güzəştləri`

Bəzi bölmələrin yanında say görünür (məsələn, neçə şkala, neçə aktiv maaş var). `Bank`, `Tarixçə` və `Vergi güzəştləri` bir əməkdaşa aid olduğu üçün orada ümumi say göstərilmir.

Panelin aşağısında qısa xülasə var: `Şkalalar`, `Pillələr`, `Aktiv komponentlər`, `Aktiv maaşlar`.

Panelin ən altındakı `İstifadə təlimatı` keçidi bu bələdçini açır.

## Bu modul kimlər üçündür?
- **Baxış icazəsi olanlar** — şkalaları, kataloqu, maaşları və tarixçəni görə bilər, amma dəyişə bilməz.
- **İdarəetmə icazəsi olanlar** — şkala, pillə, komponent, maaş, bank hesabı və dərəcə əlavə edir, redaktə edir, silir.
- **Məbləğləri görmə icazəsi olanlar** — maaş rəqəmlərini açıq görür. Bu icazə yoxdursa, rəqəmlərin yerində `•••` görünür.

Yeni bölmələr eyni iki icazə ilə işləyir:
- `Rejim və sektor` — baxış icazəsi olan sektoru və fərqli rejimli işçilərin siyahısını görür; sektoru dəyişmək və rejimi düzəltmək üçün idarəetmə icazəsi lazımdır.
- `Vergi güzəştləri` — baxış icazəsi olan kartları və sənədin surətini açır; kart əlavə etmək, bitirmək və silmək üçün idarəetmə icazəsi lazımdır.
- Hər iki bölmədə siz yalnız öz struktur görünürlüyünüzdəki işçiləri görürsünüz.

## Ekranın quruluşu

### Başlıq
Yuxarıda səhifə adı (`Kompensasiya`) və üç göstərici var: `Şkalalar`, `Pillələr`, `Aktiv maaşlar`.

Başlıqdakı düymələr:
- `Kataloq` — birbaşa komponent kataloquna keçir.
- Qara əsas düymə — açıq bölməyə görə dəyişir:
  - `Şkalalar` bölməsində: `Şkala əlavə et`
  - `Kataloq` bölməsində: `Komponent əlavə et`
  - `Bank` bölməsində (əməkdaş seçiləndən sonra): `Bank hesabı əlavə et`
  - `Vergi güzəştləri` bölməsində (əməkdaş seçiləndən sonra): `Güzəşt kartı əlavə et`
  - `Vergi/sığorta dərəcələri` bölməsində: `Dərəcə əlavə et`

Qara düymə yalnız idarəetmə icazəsi olanlara görünür. `İşçi maaşları`, `Tarixçə` və `Rejim və sektor` bölmələrində qara düymə olmur — orada forma səhifənin özündədir.

Kiçik ekranda bölmələr başlığın altında tab kimi görünür.

### Əməkdaş seçimi
`İşçi maaşları`, `Bank`, `Tarixçə` və `Vergi güzəştləri` bölmələri bir əməkdaş üzrə işləyir. Bu bölmələrin yuxarısında `Əməkdaş` sahəsi var:
1. Axtarış xanasına ad və ya tabel nömrəsi yazın (`Ad və ya tabel nömrəsi ilə axtar`).
2. Siyahıdan lazım olan əməkdaşı seçin.
3. Başqa əməkdaşa keçmək üçün `Təmizlə` düyməsini basın.

### Sətir düymələri
Cədvəllərdə hər sətrin sağında iki kiçik ikon var: qələm (`Redaktə et`) və zibil qutusu (`Sil`). Bunlar yalnız idarəetmə icazəsi olanlara görünür. Silmədən əvvəl sistem təsdiq soruşur: `Bu qeydi silmək istədiyinizə əminsiniz?`

Əlavə və redaktə formaları sağdan açılan paneldə açılır. Panelin altında `Yadda saxla` və `Ləğv et` düymələri var.

## Bölmələr və əsas əməliyyatlar

### Şkalalar
Solda maaş şkalalarının cədvəli var: `Şkala`, `Pillə`, `Minimum`, `Orta nöqtə`, `Maksimum`, `Rejim`. Minimum, orta nöqtə və maksimum şkalanın pillələrindən avtomatik hesablanır. Yuxarıdakı axtarış xanası ilə şkala tapa bilərsiniz.

Şkalanın adına basanda sağda onun `Pillələr` cədvəli açılır: `Pillə`, `Məbləğ`, `Vəzifə`. Şkala seçilməyibsə, `Pillələri görmək üçün soldan şkala seçin.` yazısı görünür.

**Şkala əlavə etmək:**
1. Başlıqda `Şkala əlavə et` düyməsini basın.
2. `Ad`, `Rejim`, `Valyuta`, `Qüvvəyə minmə` sahələrini doldurun (bunlar vacibdir).
3. Lazım olsa `Bitmə` tarixi və `Təsvir` yazın.
4. `Yadda saxla` basın.

**Pillə əlavə etmək:**
1. Soldan şkalanı seçin.
2. Sağda `Pillə əlavə et` düyməsini basın.
3. `Kod`, `Ad`, `Baza məbləğ` yazın; lazım olsa `Rütbə kateqoriyası` və `Vəzifə` seçin.
4. `Yadda saxla` basın.

### Kataloq
Bu, bütün əlavə və tutulmaların siyahısıdır. Cədvəldə `Komponent`, `Növ` (`Əlavə` və ya `Tutulma`), `Hesablama` və `Xüsusiyyətlər` görünür. Xüsusiyyətlər kiçik nişanlarla göstərilir: `Vergiyə cəlb`, `Sosial bazaya təsir`, `Qanuni`.

**Komponent əlavə etmək:**
1. `Komponent əlavə et` basın.
2. `Kod`, `Ad`, `Növ` və `Hesablama növü` seçin (`Sabit`, `Faiz`, `Formula`, `Gündəlik`, `Dərəcə`).
3. Lazım olan qutucuqları işarələyin: `Vergiyə cəlb`, `Sosial bazaya təsir`, `Qanuni`, `Aktiv`.
4. İstəsəniz `Mühasibat kodu` və `Sıra` yazın.
5. `Yadda saxla` basın.

Kod təkrarlana bilməz — eyni kodla ikinci komponent yaratmaq olmur.

**Hərbi rejimdə nişanlar.** Qazanc komponentlərində hərbi qulluqçu üçün əlavə nişanlar görünə bilər:
- `Hərbçidə vergiyə cəlb olunur` — komponent hərbçinin gəlir vergisi bazasına düşür (vəzifə maaşı və rütbə maaşı);
- `Hərbçidə vergidən azad` — komponent hərbçinin vergi bazasına düşmür;
- `Hərbçidə DSMF bazasına daxil deyil` — komponent hərbçinin DSMF bazasına düşmür (məsələn, birdəfəlik mükafat).

Bu nişanlar `Vergiyə cəlb` və `Sosial bazaya təsir` qutucuqlarının yerini tutmur: onlar ümumi qaydadır, hərbi rejimdə isə üstəgəl `Vergi/sığorta dərəcələri` bölməsindəki siyahı tətbiq olunur. Nişanın mənbəyi həmin siyahıdır, ona görə nişanı dəyişmək üçün siyahını düzəltmək lazımdır (bax: `Vergi/sığorta dərəcələri`).

### İşçi maaşları
Əməkdaşı seçəndən sonra yuxarıda `Cari maaş` kartı görünür: məbləğ, valyuta və qüvvəyə minmə tarixi.

**Yeni maaş təyin etmək:**
1. Əməkdaşı seçin.
2. `Yeni təyinat` formasında `Rejim`, `Baza məbləğ` və `Qüvvəyə minmə` tarixini doldurun.
3. İstəsəniz `Əmr nömrəsi` və `Qeyd` yazın.
4. Əlavə və ya tutulma lazımdırsa, `Əlavə / tutulma sətirləri` hissəsində `Sətir əlavə et` basın, `Komponent` seçin və `Məbləğ` və ya `Faiz` yazın. Artıq sətri `Sil` ilə çıxarın.
5. `Maaşı təyin et` basın.

Yeni təyinat yadda saxlananda əvvəlki aktiv maaş avtomatik bağlanır və tarixçəyə düşür. Yəni köhnə maaşı ayrıca silmək lazım deyil.

**Rejim sahəsi.** Yeni təyinat formasında `Rejim` avtomatik şirkətin standart rejimi ilə doldurulur və altında `Şirkət standartı: ...` yazılır. İşə qəbul qaralaması və maaşı dəyişən əmr də rejimi eyni standartdan götürür (əmrdə mövcud maaşın rejimi qorunur). Standartı `Rejim və sektor` bölməsində dəyişirsiniz. Başqa rejim seçmək olar, amma bu, vergi və sığorta dərəcələrini dəyişir — yalnız işçi həqiqətən başqa rejimə aiddirsə seçin.

### Bank
Əməkdaşı seçin. `Bank hesabları` siyahısında IBAN, bank adı və hesab nömrəsi görünür. Əsas hesab `Əsas` nişanı ilə işarələnir.

**Bank hesabı əlavə etmək:**
1. Əməkdaşı seçin.
2. Başlıqda `Bank hesabı əlavə et` basın.
3. `IBAN` (vacib), `Bank adı`, `Hesab nömrəsi` yazın.
4. Əsas hesabdırsa, `Əsas` qutucuğunu işarələyin.
5. `Yadda saxla` basın.

Maaş bank faylı əsas hesab üzrə hazırlanır, ona görə hər əməkdaşın bir əsas hesabı olmalıdır.

### Tarixçə
Əməkdaşı seçin. Burada onun bütün maaş təyinatları görünür: məbləğ, rejim, başlama və bitmə tarixi. Hələ bitməyən təyinatda bitmə yerinə `davam edir` yazılır.

Status nişanları:
- `Aktiv` (yaşıl) — hazırda qüvvədə olan maaş
- `Bitmiş` — əvəz olunmuş və ya bağlanmış maaş
- `Qaralama` — hələ qüvvəyə minməmiş qeyd

Bu bölmə yalnız baxış üçündür.

### Vergi/sığorta dərəcələri
Burada qanuni tutulmaların dərəcələri saxlanır: `Gəlir vergisi`, `DSMF`, `İşsizlik sığortası`, `İcbari tibbi sığorta`.

**Dərəcə əlavə etmək:**
1. `Dərəcə əlavə et` basın.
2. `Komponent`, `Ödəyən` (`İşçi` və ya `İşəgötürən`), `Baza` (`Vergi bazası` və ya `Sosial baza`) və `Qüvvəyə minmə` tarixini seçin.
3. `Rejim` seçməsəniz, dərəcə `Bütün rejimlər (default)` üçün keçərli olur.
4. `Pillələr` hissəsində ən azı bir pillə olmalıdır: `Həddə qədər` məbləği və `Faiz (%)`. Pilləli vergi üçün `Pillə əlavə et` ilə yeni sətir artırın. Son pillədə həddi boş qoysanız, o `(maksimum)` kimi göstərilir.
5. `Yadda saxla` basın.

### Gəlir vergisi azadolmaları
Eyni `Vergi/sığorta dərəcələri` bölməsində dərəcə kartlarının altında `Gəlir vergisi azadolmaları (VM m.102)` siyahısı var. Azadolma dərəcə pillələri tətbiq olunmazdan əvvəl vergi tutulan gəlirdən çıxılır. Hər kartda azad məbləğ, hüquqi əsas, `aylıq gəlir 2 500,00 AZN-dək` kimi gəlir həddi, `yalnız əsas iş yerində` nişanı, rejim və `01.01.2026 tarixindən` kimi qüvvəyə minmə tarixi görünür.

Sistemdə hazır olan azadolmalar:
- `VM 102.1.6` — əsas iş yerində aylıq gəlir 2500 AZN-dək olduqda 200 AZN. Bütün rejimlərə tətbiq olunur və işçi kartı tələb etmir.
- `VM 102.1-1`, `VM 102.2`, `VM 102.3`, `VM 102.4`, `VM 102.5` — 800, 400, 200, 100 və 50 AZN. Bunların üzərində `işçinin güzəşt kartı ilə (Kompensasiya → Vergi güzəştləri)` yazılır: məbləğ burada saxlanır, amma yalnız kartı olan işçiyə tətbiq olunur.
- `VM 102.1.14.15` — hərbi rejimdə vergiyə yalnız sadalanan komponentlər cəlb olunur: `Vergiyə yalnız bu komponentlər cəlb olunur: ...`.
- `SS m.15; ƏP m.1.0.5` — hərbi rejimdə DSMF bazasına yalnız sadalanan komponentlər daxildir: `DSMF bazasına yalnız bu komponentlər daxildir: ...`.

**Azadolma əlavə etmək (məsələn, məbləğ dəyişəndə):**
1. `Azadolma əlavə et` basın.
2. `Rejim`, `Hüquqi əsas`, `Kod`, `Azad məbləğ (AZN)` yazın. Gəlir həddi lazımdırsa `Yalnız aylıq gəlir bu məbləğədək olduqda (AZN)` doldurun; yalnız əsas iş yeri üçündürsə `yalnız əsas iş yerində` qutucuğunu işarələyin; `Qüvvəyə minmə` tarixini seçin.
3. `Yadda saxla` basın.

Eyni kodlu və eyni rejimli əvvəlki sətir yeni tarixdən bir gün əvvəl bağlanır, kilidlənmiş aylar köhnə qayda ilə qalır.

**Qeyd.** Bu forma yalnız «sabit məbləğli» azadolma yaradır. Kart tələb edən (`vm_102_1_1` … `vm_102_5`) və ya vergi tutulan komponent növlü qaydanın kodunu bu formla yazmaq, həmçinin həmin sətri silmək mümkün deyil: sistem xəta göstərir, çünki əks halda qayda hamıya tətbiq olunan azadolmaya çevrilərdi və ya hamıdan götürülərdi. Şəxsi güzəştlər `Vergi güzəştləri` bölməsində kartla idarə olunur.

### Rejim və sektor
Bu bölmə iki iş üçündür: şirkətin vergi sektorunu seçmək və rejimi şirkət standartından fərqli olan işçiləri düzəltmək.

**Şirkətin vergi sektoru.** Yuxarıdakı `Şirkətin vergi sektoru` kartında `Yeni təyinatların standart rejimi: ...` yazılır. Sektor hələ seçilməyibsə, yanında `quraşdırma profilindən götürülüb, sektor hələ seçilməyib` görünür: hərbi profildə hərbi, dövlət profilində dövlət, qalan hallarda özəl qeyri-neft-qaz sektoru götürülür.

`Sektor` seçimləri və onların rejimi:
- `Özəl, neft-qaz sahəsində fəaliyyəti olmayan` — özəl qeyri-neft-qaz sektoru rejimi;
- `Neft-qaz sahəsində fəaliyyəti olan` — dövlət və neft-qaz sektoru rejimi;
- `Dövlət sektoru` — dövlət və neft-qaz sektoru rejimi;
- `Hərbi qulluq` — hərbi rejim.

**Sektoru dəyişmək:**
1. `Sektor` siyahısından yeni sektoru seçin.
2. `Səbəb` sahəsinə səbəbi yazın.
3. `Sektoru saxla` basıb təsdiqləyin.

Sektor işəgötürənin xüsusiyyətidir, işçinin yox: şirkətin neft-qaz sahəsində fəaliyyəti və ya dövlət sektoruna aid olması Nazirlər Kabinetinin 18.02.2019 tarixli 56 nömrəli Qərarının meyarları ilə müəyyən olunur (meyarların qısa izahı kartın altında yazılıb). Dəyişiklik səbəblə birlikdə audit jurnalına yazılır.

Sektorun dəyişməsi mövcud işçilərin rejiminə toxunmur — yalnız yeni təyinatların, işə qəbul qaralamasının və maaşı dəyişən əmrin standart rejimini dəyişir.

**Rejimi standartdan fərqli olan işçilər.** `Rejimi şirkət standartından fərqli olan işçilər` cədvəlində `İşçi`, `Cari rejim`, `Təyinat tarixi` və `Ən tez düzəliş tarixi` görünür. Hamı uyğundursa, `Bütün işçilərin rejimi şirkət standartına uyğundur` yazılır.

**Rejimi düzəltmək:**
1. İşçiləri seçin (`Hamısını seç` hamısını işarələyir).
2. `Qüvvəyə minmə tarixi (ayın 1-i)` sahəsində ayın 1-ni seçin. Tarix `Ən tez düzəliş tarixi` sütunundakı tarixdən tez olmamalıdır.
3. `Səbəb` yazın.
4. `Seçilmişləri düzəlt (N)` basıb təsdiqləyin.

Hər işçi üçün yeni təyinat yaranır: baza məbləğ, valyuta, pillə və əlavə/tutulma sətirləri eyni qalır, yalnız rejim dəyişir; köhnə təyinat bir gün əvvəl bağlanır. Nəticə `Düzəldildi: N, keçirilmədi: M.` kimi göstərilir, keçirilməyən işçinin altında səbəb sarı sətirdə yazılır:
- «Tarix işçinin kilidlənmiş (ödənilmiş) ayına düşür — sonrakı ayın 1-ni seçin.»
- «Bu tarixdə aktiv maaş təyinatı yoxdur.»
- «Rejim artıq uyğundur.»
- «Cari təyinat bu tarixdə və ya sonra başlayır — daha gec tarix seçin.»
- «Bu tarixdən sonra başlayan başqa təyinat var — əvvəlcə onu yoxlayın.»

Ödənilmiş (kilidlənmiş) aylar köhnə rejimlə qalır və avtomatik yenidən hesablanmır. Həmin aylar üzrə vergi və sığorta fərqini `Əmək haqqı` modulunda `Vergi düzəliş hesabatı` göstərir.

### Vergi güzəştləri
Bu bölmə seçilmiş işçinin Vergi Məcəlləsinin m.102.1-1 və 102.2–102.5 üzrə güzəşt kartlarını saxlayır. Əməkdaşı seçin — kartlar siyahısı açılır. Hər kartda güzəşt adı və məbləği, dəqiq bənd, `Sənəd № ..., tarix, orqan`, `Sənədi aç` keçidi, tarixlər sətri (`Qanuni başlanğıc ... · maaşda ... tarixindən · son: ...`) və status görünür: `Qüvvədədir` və ya `Bitib`.

Kartların kateqoriyaları:
- `VM 102.1-1` — şəhid ailəsi (valideyn, dul arvad/ər, övlad): 800 AZN;
- `VM 102.2` — Qəhrəmanlar, I–II qrup müharibə əlilləri, həlak olmuş döyüşçülərin ailəsi, müharibə veteranları və s.: 400 AZN;
- `VM 102.3` — 61–100% əlilliyi olanlar, əlilliyi olan uşaq və ona baxan valideyn/qəyyum: 200 AZN;
- `VM 102.4` — həlak olmuş döyüşçülərin valideynləri, 20 Yanvar ailələri, məcburi köçkünlər və s.: 100 AZN;
- `VM 102.5` — himayəsində azı 3 nəfər olan ər və ya arvaddan biri: 50 AZN.

**Kart əlavə etmək:**
1. Əməkdaşı seçin.
2. Başlıqda `Güzəşt kartı əlavə et` basın.
3. `Güzəşt` siyahısından kateqoriyanı seçin.
4. Lazım olsa `Dəqiq bənd və izah` yazın (məsələn, «102.2.4 — I qrup müharibə əlili»).
5. `Sənədin nömrəsi` yazın (vacibdir); `Sənədin tarixi` və `Sənədi verən orqan` sahələrini doldurun.
6. `Statusun yarandığı tarix` və `Sənədlərin təqdim edildiyi tarix` yazın (ikisi də vacibdir; təqdim tarixi statusun tarixindən əvvəl ola bilməz). Status müddətlidirsə, `Etibarlılıq sonu` yazın.
7. İstəsəniz `Sənədin surətini` yükləyin — fayl özəl yaddaşda saxlanır, yalnız baxış icazəsi olan və işçinin strukturunu görən istifadəçi açır.
8. `Yadda saxla` basın.

**Güzəşti bitirmək.** Status itirildikdə və ya sənədin müddəti bitdikdə kartın yanındakı qələm ikonunu (`Güzəşti bitir`) basın, `Etibarlılıq sonu` tarixini və `Səbəb`i yazın. Tarix kilidlənmiş aya düşə bilməz.

**Silmək.** Silmə ikonu yalnız səhv daxil edilmiş kart üçündür. Kart kilidlənmiş (ödənilmiş) ayda tətbiq olunubsa silinmir — `Güzəşti bitir` istifadə edin.

Kartın tarixləri vergi qaydası ilə hesablanır (bax: aşağıdakı «Hüquqi əsas və hesablama nümunələri»). Kartın `qanuni başlanğıc` tarixi maaşda tətbiq tarixindən əvvəl olarsa, ekranda sarı xəbərdarlıq çıxır: aradakı aylar artıq ödənilib, güzəşt orada verilməyib. O aylar üzrə fərqi `Əmək haqqı` modulunda `Vergi düzəliş hesabatı` göstərir.

## Hüquqi əsas və hesablama nümunələri
Bu bölmə mühasib və sistem administratoru üçündür: dərəcələrin hüquqi əsasını, güzəştlərin necə birləşdiyini və rəqəmlərin nə verdiyini göstərir. Nümunələr avtomatik testlərlə yoxlanılıb.

### Rejimlər üzrə dərəcələr
**Özəl qeyri-neft-qaz sektoru** (`Bütün rejimlər (standart)` sətirləri; Vergi Məcəlləsi m.101.1-1, «Sosial sığorta haqqında» Qanun m.14.4, «Tibbi sığorta haqqında» Qanun m.15-10.1.2-1):
- gəlir vergisi, 2026: 2500 AZN-dək 3%; 2500–8000 arası 75 + 10%; 8000-dən çox 625 + 14%;
- gəlir vergisi, 01.01.2027-dən: 5%; 125 + 10%; 675 + 14%;
- gəlir vergisi, 01.01.2028-dən: 7%; 175 + 10%; 725 + 14%;
- DSMF, işçi: 200 AZN-dək 3%, yuxarı 10%;
- DSMF, işəgötürən: 200-dək 22%; 200–8000 arası 15%; 8000-dən çox hissədən 11% (1214 + 11%). Əvvəl 8000-dən yuxarı da 15% tətbiq olunurdu, bu düzəldilib;
- işsizlik sığortası: işçi 0,5%, işəgötürən 0,5%;
- icbari tibbi sığorta, hər tərəf: 2500 AZN-dək 2%, yuxarı 0,5%.

**Dövlət və neft-qaz sektoru** (`state` rejimi; VM m.101.1 1-ci cədvəl, «Sosial sığorta haqqında» m.14.3, «Tibbi sığorta haqqında» m.15-10.1.2):
- gəlir vergisi: 2500 AZN-dək 14%, yuxarı 350 + 25%;
- DSMF: işçi 3%, işəgötürən 22% (pillələr olmadan);
- işsizlik sığortası: 0,5% / 0,5%;
- icbari tibbi sığorta, hər tərəf: 8000 AZN-dək 2%, yuxarı 0,5%.

**Hərbi rejim** (`military`): dərəcələr dövlət rejimi ilə eynidir, fərqlər bunlardır:
- vergi bazası — VM m.102.1.14.15 üzrə yalnız vəzifə və hərbi (xüsusi) rütbəyə görə maaşlar (`base`, `rank_supplement`); qalan ödənişlər (staj, məxfilik, dil bilik əlavəsi, birdəfəlik mükafat, əmr ödənişləri) vergidən azaddır;
- DSMF bazası — yalnız pul təminatı («Sosial sığorta haqqında» m.15, «Əmək pensiyaları haqqında» m.1.0.5): `base`, `rank_supplement`, `seniority`, `hazard`, `secrecy`, `language`; birdəfəlik mükafat və əmr ödənişləri bazaya düşmür;
- işsizlik sığortası — 0% / 0%.

Hərbi rejim üzrə mühasib təsdiqi tələb olunan məqamlar:
- işsizlik sığortasının 0% olması qanunda açıq istisna kimi yazılmayıb: «İşsizlikdən sığorta haqqında» Qanunun m.2.0.3 (sığortaolunan — əmək müqaviləsi bağlamış işçi) və Əmək Məcəlləsinin m.6 (Məcəllə hərbi qulluqçulara şamil edilmir) əsasında sistemli şərhdir. Rəsmi izah tapılmayıb, mühasib təsdiqi gözlənilir (xüsusən kontrakt əsasında xidmət edənlər üçün);
- «ərzaq payı əvəzinə pul kompensasiyası» kataloqda ayrıca komponent kimi yoxdur — varsa DSMF siyahısına əlavə edilməlidir;
- büdcədənkənar vəsait hesabına olan əlavələr DSMF siyahısından çıxarılmalıdır;
- icbari tibbi sığortanın hərbçi üçün bazası ayrıca yoxlanmayıb — hələ ümumi sosial bazadan hesablanır.

**Nümunə (hərbi qulluqçu).** Vəzifə maaşı 1 000 + rütbə maaşı 300 + dil bilik əlavəsi 200 = 1 500 AZN. Vergi bazası 1 300 (vəzifə + rütbə); VM m.102.1.6 üzrə 200 çıxılır, (1 300 − 200) × 14% = **154,00**. DSMF bazası 1 500: işçi 45,00, işəgötürən 330,00. İcbari tibbi 30,00 / 30,00. İşsizlik sığortası tutulmur. 500 AZN birdəfəlik mükafat əlavə olunsa, nə vergi bazasına, nə DSMF bazasına düşmür.

### Gəlir vergisi güzəştləri
Güzəşt dərəcəyə yox, vergi tutulan gəlirə tətbiq olunur: vergi = cədvəl(gəlir − güzəştlər).

**VM m.102.1.6 — 200 AZN.** Əsas iş yerində aylıq gəlir 2500 AZN-dək olduqda 200 AZN vergidən azaddır:
- hədd daxildir: 2 500,00 AZN güzəşt alır, 2 500,01 AZN almır;
- özəl qeyri-neft sektorunda 101.1-1 cədvəli ilə birlikdə tətbiq olunur (Dövlət Vergi Xidmətinin izahı);
- hər rejimə tətbiq olunur, işçi kartı tələb etmir;
- ayda bir dəfə verilir: ayın ikinci ödənişi (`Off-cycle`) güzəşti təkrar vermir;
- natamam ayda 200 AZN tam verilir (mətndə mütənasiblik yoxdur — bu, mühasib təsdiqi tələb edən oxunuşdur).

**Əsas iş yeri.** İşçi kartındakı `İş yeri: əsas və ya əlavə` sahəsi `Əlavə` olanda heç bir güzəşt verilmir (VM m.102.1.6 və 102.8). Sahə boşdursa, iş yeri əsas sayılır.

**Şəxsi güzəştlərin birləşməsi (VM m.102.7).** 102.1-1, 102.2, 102.3 və 102.4 üzrə bir neçə hüquq olduqda yalnız məbləğcə ən böyüyü verilir. 102.1.6 (200 AZN) və 102.5 (50 AZN) bu siyahıda yoxdur və üstəgəl tətbiq olunur. Bu oxunuş mətnin sözbəsöz şərhidir, mühasib təsdiqi tələb edir.

**Tarixlər (VM m.102.8).** Güzəşt «sənədlərin təqdim edildiyi andan» tətbiq olunur:
- status əmək müqaviləsinin qüvvəyə mindiyi tarixdə və ya ondan əvvəl var idisə — qanuni başlanğıc işə qəbul tarixidir (sənəd sonra gətirilsə də);
- status sonra yaranıbsa — qanuni başlanğıc sənədlərin təqdim edildiyi tarixdir (statusun tarixindən tez olmayaraq);
- maaşda tətbiq tarixi qanuni başlanğıcdır, amma işçinin son kilidlənmiş ayından sonra: ödənilmiş aylar avtomatik yenidən hesablanmır.

**Nümunələr** (özəl qeyri-neft sektoru, 2026, əsas iş yeri):

Aylıq gəlir 1 100 AZN:
- kart yoxdur: azad 200 → (1 100 − 200) × 3% = **27,00**;
- `VM 102.3`: azad 200 + 200 → 700 × 3% = **21,00**;
- `VM 102.2` + `VM 102.3`: yalnız böyüyü (400) götürülür → azad 200 + 400 → 500 × 3% = **15,00**;
- `VM 102.2` + `VM 102.3` + `VM 102.5`: azad 200 + 400 + 50 → 450 × 3% = **13,50**;
- `VM 102.1-1` + `VM 102.2`: azad 200 + 800 → 100 × 3% = **3,00**;
- dövlət rejimi, `VM 102.3`: azad 200 + 200 → 700 × 14% = **98,00**;
- əlavə iş yeri, `VM 102.2`: güzəşt yoxdur → 1 100 × 3% = **33,00**.

Aylıq gəlir 3 000 AZN, `VM 102.2`: 102.1.6 verilmir (gəlir 2500-dən çoxdur), azad yalnız 400 → 2 600 üzrə 75 + 100 × 10% = **85,00** (kartsız 125,00).

Maaş vərəqəsində güzəşt ayrıca görünür, məsələn `Gəlir vergisi (vergidən azad olunan hissə 600,00 AZN — VM 102.1.6, VM 102.2)`.

### Rejim səhvi və keçmiş aylar
Rejim yanlış seçilibsə (məsələn, neft-qaz subpodratçısı özəl rejimdə ödənilibsə), `Rejim və sektor` bölməsi gələcək aylar üçün düzəldir, keçmiş aylar isə dəyişmir. Nümunə: işçi 1 100 AZN-lə özəl rejimdə iyunda ödənilib, iyun kilidlənib; 01.07.2026-dan dövlət rejiminə keçirilir. İyun üçün düzgün hesab: vergi (1 100 − 200) × 14% = 126,00 (tutulub 33,00); DSMF işçi 3% = 33,00 (tutulub 96,00); DSMF işəgötürən 22% = 242,00 (ödənilib 179,00). Fərq `Vergi düzəliş hesabatında` görünür; dəqiqləşdirilmiş hesabatı mühasib verir.

Subpodratçı üçün neft-qaz statusu il ərzində dəyişə bilər (gəlirin 50%-i həddi, NK-nın 56 nömrəli Qərarının 2.1.2.2 bəndi): sistem bunu izləmir, sektoru əl ilə dəyişib rejimi düzəltmək lazımdır.

## Maaş məbləğlərinin gizlədilməsi
Maaş rəqəmləri həssas məlumatdır. Məbləğləri görmə icazəniz yoxdursa:
- `Cari maaş` və `Tarixçə`də məbləğ yerinə `•••` görünür;
- şkalaların `Minimum`, `Orta nöqtə`, `Maksimum` sütunlarında və pillələrin `Məbləğ` sütununda `•••` görünür.

Siz qalan məlumatları (rejim, tarix, status) yenə görürsünüz. Rəqəmləri görmək lazımdırsa, sistem administratoruna müraciət edin.

## Tez-tez verilən suallar

**`Şkala əlavə et` (və ya başqa qara düymə) niyə görünmür?**
Sizdə idarəetmə icazəsi yoxdur. Baxış icazəsi ilə yalnız məlumatlara baxmaq olur.

**Maaş yerinə niyə `•••` görünür?**
Maaş məbləğlərini açıq görmək üçün ayrıca icazə lazımdır. O olmadıqda rəqəmlər gizlədilir.

**`Bank hesabı əlavə et` düyməsi yoxdur.**
Əvvəlcə yuxarıdakı `Əməkdaş` sahəsindən əməkdaşı seçin — düymə bundan sonra çıxır.

**Pillələr cədvəli boşdur.**
Soldakı cədvəldə şkalanın adına basın. Şkala seçilibsə və yenə boşdursa, həmin şkalada hələ pillə yoxdur.

**Yeni maaş təyin etdim, köhnəsi hara getdi?**
Köhnə maaş avtomatik bağlanıb. Onu `Tarixçə` bölməsində `Bitmiş` statusu ilə görə bilərsiniz.

**Komponenti yadda saxlamaq olmur, kod xətası çıxır.**
Bu kod artıq başqa komponentdə istifadə olunub. Başqa kod yazın.

**`Güzəşt kartı əlavə et` düyməsi yoxdur.**
Əvvəlcə `Vergi güzəştləri` bölməsində yuxarıdakı `Əməkdaş` sahəsindən əməkdaşı seçin. Düymə yalnız idarəetmə icazəsi olanlara görünür.

**Güzəşt kartı əlavə etdim, amma maaşda dəyişiklik görmürəm.**
Üç səbəb ola bilər: işçinin kartında `İş yeri: əsas və ya əlavə` sahəsi `Əlavə`dir (güzəşt yalnız əsas iş yerində verilir); kartın maaşda tətbiq tarixi gələcək aydadır (kartda `maaşda ... tarixindən` yazılır — ondan əvvəlki ödənilmiş aylara toxunulmur); ya da hesablama yenidən aparılmayıb. Qaralama və ya `Hesablanıb` statusunda olan hesablamada `Hesabla` basın.

**Kart silinmir.**
Kart kilidlənmiş (ödənilmiş) ayda tətbiq olunub. `Güzəşti bitir` istifadə edin.

**`Güzəşti bitir` tarixi qəbul edilmir.**
Tarix işçinin kilidlənmiş ayına düşür və ya statusun başlanğıcından əvvəldir. Kilidlənmiş aydan sonrakı tarix seçin.

**Neft-qaz şirkətiyik, amma işçilər özəl rejimdədir.**
`Rejim və sektor` bölməsində sektoru `Neft-qaz sahəsində fəaliyyəti olan` seçin, sonra fərqli rejimli işçiləri siyahıdan seçib rejimi düzəldin. Kilidlənmiş aylar üzrə fərqi `Əmək haqqı` modulunda `Vergi düzəliş hesabatı` göstərir.

**Siyahıdakı işçi `keçirilmədi` sətri ilə qaldı.**
İşçinin altındakı sarı sətir səbəbi yazır. Ən çox rast gələn: seçilmiş tarix işçinin kilidlənmiş ayına düşür — sonrakı ayın 1-ni seçin; yaxud işçinin gələcək tarixli başqa maaş təyinatı var — əvvəlcə onu yoxlayın.

**Sektoru dəyişdim, işçilərin rejimi dəyişmədi.**
Bu, düzgündür. Sektor yalnız yeni təyinatların standartını dəyişir. Mövcud işçiləri `Rejimi şirkət standartından fərqli olan işçilər` cədvəlindən ayrıca düzəldin.

**Hərbi qulluqçuda işsizlik sığortası niyə tutulmur?**
Hərbi rejimdə işsizlik sığortası dərəcəsi 0%-dir. Bu, qanunun açıq istisnası deyil, sistemli şərhdir və mühasib təsdiqi gözlənilir. Fərqli rəy varsa, `Vergi/sığorta dərəcələri` bölməsində hərbi rejim üçün dərəcəni yeni tarixlə əlavə edin.

## Yadda saxlayın
- Maaş hesablaması bu modulun məlumatlarına əsaslanır — dəyişikliyi hesablamadan əvvəl edin.
- Yeni maaş üçün köhnəni silməyin, sadəcə yeni təyinat edin.
- Hər əməkdaşın bir `Əsas` bank hesabı olsun.
- Silmədən əvvəl sistem təsdiq soruşur — silinmiş qeydi geri qaytarmaq olmur.
- `•••` xəta deyil, icazə ilə bağlı gizlətmədir.
- Şirkətin sektoru dəyişəndə mövcud işçilərin rejimi öz-özünə dəyişmir — fərqli rejimli işçiləri `Rejim və sektor` bölməsindən ayın 1-i tarixi ilə düzəldin.
- Güzəşt kartını sənədlə və düzgün tarixlərlə daxil edin: vergi güzəşti sənəd təqdim edildiyi andan tətbiq olunur, ödənilmiş aylara avtomatik geriyə baxılmır.
- Güzəşt yalnız əsas iş yerində verilir — işçi kartında `İş yeri: əsas və ya əlavə` sahəsini yoxlayın.
- 800/400/200/100 AZN güzəştlərindən yalnız ən böyüyü verilir; 200 AZN-lik `VM 102.1.6` və 50 AZN-lik `VM 102.5` onlara əlavə olunur.
- `vm_102_1_1` … `vm_102_5` kodlu güzəştlərin məbləğini `Azadolma əlavə et` formu ilə dəyişməyin.
- Hərbi rejimdə işsizlik sığortasının 0% olması mühasib təsdiqi gözləyir.
