# İcazələr istifadəçi bələdçisi

## Bu modul nə üçündür?
Bu modul əməkdaşların qısamüddətli icazələrini (tam gün, yarım gün və ya bir neçə saat) qeydə almaq, təsdiqə göndərmək və izləmək üçündür.

Sadə dildə bu modul sizə bu suallara cavab verir:
- kim, hansı növ icazə alıb?
- icazə hansı tarixlərə və neçə günə (saata) düşür?
- icazə təsdiq gözləyir, təsdiqlənib, yoxsa ləğv edilib?
- icazəni kim təsdiqləməlidir və kim təsdiqləyib?

Təsdiqlənmiş icazə davamiyyət cədvəlində (puantajda) həmin günlərə avtomatik düşür. Ona görə icazə sadəcə qeyd deyil, ay yekununa da təsir edir.

## Harada açılır?
Sol menyudan `İcazələr` bölməsini açın. Səhifənin başlığı `İcazə müraciətləri`dir.

Səhifənin solunda `İcazələr` paneli açılır. Panelin yuxarısında ümumi müraciət sayı yazılır, altında isə status və icazə növü siyahıları var (aşağıda izah olunur).

İcazəni əməkdaşın şəxsi işindən də əlavə etmək olur. Bu halda forma həmin əməkdaş artıq seçilmiş halda açılır.

## Bu modul kimlər üçündür?

### HR əməkdaşı / operator
- yeni icazə əlavə edir
- məlumatları düzəldir, sənəd yükləyir
- siyahını Excel-ə çıxarır

### Rəhbər (təsdiq verən şəxs)
- ona təyin olunmuş icazələri təsdiqləyir və ya rədd edir
- təsdiq zamanı qısa şərh yazır

### Admin
- silinmiş qeydlərə baxır, onları bərpa edir və ya tam silir

Düymələr icazəyə görə görünür:
- `İcazə əlavə et` düyməsi yalnız icazə əlavə etmək hüququ olanlara görünür
- redaktə düyməsi yalnız redaktə hüququ olanlara görünür
- sil, bərpa et, tam sil düymələri və `Silinmiş` bölməsi yalnız silmə hüququ olanlara görünür
- Excel düyməsi yalnız ixrac hüququ olanlara görünür

## Ekranın quruluşu

### Başlıq hissəsi
Başlığın altında üç göstərici var:
- ümumi `müraciət` sayı
- neçə müraciətin `təsdiq gözləyir` vəziyyətində olduğu (sarı rəngdə)
- bütün seçilmiş icazələrin `gün ekvivalenti` (saatlıq icazələr gün hissəsinə çevrilib toplanır, yarım gün 0,5 sayılır)

Sağ tərəfdə iki düymə var:
- yaşıl Excel işarəsi — `Excel-ə ixrac et`
- qara `İcazə əlavə et` düyməsi — səhifənin əsas düyməsi

### Sol panel
Birinci hissə statuslardır. Hər birinin yanında say göstərilir:
- `Hamısı` — bütün müraciətlər
- `Təsdiq gözləyən` — sarı nöqtə
- `Təsdiqlənmiş` — yaşıl nöqtə
- `Ləğv edilmiş` — qırmızı nöqtə
- `Silinmiş` — silinmiş qeydlər (yalnız silmə hüququ olanlara)

İkinci hissə `İcazə növü`dür. Növə basanda siyahı yalnız həmin növü göstərir. Geri qayıtmaq üçün `← Hamısını göstər` seçin.

Kiçik ekranda sol panel görünmürsə, eyni status seçimləri filtrlərin altında düymələr şəklində çıxır.

### Filtrlər
Filtrlər yazdıqca və ya seçdikcə dərhal işləyir, ayrıca "axtar" düyməsi yoxdur:
- `Soyad, ad, ata adı` — əməkdaşın adına görə axtarış
- `Tarixlər` — başlama və bitmə tarixi aralığı
- `Səbəb` — səbəb mətnində axtarış
- `Cins` — `Hamısı`, kişi və ya qadın

Hər hansı filtr aktivdirsə, yanında sıfırlama düyməsi çıxır və bütün filtrləri birdən təmizləyir.

Filtrlərin altında qısa qeyd var: `Təsdiq iyerarxiyaya görə təyin olunur`.

### Siyahı
Sütunlar:
- `Soyad, ad, ata adı` — əməkdaş, vəzifəsi və strukturu
- `Növ` — icazə növü
- `Tarixlər` — tarix aralığı, müddət (məsələn `3 gün`, `Yarım gün`, `2 saat`) və lazım olsa günün hissəsi və ya saat aralığı
- `Səbəb` — formada yazılmış izah
- `Status` — rəngli status nişanı; qərar verilibsə altında qərarı verən şəxs və tarix-saat
- `Fayl` — sənəd varsa keçid işarəsi (`Sənədi aç`), yoxdursa tire
- son sütun — əməliyyat düymələri

Status nişanlarının mənası:
- sarı — `Təsdiq gözləyən` (belə sətirlər açıq sarı fonla da seçilir)
- yaşıl — `Təsdiqlənmiş`
- qırmızı — `Ləğv edilmiş`

