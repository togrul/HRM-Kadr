# Əmrlər Admin Bələdçisi

Bu bələdçi əmr növlərini və onların Word şablonlarını idarə edən şəxs (şablon sahibi) üçündür.

## Harada açılır?
1. `Əmrlər` səhifəsini açın.
2. Başlıqdakı `⋯` (`Digər əməliyyatlar`) düyməsini basın.
3. `Əmr növü dizayneri` seçin.

Bu menyu və dizayner yalnız əmrləri redaktə etmə icazəsi olanlara görünür.

## Dizaynerin quruluşu
Yuxarıda sabit zolaq var:
- `Əmrin adı` — siyahıda və əmr yaratma panelində görünən ad
- `kod` — əmr növünün qısa kodu; yalnız yeni şablon yaradanda yazılır, sonra dəyişmir
- qara `Yadda saxla` düyməsi

Yeni şablon açanda əvvəlcə `Mövcud şablonlar` siyahısı görünür. Hazır şablonu açmaq üçün onun yanındakı `Bax / Redaktə →` seçin. Şablonun içindən siyahıya qayıtmaq üçün zolağın solundakı geri düyməsindən istifadə edin.

## Yeni əmr növü yaratmaq
1. **Word faylını hazırlayın.** Əmri Microsoft Word-də tam yazın. Dəyişən hissələri kvadrat mötərizədə yazın, məsələn: `[Başlama tarixi]`.
2. **`Dəyişən lüğəti`ndən istifadə edin.** Bu bölməni açın, lazım olan dəyişənə klikləyin — o, kopyalanır (`Kopyalandı ✓`). Sonra Word-ə yapışdırın. Lüğətdəki dəyişənləri sistem özü tanıyır.
3. **Faylı yükləyin.** `Word şablonunu yükləyin` bölməsində `Word faylı seçin (.docx)` basın. Sistem faylı oxuyur (`Fayl oxunur…`) və bütün `[dəyişən]`-ləri tapır.
4. **Təsdiq nəticəsini seçin.** `Bu əmr təsdiqlənəndə nə baş versin?` siyahısından seçin: `Yoxdur (yalnız sənəd)`, `Məzuniyyət (işçi məzuniyyətə düşür)`, `Xitam (əmək müqaviləsi bitir)`, `Köçürmə (struktur/vəzifə dəyişir)`, `Soyad dəyişikliyi`, `Pul mükafatı (şəxsi işə yazılır)` və ya `İşə qəbul (namizəd işçi olur)`.
5. **Dəyişənləri bağlayın.** `Dəyişənlər` bölməsində hər dəyişən üçün:
   - `Avtomatik` — sistem məlumatından doldurulur; `Mənbəni seçin` siyahısından mənbəyi göstərin
   - `Əl ilə` — əmr yaradan şəxs dəyəri özü yazacaq; sahənin növünü seçin (`Mətn`, `Rəqəm`, `Tarix`, `İş ili (tarixdən aralıq)`, `Struktur (siyahıdan)` və s.)
   - əl ilə sahə təsdiq nəticəsində iştirak edirsə, onun rolunu seçin (məsələn, `Başlama tarixi`, `Yeni vəzifə`, `Məbləğ`); iştirak etmirsə `Effektdə rolu yoxdur` qalsın
6. `Əmrin adı` və `kod` yazın, `Yadda saxla` basın. `Şablon yadda saxlanıldı.` bildirişi çıxır.

Bundan sonra yeni növ əmr yaratma panelindəki `Əmrin növü` siyahısında görünür.

## Mövcud şablonu yoxlamaq və düzəltmək
Şablonu açanda `Hazır şablon` bölməsi görünür:
- `Şablona bax` — şablonu PDF kimi göstərir, dəyişənlər `[mötərizə]` içində görünür
- `Word-də yüklə (düzəliş üçün)` — faylı endirir; Word-də düzəldib yenidən yükləyin

Yeni fayl yükləyib yadda saxlayanda köhnə fayl avtomatik arxivlənir. Arxiv `Versiya tarixçəsi` siyahısında görünür (`v1`, `v2` …, tarix ilə). Hər köhnə versiyanı `Word-də yüklə (düzəliş üçün)` ilə endirmək olar.

## İntizam tənbehi: müddət ayarı və şablon

### Qüvvədə qalma müddəti
İntizam tənbehinin müddəti ƏM Md.190.1 ilə müəyyən olunub: verildiyi gündən **6 ay**. 12 ay yoxdur.

