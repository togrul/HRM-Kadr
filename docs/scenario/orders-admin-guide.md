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

## Yadda saxlayın
- Word faylını tam hazırlayıb yükləyin — sistem mətni dəyişmir, yalnız dəyişənləri doldurur.
- Dəyişən adlarını `Dəyişən lüğəti`ndən kopyalayın ki, avtomatik tanınsın.
- Hər dəyişiklikdən sonra sınaq əmri ilə yoxlayın.
- Köhnə versiya lazım olsa, `Versiya tarixçəsi`ndən endirin.