Statusun yanında danışıq işarəsi varsa, qərar verilərkən şərh yazılıb. Ona basın — şərh kiçik pəncərədə açılır (`Şərhi göstər`).

Siyahı səhifələrlə göstərilir, hər səhifədə 10 müraciət olur.

## Əsas əməliyyatlar

### 1. Yeni icazə əlavə etmək
1. `İcazə əlavə et` düyməsini basın. Sağ tərəfdə forma açılır.
2. `Personal axtar` sahəsində əməkdaşın adını yazıb siyahıdan seçin.
3. `İcazə növü`nü seçin. Altında iki nişan görünəcək:
   - `Maksimum: N gün` və ya `Gün limiti yoxdur`
   - `Sənəd tələb olunur` və ya `Sənəd tələb olunmur`
4. `Müddət növü`nü seçin: `Tam gün`, `Yarım gün` və ya `Saatlıq`.
   - `Yarım gün` seçilsə, `Günün hissəsi` sahəsi çıxır: `Günün ilk yarısı` və ya `Günün ikinci yarısı`.
   - `Saatlıq` seçilsə, `Başlama saatı` və `Bitmə saatı` sahələri çıxır.
5. `Başlama tarixi`ni və (tam gün üçün) `Bitmə tarixi`ni seçin. `Ümumi gün` sahəsi avtomatik hesablanır, onu əl ilə dəyişmək olmur.
6. `Səbəb` sahəsinə qısa izah yazın (məsələn, "Həkim qəbulu").
7. `Status` seçin. Yeni müraciət üçün adətən `Təsdiq gözləyən` seçilir.
8. İcazə növü sənəd tələb edirsə, faylı yükləyin — sənədsiz yadda saxlamaq olmur.
9. Sağdakı `Təsdiq marşrutu` hissəsini yoxlayın (aşağıda izah olunur).
10. `Yadda saxla` düyməsini basın.

#### Sonra nə olur?
- "İcazə uğurla əlavə olundu!" mesajı çıxır
- müraciət siyahının yuxarısında görünür
- forma təmizlənir, növbəti icazəni dərhal yazmaq olar

#### Gün limiti aşılanda
Seçdiyiniz günlər icazə növünün maksimumundan çoxdursa, formada qırmızı `Gün limiti aşılıb` xəbərdarlığı çıxır. Bu qadağa deyil — yadda saxlamaq mümkündür, sadəcə nəzərə almaq lazımdır.

### 2. Təsdiq marşrutunu yoxlamaq
Formanın `Təsdiq marşrutu` hissəsində iki seçim var: `Avtomatik seçim` və `Manual seçim`.

`Avtomatik seçim` açıq olanda sistem əməkdaşın strukturuna və vəzifəsinə görə rəhbəri özü tapır. Bu hissə kartlara bölünüb:
- `Təsdiq addımları` — nömrələnmiş addımlar: 1 — `Birbaşa rəhbər`, 2 — `Yuxarı xətt`. Yuxarı rəhbər ayrıca təsdiq vermirsə, bu barədə qeyd yazılır; tapılmırsa `Yuxarı addım tapılmadı` görünür.
- `Hazırki təyinat` — müraciətin kimə təsdiqə gedəcəyi (ad və vəzifə). Heç kim tapılmayıbsa `Təyin edilməyib` yazılır.
- `Bütün ierarxik xətt` — açılıb-bağlanan kart; əməkdaşın üstündəki bütün rəhbərlərin sırası
- `Marşrut haqqında` — açılıb-bağlanan kart; `HR aktivdir` / `HR aktiv deyil` və `Təyinat mənbəyi` nişanları

`Manual seçim` açanda təsdiq edəcək şəxsi özünüz seçirsiniz: `Manual təyin olunan şəxs` sahəsində adı yazıb siyahıdan seçin.

### 3. İcazəni təsdiqləmək və ya rədd etmək
Bu düymələr yalnız statusu `Təsdiq gözləyən` olan və sizə təyin olunmuş müraciətlərdə görünür.

1. Siyahıda müraciəti tapın (sol paneldə `Təsdiq gözləyən` seçmək rahatdır).
2. Sətrin sonunda yaşıl işarəyə (`Təsdiqlə`) və ya qırmızı işarəyə (`Rədd et`) basın.
3. `Şərh əlavə et` pəncərəsi açılır. Qısa şərh yazın.
4. `Yadda saxla` basın. Fikrinizi dəyişsəniz — `Ləğv et`.

#### Sonra nə olur?
- status `Təsdiqlənmiş` və ya `Ləğv edilmiş` olur
- statusun altında adınız və tarix-saat görünür, şərh danışıq işarəsi ilə açılır
- təsdiqlənmiş icazə davamiyyət cədvəlində həmin günlərə düşür
- qərar verilmiş müraciətdə təsdiq/rədd düymələri artıq görünmür

