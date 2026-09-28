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

Bəzi bölmələrin yanında say görünür (məsələn, neçə şkala, neçə aktiv maaş var). `Bank` və `Tarixçə` bir əməkdaşa aid olduğu üçün orada ümumi say göstərilmir.

Panelin aşağısında qısa xülasə var: `Şkalalar`, `Pillələr`, `Aktiv komponentlər`, `Aktiv maaşlar`.

Panelin ən altındakı `İstifadə təlimatı` keçidi bu bələdçini açır.

## Bu modul kimlər üçündür?
- **Baxış icazəsi olanlar** — şkalaları, kataloqu, maaşları və tarixçəni görə bilər, amma dəyişə bilməz.
- **İdarəetmə icazəsi olanlar** — şkala, pillə, komponent, maaş, bank hesabı və dərəcə əlavə edir, redaktə edir, silir.
- **Məbləğləri görmə icazəsi olanlar** — maaş rəqəmlərini açıq görür. Bu icazə yoxdursa, rəqəmlərin yerində `•••` görünür.

## Ekranın quruluşu

### Başlıq
Yuxarıda səhifə adı (`Kompensasiya`) və üç göstərici var: `Şkalalar`, `Pillələr`, `Aktiv maaşlar`.

Başlıqdakı düymələr:
- `Kataloq` — birbaşa komponent kataloquna keçir.
- Qara əsas düymə — açıq bölməyə görə dəyişir:
  - `Şkalalar` bölməsində: `Şkala əlavə et`
  - `Kataloq` bölməsində: `Komponent əlavə et`
  - `Bank` bölməsində (əməkdaş seçiləndən sonra): `Bank hesabı əlavə et`
  - `Vergi/sığorta dərəcələri` bölməsində: `Dərəcə əlavə et`

Qara düymə yalnız idarəetmə icazəsi olanlara görünür. `İşçi maaşları` və `Tarixçə` bölmələrində qara düymə olmur — orada forma səhifənin özündədir.

Kiçik ekranda bölmələr başlığın altında tab kimi görünür.

### Əməkdaş seçimi
`İşçi maaşları`, `Bank` və `Tarixçə` bölmələri bir əməkdaş üzrə işləyir. Bu bölmələrin yuxarısında `Əməkdaş` sahəsi var:
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

### İşçi maaşları
Əməkdaşı seçəndən sonra yuxarıda `Cari maaş` kartı görünür: məbləğ, valyuta və qüvvəyə minmə tarixi.

**Yeni maaş təyin etmək:**
1. Əməkdaşı seçin.
2. `Yeni təyinat` formasında `Rejim`, `Baza məbləğ` və `Qüvvəyə minmə` tarixini doldurun.
3. İstəsəniz `Əmr nömrəsi` və `Qeyd` yazın.
4. Əlavə və ya tutulma lazımdırsa, `Əlavə / tutulma sətirləri` hissəsində `Sətir əlavə et` basın, `Komponent` seçin və `Məbləğ` və ya `Faiz` yazın. Artıq sətri `Sil` ilə çıxarın.
5. `Maaşı təyin et` basın.

Yeni təyinat yadda saxlananda əvvəlki aktiv maaş avtomatik bağlanır və tarixçəyə düşür. Yəni köhnə maaşı ayrıca silmək lazım deyil.

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

## Yadda saxlayın
- Maaş hesablaması bu modulun məlumatlarına əsaslanır — dəyişikliyi hesablamadan əvvəl edin.
- Yeni maaş üçün köhnəni silməyin, sadəcə yeni təyinat edin.
- Hər əməkdaşın bir `Əsas` bank hesabı olsun.
- Silmədən əvvəl sistem təsdiq soruşur — silinmiş qeydi geri qaytarmaq olmur.
- `•••` xəta deyil, icazə ilə bağlı gizlətmədir.