1. `Tənzimləmələr` bölməsində `İntizam tənbehinin qüvvədə qalma müddəti (ay)` ayarını tapın.
2. Qiymət 1–6 arasında ola bilər. Boş, sıfır, mənfi və ya 6-dan böyük qiymət **6** kimi oxunur.
3. Ayar yalnız **sonradan təsdiqlənən** əmrlərə təsir edir: tənbehin bitmə tarixi təsdiq anında yazılır.

Nümunə: ayar `12` yazılıbsa sistem 6 ay götürür. Ayar `3`-dürsə, 15.03.2026 tarixli tənbeh 15.06.2026-da bitir (qanundan əlverişli, qısa müddət qəbul olunur). Ayar boşdursa 6 ay.

#### Köhnə açıq tənbehlər
Yeniləmə miqrasiyası:
- 6-dan böyük ayarı 6-ya endirir;
- əmr nömrəsi olan, hələ götürülməmiş və bitmə tarixi «verilmə + 6 ay»dan sonra olan tənbehlərin tarixini verilmə + 6 aya çəkir; artıq qısa (daha əlverişli) tarixlərə toxunmur;
- artıq keçmiş tarixlər növbəti gündəlik işdə (`personnel:lift-expired-sanctions`, hər gün 00:20) «götürülmüş» olur;
- geri qaytarılmır: 12 aylıq müddət qanunsuz idi.

Gündəlik iş idempotentdir, `--date=Y-m-d` ilə xüsusi tarix üçün işlədilə bilər.

### `intizam_tenbehi` şablonu
İntizam əmri standart `intizam_tenbehi` kodlu şablonla çıxır. Təsdiq nəticəsi: `İntizam tənbehi (şəxsi işə yazılır)`. Əl ilə sahələr: `Pozuntunun təsviri`, `Tənbehin növü`, `Əsas mətni`.

Dizayner ekranında bu təsir üçün rollar bunlardır: `Tənbehin növü`, `Pozuntu`, `Tənbeh tarixi`. Standart şablonda `Pozuntunun təsviri` sahəsi `Pozuntu`, `Tənbehin növü` sahəsi `Tənbehin növü` roluna bağlıdır; `Tənbeh tarixi` bağlanmayıbsa tənbehin tarixi əmrin tarixidir. Rol qoyulmayıbsa, tənbehin səbəb sətri boş qalır və kataloqda ümumi sətir (`Əmək intizamını pozduğuna görə`) istifadə olunur.

### İşçi münasibətləri modulu ilə əlaqə
`İşçi münasibətləri` modulunda işin qərarı `Töhmət` və ya `Sonuncu xəbərdarlıqla şiddətli töhmət` seçiləndə sistem `Əmrlər`də əmr yaradır:

1. aktiv, təsiri `İntizam tənbehi (şəxsi işə yazılır)` olan şablonlar siyahısı alınır;
2. `intizam_tenbehi` varsa o seçilir, yoxdursa bu təsirli şablonlardan adına görə ilk olan;
3. heç bir aktiv intizam şablonu yoxdursa qərar verilmir: `«İntizam tənbehi» təsirli əmr şablonu tapılmadı — Əmrlər → Şablonlar bölməsində aktiv edin.`;
4. şablon var, amma əmr yaradıla bilmirsə: `İntizam əmri qaralaması yaradıla bilmədi. Şablonu yoxlayın.`

Yaranan əmr `Təsdiq gözləyən` statusundadır, əmr nömrəsi olaraq işin nömrəsi yazılır, `Əmr #` sütununda həmin nömrə görünür.

#### Şablonu aktiv saxlamaq
- `Əmr növü dizayneri`ndə şablonu `Yadda saxla` ilə saxlamaq şablonu aktiv edir.
- `intizam_tenbehi` şablonunun təsir nəticəsini (`Yoxdur (yalnız sənəd)`, `Xitam` və s.) dəyişməyin: İşçi münasibətləri ekranı yalnız `İntizam tənbehi` təsirli şablonları axtarır. Təsir dəyişsə və başqa intizam şablonu yoxdursa, tənbeh seçimi «şablon tapılmadı» xətası verər.
- Şablondakı `Pozuntunun təsviri`, `Tənbehin növü`, `Əsas mətni` adlarını dəyişməyin: İşçi münasibətləri modulu dəyərləri bu adlarla göndərir. Ad dəyişsə, sahələr boş çıxır.