### 4. Mövcud icazəni düzəltmək
1. Siyahıda müraciəti tapın.
2. Sətrin sonunda sənəd işarəsinə (`Redaktə et`) basın.
3. `İcazəni redaktə et` forması açılır — sahələr yeni icazədəki kimidir.
4. Lazımi düzəlişi edib `Yadda saxla` basın.

Düzəliş qaydaları:
- **Əmrlə yaranmış icazə** (siyahıda `Əmrlə` nişanı) burada dəyişdirilmir, silinmir və bərpa edilmir — onu yalnız əmri geri qaytarmaqla və ya ləğv etməklə dəyişmək olar. Xəstəlik vərəqəsinə bağlı icazə də eyni qayda ilə yalnız `Xəstəlik vərəqələri` reyestrində dəyişir.
- **Təsdiqlənmiş icazənin tarixi** (əməkdaş, növ, başlama/bitmə, saat) dəyişəndə icazə yenidən `Təsdiq gözləyən` statusuna qayıdır və təsdiq marşrutundan keçir. İcazələri təsdiqləmək hüququ olan şəxs dəyişdirirsə, icazə onun adına və dəyişiklik anına yenidən təsdiqlənmiş kimi qeyd olunur.
- **Öz icazəniz:** hesabınıza bağlı əməkdaşın icazəsini `Təsdiqlənmiş` statusu ilə yaza bilməzsiniz — o, təsdiq marşrutundan keçməlidir.
- **Bağlanmış ay:** əmək haqqı (və ya davamiyyət) üçün bağlanmış aya düşən icazə yalnız `Bağlanmış aya düşən icazələri dəyişmək` icazəsi ilə yaradılır, dəyişdirilir və silinir. Bu qayda həm köhnə, həm də yeni tarixlərə aiddir.

### 5. İcazəni silmək
1. Sətrin sonunda zibil qutusu işarəsinə (`Sil`) basın.
2. `İcazəni sil` pəncərəsi açılır və sistem təsdiq soruşur.
3. `Sil` basın.

Silinmiş qeyd itmir — sol paneldə `Silinmiş` bölməsinə keçir. Orada silinmə tarixi də göstərilir.

### 6. Silinmiş icazəni bərpa etmək və ya tam silmək
1. Sol paneldə `Silinmiş` seçin.
2. Qeydi geri qaytarmaq üçün bərpa işarəsinə (`Bərpa et`) basın — qeyd əvvəlki yerinə qayıdır.
3. Birdəfəlik silmək üçün `Tam sil` işarəsinə basın. Sistem "Bu məlumatı tam silmək istədiyinizə əminsiniz?" soruşacaq. Təsdiqdən sonra qeydi qaytarmaq mümkün olmur.

### 7. Excel-ə çıxarmaq
1. Lazımi filtrləri və statusu seçin.
2. Başlıqdakı yaşıl Excel işarəsinə basın.
3. Fayl yüklənir — içində ekranda seçdiyiniz filtrə uyğun bütün müraciətlər olur (yalnız cari səhifə yox).

## Tez-tez verilən suallar

**`İcazə əlavə et` düyməsi niyə görünmür?**
Sizdə icazə əlavə etmək hüququ yoxdur. Admininizə müraciət edin.

**Təsdiqlə / Rədd et düymələri niyə görünmür?**
Ya müraciət artıq `Təsdiqlənmiş` və ya `Ləğv edilmiş` vəziyyətindədir, ya da o sizə deyil, başqa rəhbərə təyin olunub. Formadakı `Hazırki təyinat` kartına baxın.

**Təsdiqlənmiş icazəni geri qaytarmaq olarmı?**
Təsdiq və rədd yekun qərardır, təsdiq düymələri ilə dəyişmək olmur. Səhv varsa, HR ilə əlaqə saxlayın.

**Yadda saxlayanda "sənəd" xətası çıxır. Niyə?**
Seçdiyiniz icazə növü üçün `Sənəd tələb olunur`. Faylı yükləyib yenidən yadda saxlayın.

**`Ümumi gün` sahəsinə niyə yaza bilmirəm?**
O sahə tarixlərə və müddət növünə görə avtomatik hesablanır.

**İcazə təsdiqləndi, amma puantajda görünmür. Nə edim?**
Statusun həqiqətən `Təsdiqlənmiş` olduğunu, tarixləri və icazə növünü yoxlayın. Sonra davamiyyət cədvəlində həmin günlərə baxın.

**Silinmiş qeydi harada tapım?**
Sol paneldə `Silinmiş` bölməsində. Bu bölmə yalnız silmə hüququ olanlara görünür.

## Yadda saxlayın
- Yadda saxlamadan əvvəl `Təsdiq marşrutu`nda `Hazırki təyinat`ı yoxlayın — müraciət o şəxsə gedəcək.
- Saatlıq və yarım günlük icazələrdə müddət növünü düzgün seçin, əks halda puantaj səhv hesablanar.
- `Səbəb` sahəsini qısa və aydın yazın — sonradan axtarışda işinizə yarayacaq.
- Təsdiq və rədd zamanı şərh yazın, sonra qərarın səbəbini hamı görə bilsin.
- `Tam sil` geri qaytarılmır — şübhə varsa adi `Sil` istifadə edin.