### Əmr tərtibçisinin avtomatik qaralaması
Əmr tərtibçisi (`+ Əmrin tərtibatı`) istifadəçinin yarımçıq sahələrini avtomatik saxlayır:

- açar: istifadəçi + işçi (işçi olmadan açılanda ayrıca ümumi qaralama)
- müddət: 7 gün, hər dəyişiklikdə yenilənir
- bərpa: formanı yenidən açanda `Yarımçıq qalmış qaralama bərpa olundu.` zolağı çıxır, `Təmizlə` silir
- silinmə: əmr nəşr olunanda dərhal; boş sahələr yazılanda da
- saxlanmır: əmr nömrəsi, tarix, iştirakçı siyahısı
- redaktə rejimində saxlanmır
- yer: sistem keşi; keş təmizlənəndə (`cache:clear`) bütün qaralamalar itir

Admin üçün nəticə: bu, `Əmrlər` siyahısındakı `Qaralama` statusu deyil; verilənlər bazasında əmr sətri yaratmır.

## Riskli dəyişikliklər
- **Təsdiq nəticəsini dəyişmək.** Bu, gələcəkdə təsdiqlənən bütün əmrlərin nə edəcəyini dəyişir.
- **Dəyişəni silmək və ya adını dəyişmək.** Word-də mötərizədəki adı dəyişsəniz, yükləmədən sonra mənbəyini yenidən seçməlisiniz.
- **Rolu səhv seçmək.** Məsələn, məzuniyyət əmrində `Başlama tarixi` rolu verilməyibsə, təsdiqdə məzuniyyət düzgün yazılmaya bilər.

Dəyişiklikdən sonra bir sınaq əmri yaradın, `Önizləmə` ilə yoxlayın və lazım deyilsə silin.

## Tez-tez verilən suallar

**“Faylda [dəyişən] tapılmadı” yazılır.**
Word faylında dinamik hissələr kvadrat mötərizədə deyil. Onları `[Ad]` şəklində yazın.

**`Yadda saxla` basanda `Mənbəni seçin` qırmızı yazılır.**
`Avtomatik` seçilmiş dəyişən üçün mənbə seçilməyib. Ya mənbə seçin, ya da `Əl ilə` edin.

**`kod` sahəsi dəyişmir.**
Kod yalnız yeni şablonda yazılır. Mövcud şablonun kodu sabitdir.

**`⋯` menyusunda `Əmr növü dizayneri` yoxdur.**
Sizdə əmrləri redaktə etmə icazəsi yoxdur.

**İşçi münasibətləri modulunda töhmət seçəndə `«İntizam tənbehi» təsirli əmr şablonu tapılmadı` xətası çıxır.**
Aktiv intizam təsirli şablon yoxdur. `Əmr növü dizayneri`ndə `intizam_tenbehi` şablonunu açıb təsir nəticəsini `İntizam tənbehi (şəxsi işə yazılır)` saxlayın və `Yadda saxla` basın.

**Ayar 12 yazılıb, amma tənbeh 6 ay qüvvədədir.**
Bu, qanundur (ƏM Md.190.1): 6-dan böyük qiymət 6 kimi oxunur. Qısa müddət yazmaq olar.

**İstifadəçi yarımçıq əmrinin itdiyini deyir.**
Qaralama yalnız həmin istifadəçi və həmin işçi üçün saxlanır, 7 gündən sonra silinir, keş təmizlənəndə itir. Əmr nömrəsi, tarix və iştirakçılar heç vaxt saxlanmır.

## Yadda saxlayın
- Word faylını tam hazırlayıb yükləyin — sistem mətni dəyişmir, yalnız dəyişənləri doldurur.
- Dəyişən adlarını `Dəyişən lüğəti`ndən kopyalayın ki, avtomatik tanınsın.
- Hər dəyişiklikdən sonra sınaq əmri ilə yoxlayın.
- Köhnə versiya lazım olsa, `Versiya tarixçəsi`ndən endirin.
- İntizam tənbehi 6 ay qüvvədədir; ayarı 6-dan çox yazmaq təsir etmir.
- `intizam_tenbehi` şablonunun təsirini və sahə adlarını dəyişməyin: İşçi münasibətləri modulu onlara bağlıdır.
- Əmr tərtibçisinin qaralaması keşdədir: bu, siyahıdakı `Qaralama` əmri deyil.
